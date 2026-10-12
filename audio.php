<?php
/**
 * 888box Audio Upload Endpoint
 */
ob_start();
session_start();

require_once 'vendor/autoload.php';
require_once 'config/database.php';
require_once 'config/upload.php';
require_once 'config/audio_logic.php';
require_once 'config/turnstile.php';

// 初始化
$db = Database::getInstance();
$pdo = $db->getConnection();
$config = Database::getConfig($pdo);

// ============================================
// 工具函数 (从 api.php 移植)
// ============================================

function getConfigValue($pdo, $key) {
    $stmt = $pdo->prepare("SELECT value FROM configs WHERE `key` = ?");
    $stmt->execute([$key]);
    return (int)$stmt->fetch(PDO::FETCH_ASSOC)['value'];
}

function isDomainAllowed($host) {
    global $config;
    if (empty($host)) return false;
    $siteDomains = array_map('trim', explode(',', $config['site_domain']));
    if (in_array('*', $siteDomains)) return true;
    foreach ($siteDomains as $domain) {
        if ($host === parse_url($domain, PHP_URL_HOST)) {
            return true;
        }
    }
    return false;
}

function logMessage($message) {
    file_put_contents('上传日志.txt', "[" . date('Y-m-d H:i:s') . "] $message" . PHP_EOL, FILE_APPEND);
}

function respondAndExit($response) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function validateToken() {
    global $pdo, $config;
    
    // 1. 验证 Token 或 API Key (優先級最高)
    $user = resolveUploadUser($pdo);
    if ($user) {
        if (isset($user['status']) && (int)$user['status'] === 0) {
            respondAndExit(['result' => 'error', 'code' => 403, 'message' => '帳號已被停用，無存取權限']);
        }
        return;
    }
    
    // 2. 验证 Session
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        return;
    }
    
    // 3. 如果開啟了登入限制，則此時應拒絕
    $loginRestriction = isset($config['login_restriction']) && filter_var($config['login_restriction'], FILTER_VALIDATE_BOOLEAN);
    if ($loginRestriction) {
        respondAndExit(['result' => 'error', 'code' => 403, 'message' => '登入保護已開啟，請提供有效的 Token 或先登入']);
    }

    // 4. 驗證網域
    $refererHost = parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_HOST);
    $currentHost = parse_url(((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
    if (!empty($refererHost) && !empty($currentHost) && strcasecmp($refererHost, $currentHost) === 0) {
        return;
    }
    if (isDomainAllowed($refererHost)) return;
    
    respondAndExit(['result' => 'error', 'code' => 403, 'message' => '身分驗證失敗：無效的 Token、尚未登入或網域未授權']);
}

function setCorsHeaders() {
    global $config;
    
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $siteDomains = array_map('trim', explode(',', $config['site_domain']));
    
    if (in_array('*', $siteDomains)) {
        header("Access-Control-Allow-Origin: *");
    } else if (!empty($origin) && isDomainAllowed(parse_url($origin, PHP_URL_HOST))) {
        header("Access-Control-Allow-Origin: $origin");
        header("Access-Control-Allow-Credentials: true");
    } else {
        header("Access-Control-Allow-Origin: null");
    }
    
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key, X-Requested-With");
}

function formatSizeLimit($bytes) {
    if ($bytes < 1024 * 1024) {
        return max(1, round($bytes / 1024)) . 'KB';
    }

    $mb = $bytes / (1024 * 1024);
    return $mb < 10 ? number_format($mb, 1) . 'MB' : number_format($mb, 0) . 'MB';
}

// 设置CORS响应头
setCorsHeaders();

try {
    // 1. 验证请求方法
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respondAndExit(['result' => 'error', 'code' => 405, 'message' => '僅支援 POST 請求']);
    }

    if (empty($_FILES)) {
        respondAndExit(['result' => 'error', 'code' => 204, 'message' => '無文件上傳']);
    }

    // 2. 验证权限
    validateToken();

    $turnstileCheck = checkUploadTurnstilePermission($pdo, $config);
    if (!$turnstileCheck['allowed']) {
        respondAndExit(['result' => 'error', 'code' => 403, 'message' => $turnstileCheck['message']]);
    }

    // 3. 验证上传次数限制 (重用 api.php 逻辑)
    $maxUploadsPerDay = getConfigValue($pdo, 'max_uploads_per_day');
    $uploadCheck = isUploadAllowed($maxUploadsPerDay, $pdo);
    if ($uploadCheck !== true) {
        respondAndExit(['result' => 'error', 'code' => 429, 'message' => $uploadCheck]);
    }

    // 4. 处理音訊上傳 (一次只處理一個文件，或者循環處理)
    $results = [];
    foreach ($_FILES as $file) {
        // 获取设定的最大音訊大小，如果没有则预设为 100MB
        $audioMaxFileSizeMB = getConfigValue($pdo, 'max_audio_size');
        if (!$audioMaxFileSizeMB) {
            $audioMaxFileSizeMB = 100;
        }
        $audioMaxFileSize = $audioMaxFileSizeMB * 1024 * 1024; 

        if ($file['size'] > $audioMaxFileSize) {
            respondAndExit(['result' => 'error', 'code' => 413, 'message' => '音訊大小超過限制，最大允許 ' . formatSizeLimit($audioMaxFileSize)]);
        }

        $audioData = handleAudioUpload($file, $pdo, $_POST['title'] ?? '', $_POST['description'] ?? '', $_POST['password'] ?? '');
        $results[] = $audioData;
    }

    // 5. 返回响应
    respondAndExit([
        'result' => 'success',
        'code' => 200,
        'message' => '音訊上傳成功',
        'data' => count($results) === 1 ? $results[0] : $results
    ]);

} catch (Exception $e) {
    logMessage('音訊上傳錯誤: ' . $e->getMessage());
    respondAndExit(['result' => 'error', 'code' => 500, 'message' => '伺服器錯誤: ' . $e->getMessage()]);
}
