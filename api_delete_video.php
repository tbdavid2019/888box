<?php
ob_start();
session_start();
error_reporting(0);
ini_set('display_errors', '0');

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['result' => 'error', 'message' => '未登入，無權限'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once 'vendor/autoload.php';
require_once 'config/database.php';
require_once 'config/storage.php';

$db = Database::getInstance();
$pdo = $db->getConnection();
$config = Database::getConfig($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $path = $_POST['path'] ?? '';
    
    if (empty($id) || empty($path)) {
        ob_end_clean();
        echo json_encode(['result' => 'error', 'message' => '參數錯誤'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        // 取得 storage 類型
        $stmt = $pdo->prepare("SELECT storage FROM images WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new Exception("找不到該影片記錄");
        }
        $storage = $row['storage'];
        
        // 刪除實體檔案
        StorageHelper::delete($storage, $config, $path);
        
        // 嘗試刪除縮圖
        $thumbPath = preg_replace('/\.[^.]+$/', '_thumb.jpg', $path);
        try {
            StorageHelper::delete($storage, $config, $thumbPath);
        } catch(Exception $e) {
            // 縮圖可能不存在，忽略錯誤
        }
        
        // 從資料庫刪除
        $stmt = $pdo->prepare("DELETE FROM images WHERE id = ?");
        $stmt->execute([$id]);
        
        // 重建 RSS (撈取所有剩下的影片)
        require_once 'config/video_logic.php';
        rebuildVideoRSS($pdo, $config);
        
        ob_end_clean();
        echo json_encode(['result' => 'success', 'message' => '刪除成功'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode(['result' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} else {
    ob_end_clean();
    echo json_encode(['result' => 'error', 'message' => '只支援 POST 請求'], JSON_UNESCAPED_UNICODE);
}
