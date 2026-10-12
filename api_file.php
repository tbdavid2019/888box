<?php
/** @deprecated Use api.php instead */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'config/database.php';
require_once 'config/upload.php';

if (!function_exists('respondAndExit')) {
    function respondAndExit($data) {
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('formatSizeLimit')) {
    function formatSizeLimit($bytes) {
        if ($bytes < 1024 * 1024) {
            return max(1, round($bytes / 1024)) . 'KB';
        }

        $mb = $bytes / (1024 * 1024);
        return $mb < 10 ? number_format($mb, 1) . 'MB' : number_format($mb, 0) . 'MB';
    }
}

if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        $config = Database::getConfig($pdo);
        
        // 檢查是否需要登入限制
        $loginRestriction = isset($config['login_restriction']) && filter_var($config['login_restriction'], FILTER_VALIDATE_BOOLEAN);
        if ($loginRestriction) {
            $user = resolveUploadUser($pdo);
            $authenticated = false;
            if ($user) {
                if (isset($user['status']) && (int)$user['status'] === 0) {
                    respondAndExit(['result' => 'error', 'message' => '帳號已被停用，無權限']);
                }
                $authenticated = true;
            }
            if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                $authenticated = true;
            }
            
            if (!$authenticated) {
                respondAndExit(['result' => 'error', 'message' => '未登入或 Token 無效，無權限']);
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'upload_file') {
                require_once 'config/turnstile.php';
                $turnstileCheck = checkUploadTurnstilePermission($pdo, $config);
                if (!$turnstileCheck['allowed']) {
                    respondAndExit(['result' => 'error', 'message' => $turnstileCheck['message']]);
                }
                handleFileUpload($_FILES['file'], $pdo, $config);
            } else {
                respondAndExit(['result' => 'error', 'message' => '未知操作']);
            }
        }
    } catch (Exception $e) {
        respondAndExit(['result' => 'error', 'message' => $e->getMessage()]);
    }
}

function handleFileUpload($file, $pdo, $config, $checkLimit = true) {
    $storage = $config['storage'];
    $user_id = $_SESSION['user_id'] ?? NULL;
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($title)) {
        $title = pathinfo($file['name'], PATHINFO_FILENAME);
    }

    // 驗證上傳次數限制 (支援同事 API Key / 無限制額度)
    if ($checkLimit) {
        $maxUploadsPerDay = (int)($config['max_uploads_per_day'] ?? 100);
        $uploadCheck = isUploadAllowed($maxUploadsPerDay, $pdo);
        if ($uploadCheck !== true) {
            respondAndExit(['result' => 'error', 'code' => 429, 'message' => $uploadCheck]);
        }
    }
    
    // 1. Validate file (content-type detection + strict document extension whitelist)
    require_once __DIR__ . '/config/detector.php';
    $detection = AssetDetector::detect($file['tmp_name'], $file['name']);

    if (AssetDetector::isDangerous($detection)) {
        respondAndExit(['result' => 'error', 'message' => '安全防護：禁止上傳可執行檔或腳本程式 (' . htmlspecialchars($detection['label'] ?? 'unknown', ENT_QUOTES, 'UTF-8') . ')']);
    }

    if (($detection['label'] ?? '') === 'empty' || ($file['size'] ?? 0) === 0) {
        respondAndExit(['result' => 'error', 'message' => '上傳的檔案為空檔案']);
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedDocs = ['zip', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'vsd', 'vsdx', 'epub', 'txt', 'md', 'csv', 'json', 'yaml', 'yml', '7z', 'tar', 'gz', 'bz2'];
    $dangerousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'inc', 'sh', 'bash', 'exe', 'bat', 'cmd', 'cgi', 'pl', 'py', 'js', 'html', 'htm', 'shtml', 'svg'];

    if (empty($extension) || !in_array($extension, $allowedDocs, true) || in_array($extension, $dangerousExtensions, true)) {
        respondAndExit(['result' => 'error', 'message' => '不支援的文件格式: ' . htmlspecialchars($extension, ENT_QUOTES, 'UTF-8')]);
    }

    $maxFileSize = 100 * 1024 * 1024;
    $stmt = $pdo->prepare("SELECT value FROM configs WHERE `key` = 'max_file_size'");
    $stmt->execute();
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $configuredMaxFileSize = (int)$row['value'];
        if ($configuredMaxFileSize > 0) {
            $maxFileSize = $configuredMaxFileSize;
        }
    }

    if ($file['size'] > $maxFileSize) {
        respondAndExit(['result' => 'error', 'message' => '文件大小超過限制，最大允許 ' . formatSizeLimit($maxFileSize)]);
    }
    
    // Get MIME type accurately from detector
    $mimeType = $detection['mime'] ?: 'application/octet-stream';
    
    // 2. Prepare paths
    $dateFolder = date('Y/m/d');
    $localPath = 'storage/file/' . $dateFolder;
    if (!is_dir($localPath) && !mkdir($localPath, 0755, true)) {
        respondAndExit(['result' => 'error', 'message' => '無法創建上傳目錄']);
    }
    
    $randomName = bin2hex(random_bytes(8));
    $fileName = $randomName . '.' . $extension;
    $targetPath = $localPath . '/' . $fileName;
    
    // 3. Move file
    $moveFunction = isset($_SESSION['use_rename']) ? 'rename' : 'move_uploaded_file';
    if (!$moveFunction($file['tmp_name'], $targetPath)) {
        respondAndExit(['result' => 'error', 'message' => '檔案儲存失敗']);
    }

    
    $fileSize = filesize($targetPath);
    $remotePath = $targetPath;
    
    try {
        // 4. Upload to remote storage if needed
        $result = StorageHelper::upload($storage, $config, $targetPath, $remotePath, [
            'content_type' => $mimeType,
            'content_disposition' => 'inline; filename="' . addcslashes($file['name'], '"\\') . '"'
        ]);
        $fileUrl = generateFileUrl($storage, $config, $remotePath, $result);
        $publicFileUrl = generatePublicFileUrl($storage, $config, $remotePath, $fileUrl, !empty($password));
        
        if ($storage !== 'local') {
            if (file_exists($targetPath)) unlink($targetPath);
        }
        
        // 5. Save to database
        $shareToken = generateShareToken();
        $stmt = $pdo->prepare("INSERT INTO images (url, path, storage, size, upload_ip, user_id, title, description, password, mime_type, is_video, is_file, share_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $fileUrl, 
            $remotePath, 
            $storage, 
            $fileSize, 
            getClientIp(), 
            $user_id, 
            $title, 
            $description, 
            !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : NULL,
            $mimeType,
            0,
            1,
            $shareToken
        ]);
        
        $dbId = $pdo->lastInsertId();
        $manageToken = issueAssetManageToken($pdo, $dbId);
        
        // Return success with share link
        respondAndExit([
            'result' => 'success',
            'data' => [
                'id' => $dbId,
                'url' => $publicFileUrl,
                'share_url' => buildAssetShareUrl($shareToken, $config),
                'manage_token' => $manageToken
            ]
        ]);
        
    } catch (Exception $e) {
        respondAndExit(['result' => 'error', 'message' => '處理失敗: ' . $e->getMessage()]);
    }
}
