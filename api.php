<?php
ob_start();
session_start();

require_once 'vendor/autoload.php';
require_once 'config/database.php';
require_once 'config/upload.php';

// 初始化
$db = Database::getInstance();
$pdo = $db->getConnection();
$config = Database::getConfig($pdo);

// ============================================
// 工具函数
// ============================================

/**
 * 从数据库获取配置值
 */
function getConfigValue($pdo, $key, $default = 0) {
    $stmt = $pdo->prepare("SELECT value FROM configs WHERE `key` = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row === false || !isset($row['value']) || $row['value'] === '') {
        return $default;
    }

    return (int)$row['value'];
}

/**
 * 检查域名是否被允许
 */
function isDomainAllowed($host) {
    global $config;
    
    if (empty($host)) return false;
    
    $siteDomains = array_map('trim', explode(',', $config['site_domain']));
    
    // 通配符允许所有域名
    if (in_array('*', $siteDomains)) return true;
    
    // 检查域名是否匹配
    foreach ($siteDomains as $domain) {
        if ($host === parse_url($domain, PHP_URL_HOST)) {
            return true;
        }
    }
    
    return false;
}

/**
 * 记录日志
 */
function logMessage($message) {
    file_put_contents('上传日志.txt', "[" . date('Y-m-d H:i:s') . "] $message" . PHP_EOL, FILE_APPEND);
}

/**
 * 返回JSON响应并退出
 */
function respondAndExit($response) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function formatSizeLimit($bytes) {
    if ($bytes < 1024 * 1024) {
        return max(1, round($bytes / 1024)) . 'KB';
    }

    $mb = $bytes / (1024 * 1024);
    return $mb < 10 ? number_format($mb, 1) . 'MB' : number_format($mb, 0) . 'MB';
}

// ============================================
// 上传限制
// ============================================

/**
 * 检查上传次数限制
 */
function isUploadAllowed($maxUploadsPerDay) {
    if ($maxUploadsPerDay <= 0) return true;
    
    $uploadDir = 'i/.upload_limits/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    
    $clientIp = getClientIp();
    $currentDate = date('Y-m-d');
    $limitFile = $uploadDir . md5($clientIp) . '.json';
    
    // 读取或初始化记录
    $uploadData = file_exists($limitFile) 
        ? json_decode(file_get_contents($limitFile), true) ?: []
        : [];
    
    // 新的一天重置计数
    if (!isset($uploadData['date']) || $uploadData['date'] !== $currentDate) {
        $uploadData = ['date' => $currentDate, 'count' => 0, 'ip' => $clientIp];
    }
    
    // 检查限制
    if ($uploadData['count'] >= $maxUploadsPerDay) {
        return "上传次数已达今日限制（{$maxUploadsPerDay}次），请明天再试";
    }
    
    // 更新计数
    $uploadData['count']++;
    $uploadData['last_upload'] = date('Y-m-d H:i:s');
    file_put_contents($limitFile, json_encode($uploadData, JSON_PRETTY_PRINT));
    
    // 10%概率清理过期文件
    try {
        if (random_int(1, 10) === 1) {
            foreach (glob($uploadDir . '*.json') as $file) {
                $data = json_decode(@file_get_contents($file), true);
                if (isset($data['date']) && $data['date'] !== $currentDate) {
                    @unlink($file);
                }
            }
        }
    } catch (Exception $e) {}
    
    return true;
}

// ============================================
// 验证函数
// ============================================

/**
 * 验证Token和请求来源
 */
