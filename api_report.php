<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'config/database.php';

$id = $_POST['id'] ?? '';
if (empty($id)) {
    echo json_encode(['result' => 'error', 'message' => '缺少 ID'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 簡單的防刷機制
if (isset($_SESSION['reported_' . $id])) {
    echo json_encode(['result' => 'success', 'message' => '您已經檢舉過了'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $config = Database::getConfig($pdo);
    
    // 1. 撈取資產資訊
    $stmt = $pdo->prepare("SELECT * FROM images WHERE id = ?");
    $stmt->execute([$id]);
    $asset = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$asset) {
        throw new Exception("找不到該資產");
    }
    
    // 2. 累加檢舉次數
    $pdo->prepare("UPDATE images SET report_count = report_count + 1 WHERE id = ?")->execute([$id]);
    $_SESSION['reported_' . $id] = true;
    
    // 3. 發送郵件通知管理員
    if (!empty($config['admin_emails'])) {
        require_once __DIR__ . '/config/mail.php';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $subject = '【888box】資產檢舉通知 - ' . ($asset['title'] ?: $id);
        $body = "管理員您好，\n\n系統收到一則資產檢舉：\n\n資產 ID: {$id}\n標題: " . ($asset['title'] ?: '未命名') . "\nURL: {$asset['url']}\n檢舉次數: " . ($asset['report_count'] + 1) . "\n\n請登入後台查看：https://{$host}/admin/";

        $mailRes = sendMail($config['admin_emails'], $subject, $body, false, $config);
        if (!$mailRes['success']) {
            error_log("Report Mail Error: " . ($mailRes['message'] ?? 'Unknown'));
        }
    }
    
    echo json_encode(['result' => 'success'], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(['result' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
