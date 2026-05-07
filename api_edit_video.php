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

$db = Database::getInstance();
$pdo = $db->getConnection();
$config = Database::getConfig($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (empty($id)) {
        ob_end_clean();
        echo json_encode(['result' => 'error', 'message' => '缺少 ID 參數'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        // 更新資料庫
        $stmt = $pdo->prepare("UPDATE images SET title = ?, description = ? WHERE id = ?");
        $stmt->execute([$title, $description, $id]);
        
        // 重建 RSS (確保標題描述同步)
        require_once 'config/video_logic.php';
        rebuildVideoRSS($pdo, $config);
        
        ob_end_clean();
        echo json_encode(['result' => 'success', 'message' => '更新成功'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode(['result' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} else {
    ob_end_clean();
    echo json_encode(['result' => 'error', 'message' => '只支援 POST 請求'], JSON_UNESCAPED_UNICODE);
}