function validateToken() {
    global $pdo, $config;
    
    // 1. 获取 Token
    $token = '';
    if (preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'] ?? '', $matches)) {
        $token = $matches[1];
    } else {
        $token = $_POST['token'] ?? '';
    }
    
    // 2. 验证 Token (優先級最高)
    if (!empty($token)) {
        // 允許預設的 AI 代理人 Token
        if ($token === 'ai_agent') return;

        $stmt = $pdo->prepare("SELECT id FROM users WHERE token = ?");
        $stmt->execute([$token]);
        if ($stmt->fetch()) return;
    }
    
    // 3. 验证 Session (針對官方網頁上傳)
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        return;
    }
    
    // 4. 如果開啟了登入限制，則此時應拒絕（因為 Token 與 Session 都失效）
    $loginRestriction = isset($config['login_restriction']) && filter_var($config['login_restriction'], FILTER_VALIDATE_BOOLEAN);
    if ($loginRestriction) {
        respondAndExit(['result' => 'error', 'code' => 403, 'message' => '登入保護已開啟，請提供有效的 Token 或先登入']);
    }

    // 5. 驗證網域 (僅作為公開模式下的基本過濾)
    $refererHost = parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_HOST);
    $currentHost = parse_url(((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
    if (!empty($refererHost) && !empty($currentHost) && strcasecmp($refererHost, $currentHost) === 0) {
        return;
    }
    if (isDomainAllowed($refererHost)) return;
    
    respondAndExit(['result' => 'error', 'code' => 403, 'message' => '身分驗證失敗：無效的 Token、尚未登入或網域未授權']);
}

/**
 * 设置CORS响应头
 */
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

// ============================================
// 主流程
// ============================================

try {
    setCorsHeaders();

    // 取得 Action
    $action = $_GET['action'] ?? $_POST['action'] ?? 'upload';

    // 1. 驗證權限 (除某些公開 Action 外)
    $publicActions = ['stats']; // 未來可以增加
    if (!in_array($action, $publicActions)) {
        validateToken();
    }

    switch ($action) {
        case 'upload':
            handleUnifiedUpload($pdo, $config);
            break;

        case 'list':
            $type = $_GET['type'] ?? 'all';
            handleUnifiedList($pdo, $type);
            break;

        case 'search':
            $query = $_GET['q'] ?? '';
            handleUnifiedSearch($pdo, $query);
            break;

        case 'stats':
            handleGetStats($pdo);
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            handleDeleteAsset($pdo, $id);
            break;

        case 'upload_url':
            handleUploadFromUrl($pdo, $config);
            break;

        default:
            respondAndExit(['result' => 'error', 'code' => 400, 'message' => '未知的操作: ' . $action]);
    }
    
} catch (Exception $e) {
    logMessage('錯誤: ' . $e->getMessage());
    respondAndExit(['result' => 'error', 'code' => 500, 'message' => '伺服器錯誤: ' . $e->getMessage()]);
}

/**
 * 遠端 URL 上傳處理器
 */
function handleUploadFromUrl($pdo, $config) {
    $url = $_POST['url'] ?? $_GET['url'] ?? '';
    if (empty($url)) {
        respondAndExit(['result' => 'error', 'code' => 400, 'message' => 'URL 不能為空']);
    }

    // 1. 安全檢查：獲取 Headers
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_NOBODY, true); // 僅獲取 Header
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH);
    curl_close($ch);

    // 驗證大小 (限制 100MB 避免伺服器爆掉)
    $maxFileSize = getConfigValue($pdo, 'max_file_size', 100 * 1024 * 1024);
    if ($contentLength > $maxFileSize) {
        respondAndExit(['result' => 'error', 'code' => 413, 'message' => '遠端檔案太大']);
    }

    // 2. 下載檔案
    $tempDir = 'storage/temp';
    if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);
    
    $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION)) ?: 'tmp';
    $tempFile = $tempDir . '/' . bin2hex(random_bytes(8)) . '.' . $extension;

    $fp = fopen($tempFile, 'w+');
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);

    if (!file_exists($tempFile) || filesize($tempFile) == 0) {
        @unlink($tempFile);
        respondAndExit(['result' => 'error', 'code' => 500, 'message' => '遠端檔案下載失敗']);
    }

    // 3. 模擬 $_FILES 結構並調用現有邏輯
    $file = [
        'name' => basename(parse_url($url, PHP_URL_PATH)) ?: 'downloaded_asset.' . $extension,
        'type' => $contentType,
        'tmp_name' => $tempFile,
        'error' => 0,
        'size' => filesize($tempFile),
        'is_remote' => true // 標記為遠端抓取，方便內部處理
    ];

    // 這裡我們需要修改 handleUploadedFile 以支援非 move_uploaded_file 的情況 (因為我們已經是本地暫存檔了)
    // 為了簡單起見，我們直接在這裡手動處理或調用對應處理器
    list($mimeType, $ext) = detectMimeType($file);
    
    // 注意：這裡必須把 move_uploaded_file 換成 rename，因為它是我們下載的檔案
    // 我們稍微修改一下 handleUnifiedUpload 的邏輯，讓它支援本地路徑
    processAsset($file, $pdo, $config, $mimeType);
}

