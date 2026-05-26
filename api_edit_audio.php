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
    $passwordAction = $_POST['password_action'] ?? 'keep';
    $newPassword = $_POST['password'] ?? '';
    
    if (empty($id)) {
        ob_end_clean();
        echo json_encode(['result' => 'error', 'message' => '缺少 ID 參數'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        $fields = [
            'title = ?',
            'description = ?'
        ];
        $params = [$title, $description];

        if ($passwordAction === 'set') {
            if (trim($newPassword) === '') {
                throw new Exception('請輸入新的存取密碼');
            }
            $fields[] = 'password = ?';
            $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
        } elseif ($passwordAction === 'clear') {
            $fields[] = 'password = NULL';
        } elseif ($passwordAction !== 'keep') {
            throw new Exception('無效的密碼操作');
        }

        $params[] = $id;
        $stmt = $pdo->prepare("UPDATE images SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);
        
        // 重建 音訊 RSS
        require_once 'config/audio_logic.php';
        rebuildAudioRSS($pdo, $config);
        
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
