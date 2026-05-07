<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    echo json_encode(['result' => 'error', 'message' => '未登入'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once 'config/database.php';
require_once 'config/storage.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $config = Database::getConfig($pdo);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = $_POST['id'] ?? '';
        $path = $_POST['path'] ?? '';
        
        if (empty($id)) {
            echo json_encode(['result' => 'error', 'message' => '缺少 ID'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 1. Delete from storage
        if (!empty($path)) {
            StorageHelper::delete($config['storage'], $config, $path);
        }
        
        // 2. Delete from database
        $stmt = $pdo->prepare("DELETE FROM images WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['result' => 'success'], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    echo json_encode(['result' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