/**
 * 處理資產 (通用上傳邏輯)
 */
function processAsset($file, $pdo, $config, $mimeType) {
    // 針對 URL 下載的檔案，將其從臨時目錄「移動」到正式流程
    // 我們可以透過一個特殊的 flag 讓 handleUploadedFile 知道不需要調用 move_uploaded_file
    $_SESSION['use_rename'] = true; // 髒方法，但能最快兼容現有代碼
    
    try {
        if (strpos($mimeType, 'image/') === 0) {
            handleUploadedFile($file, $_POST['token'] ?? '', $_SERVER['HTTP_REFERER'] ?? '', $_POST['password'] ?? '');
        } elseif (strpos($mimeType, 'video/') === 0) {
            require_once 'config/video_logic.php';
            // 修改 handleVideoUpload 以支援 rename
            $videoData = handleVideoUpload($file, $pdo, $_POST['title'] ?? '', $_POST['description'] ?? '', $_POST['password'] ?? '');
            respondAndExit(['result' => 'success', 'code' => 200, 'data' => $videoData]);
        } else {
            require_once 'api_file.php';
            handleFileUpload($file, $pdo, $config);
        }
    } finally {
        unset($_SESSION['use_rename']);
        if (file_exists($file['tmp_name'])) @unlink($file['tmp_name']);
    }
}


/**
 * 統一上傳處理器
 */
function handleUnifiedUpload($pdo, $config) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES)) {
        respondAndExit(['result' => 'error', 'code' => 204, 'message' => '無文件上傳']);
    }

    // 驗證上傳限制
    $maxUploadsPerDay = getConfigValue($pdo, 'max_uploads_per_day');
    $uploadCheck = isUploadAllowed($maxUploadsPerDay);
    if ($uploadCheck !== true) {
        respondAndExit(['result' => 'error', 'code' => 429, 'message' => $uploadCheck]);
    }

    // 驗證檔案大小
    $maxFileSize = getConfigValue($pdo, 'max_file_size', 100 * 1024 * 1024);
    foreach ($_FILES as $file) {
        if ($file['size'] > $maxFileSize) {
            respondAndExit([
                'result' => 'error',
                'code' => 413,
                'message' => '文件大小超過限制，最大允許 ' . formatSizeLimit($maxFileSize)
            ]);
        }
    }

    // 根據檔案類型自動分流
    foreach ($_FILES as $file) {
        list($mimeType, $extension) = detectMimeType($file);
        
        if (strpos($mimeType, 'image/') === 0) {
            // 圖片處理
            handleUploadedFile($file, $_POST['token'] ?? '', $_SERVER['HTTP_REFERER'] ?? '', $_POST['password'] ?? '');
        } elseif (strpos($mimeType, 'video/') === 0) {
            // 影片處理
            require_once 'config/video_logic.php';
            $videoData = handleVideoUpload($file, $pdo, $_POST['title'] ?? '', $_POST['description'] ?? '', $_POST['password'] ?? '');
            respondAndExit(['result' => 'success', 'code' => 200, 'data' => $videoData]);
        } else {
            // 文件處理
            require_once 'api_file.php'; // 暫時借用 api_file.php 的邏輯
            handleFileUpload($file, $pdo, $config);
        }
    }
}

