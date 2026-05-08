<?php
/** @deprecated Use api.php instead */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'config/database.php';
require_once 'config/upload.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $config = Database::getConfig($pdo);
    
    // 檢查是否需要登入限制
    if ($config && 
        isset($config['login_restriction']) && 
        filter_var($config['login_restriction'], FILTER_VALIDATE_BOOLEAN) && 
        (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin'])) {
        respondAndExit(['result' => 'error', 'message' => '未登入，無權限']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'upload_file') {
            if (!isset($_FILES['file'])) {
                respondAndExit(['result' => 'error', 'message' => '未選擇檔案']);
            }
            
            handleFileUpload($_FILES['file'], $pdo, $config);
        } else {
            respondAndExit(['result' => 'error', 'message' => '未知操作']);
        }
    }
} catch (Exception $e) {
    respondAndExit(['result' => 'error', 'message' => $e->getMessage()]);
}

function handleFileUpload($file, $pdo, $config) {
    $storage = $config['storage'];
    $user_id = $_SESSION['user_id'] ?? NULL;
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($title)) {
        $title = pathinfo($file['name'], PATHINFO_FILENAME);
    }
    
    // 1. Validate file (more relaxed for documents)
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedDocs = ['zip', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'vsd', 'vsdx', 'epub', 'txt', 'md'];
    
    // Get MIME type more accurately
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($extension, $allowedDocs) && strpos($mimeType, 'application/') === false && strpos($mimeType, 'text/') === false) {
        respondAndExit(['result' => 'error', 'message' => '不支援的文件格式: ' . $extension]);
    }
    
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
        $result = StorageHelper::upload($storage, $config, $targetPath, $remotePath);
        $fileUrl = generateFileUrl($storage, $config, $remotePath, $result);
        
        if ($storage !== 'local') {
            if (file_exists($targetPath)) unlink($targetPath);
        }
        
        // 5. Save to database
        $stmt = $pdo->prepare("INSERT INTO images (url, path, storage, size, upload_ip, user_id, title, description, password, mime_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
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
            $mimeType
        ]);
        
        $dbId = $pdo->lastInsertId();
        
        // Return success with share link
        respondAndExit([
            'result' => 'success',
            'data' => [
                'id' => $dbId,
                'url' => $fileUrl,
                'share_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/view.php?id=' . $dbId
            ]
        ]);
        
    } catch (Exception $e) {
        respondAndExit(['result' => 'error', 'message' => '處理失敗: ' . $e->getMessage()]);
    }
}

if (!function_exists('respondAndExit')) {
    function respondAndExit($data) {
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
