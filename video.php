<?php
/**
 * PixPro Video Upload Endpoint
 */
ob_start();
session_start();

require_once 'vendor/autoload.php';
require_once 'config/database.php';
require_once 'config/upload.php';
require_once 'config/video_logic.php';

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

function isUploadAllowed($maxUploadsPerDay) {
    if ($maxUploadsPerDay <= 0) return true;
    
    $uploadDir = 'i/.upload_limits/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    
    $clientIp = getClientIp();
    $currentDate = date('Y-m-d');
    $limitFile = $uploadDir . md5($clientIp) . '.json';
    
    $uploadData = file_exists($limitFile) 
        ? json_decode(file_get_contents($limitFile), true) ?: []
        : [];
    
    if (!isset($uploadData['date']) || $uploadData['date'] !== $currentDate) {
        $uploadData = ['date' => $currentDate, 'count' => 0, 'ip' => $clientIp];
    }
    
    if ($uploadData['count'] >= $maxUploadsPerDay) {
        return "影片上傳次數已達今日限制（{$maxUploadsPerDay}次），請明天再試";
    }
    
    $uploadData['count']++;
    $uploadData['last_upload'] = date('Y-m-d H:i:s');
    file_put_contents($limitFile, json_encode($uploadData, JSON_PRETTY_PRINT));
    
    return true;
}

function validateToken() {
    global $pdo, $config;
    
    $token = '';
    if (preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'] ?? '', $matches)) {
        $token = $matches[1];
    } else {
        $token = $_POST['token'] ?? '';
    }
    
    if (!empty($token)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE token = ?");
        $stmt->execute([$token]);
        if ($stmt->fetch()) return;
    }
    
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        return;
    }
    
    $loginRestriction = isset($config['login_restriction']) && filter_var($config['login_restriction'], FILTER_VALIDATE_BOOLEAN);
    if ($loginRestriction) {
        respondAndExit(['result' => 'error', 'code' => 403, 'message' => '登入保護已開啟，請提供有效的 Token 或先登入']);
    }

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
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
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

    // 3. 验证上传次数限制 (重用 api.php 逻辑)
    $maxUploadsPerDay = getConfigValue($pdo, 'max_uploads_per_day');
    $uploadCheck = isUploadAllowed($maxUploadsPerDay);
    if ($uploadCheck !== true) {
        respondAndExit(['result' => 'error', 'code' => 429, 'message' => $uploadCheck]);
    }

    // 自动数据库迁移：为 images 表加上 title 和 description 栏位 (供后台编辑使用)
    try {
        $pdo->exec("ALTER TABLE images ADD COLUMN title VARCHAR(255) DEFAULT ''");
        $pdo->exec("ALTER TABLE images ADD COLUMN description TEXT DEFAULT ''");
    } catch (PDOException $e) {
        // 如果列已存在会抛出异常，忽略即可
    }

    // 4. 处理影片上传 (一次只处理一个文件，或者循环处理)
    $results = [];
    foreach ($_FILES as $file) {
        // 获取设定的最大影片大小，如果没有则预设为 500MB
        $videoMaxFileSizeMB = getConfigValue($pdo, 'max_video_size');
        if (!$videoMaxFileSizeMB) {
            $videoMaxFileSizeMB = 500;
        }
        $videoMaxFileSize = $videoMaxFileSizeMB * 1024 * 1024; 

        if ($file['size'] > $videoMaxFileSize) {
            respondAndExit(['result' => 'error', 'code' => 413, 'message' => '影片大小超過限制，最大允許 ' . formatSizeLimit($videoMaxFileSize)]);
        }

        $videoData = handleVideoUpload($file, $pdo, $_POST['title'] ?? '', $_POST['description'] ?? '', $_POST['password'] ?? '');
        $results[] = $videoData;
    }

    // 5. 返回响应
    respondAndExit([
        'result' => 'success',
        'code' => 200,
        'message' => '影片上傳成功',
        'data' => count($results) === 1 ? $results[0] : $results
    ]);

} catch (Exception $e) {
    logMessage('影片上傳錯誤: ' . $e->getMessage());
    respondAndExit(['result' => 'error', 'code' => 500, 'message' => '伺服器錯誤: ' . $e->getMessage()]);
}