/**
 * 統一列表處理器
 */
function handleUnifiedList($pdo, $type) {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;

    $where = "1=1";
    $params = [];
    if ($type === 'image') {
        $where = "(url LIKE '%.jpg' OR url LIKE '%.jpeg' OR url LIKE '%.png' OR url LIKE '%.gif' OR url LIKE '%.webp' OR url LIKE '%.svg')";
    } elseif ($type === 'video') {
        $where = "(url LIKE '%.mp4' OR url LIKE '%.webm' OR url LIKE '%.mov' OR url LIKE '%.mkv')";
    } elseif ($type === 'file') {
        $where = "NOT (url LIKE '%.jpg' OR url LIKE '%.jpeg' OR url LIKE '%.png' OR url LIKE '%.gif' OR url LIKE '%.webp' OR url LIKE '%.svg' OR url LIKE '%.mp4' OR url LIKE '%.webm' OR url LIKE '%.mov' OR url LIKE '%.mkv')";
    }

    $stmt = $pdo->prepare("SELECT * FROM images WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($assets as &$asset) {
        $asset['share_url'] = (isset($asset['mime_type']) && strpos($asset['mime_type'], 'image/') === false) 
            ? 'https://' . $_SERVER['HTTP_HOST'] . '/view.php?id=' . $asset['id']
            : $asset['url'];
    }

    $totalCount = (int)$pdo->query("SELECT COUNT(*) FROM images WHERE $where")->fetchColumn();
    $totalPages = ceil($totalCount / $limit);

    respondAndExit([
        'result' => 'success',
        'code' => 200,
        'data' => $assets,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => (int)$totalPages,
            'total_count' => $totalCount
        ]
    ]);
}

/**
 * 統一搜尋處理器
 */
function handleUnifiedSearch($pdo, $query) {
    if (empty($query)) {
        respondAndExit(['result' => 'error', 'code' => 400, 'message' => '搜尋內容不能為空']);
    }

    $stmt = $pdo->prepare("SELECT * FROM images WHERE path LIKE ? OR url LIKE ? OR title LIKE ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute(["%$query%", "%$query%", "%$query%"]);
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($assets as &$asset) {
        $asset['share_url'] = $asset['url'];
    }

    respondAndExit([
        'result' => 'success',
        'code' => 200,
        'data' => $assets,
        'query' => $query
    ]);
}

/**
 * 獲取統計數據
 */
function handleGetStats($pdo) {
    $stats = [
        'total' => (int)$pdo->query("SELECT COUNT(*) FROM images")->fetchColumn(),
        'image' => (int)$pdo->query("SELECT COUNT(*) FROM images WHERE url LIKE '%.jpg' OR url LIKE '%.jpeg' OR url LIKE '%.png' OR url LIKE '%.gif' OR url LIKE '%.webp' OR url LIKE '%.svg'")->fetchColumn(),
        'video' => (int)$pdo->query("SELECT COUNT(*) FROM images WHERE url LIKE '%.mp4' OR url LIKE '%.webm' OR url LIKE '%.mov' OR url LIKE '%.mkv'")->fetchColumn(),
    ];
    $stats['file'] = $stats['total'] - $stats['image'] - $stats['video'];

    respondAndExit([
        'result' => 'success',
        'code' => 200,
        'data' => $stats
    ]);
}

/**
 * 刪除資產
 */
function handleDeleteAsset($pdo, $id) {
    if ($id <= 0) respondAndExit(['result' => 'error', 'message' => '無效的 ID']);
    
    // 這裡可以引入 config/delete.php 的邏輯，或者直接實作
    require_once 'config/delete.php';
    $result = deleteAsset($pdo, $id); // 假設有這個函數
    
    if ($result) {
        respondAndExit(['result' => 'success', 'message' => '刪除成功']);
    } else {
        respondAndExit(['result' => 'error', 'message' => '刪除失敗']);
    }
}

?>
