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
        $mailData = [
            'smtp_host' => $config['smtp_host'] ?? '',
            'smtp_port' => $config['smtp_port'] ?? '',
            'smtp_user' => $config['smtp_user'] ?? '',
            'smtp_pass' => $config['smtp_pass'] ?? '',
            'smtp_tls' => ($config['smtp_tls'] ?? 'true') === 'true',
            'recipients' => $config['admin_emails'],
            'subject' => '【888box】資產檢舉通知 - ' . ($asset['title'] ?: $id),
            'body' => "管理員您好，\n\n系統收到一則資產檢舉：\n\n資產 ID: {$id}\n標題: " . ($asset['title'] ?: '未命名') . "\nURL: {$asset['url']}\n檢舉次數: " . ($asset['report_count'] + 1) . "\n\n請登入後台查看：https://" . $_SERVER['HTTP_HOST'] . "/admin/"
        ];
        
        $jsonArg = escapeshellarg(json_encode($mailData, JSON_UNESCAPED_UNICODE));
        $command = "python3 scripts/report_mail.py $jsonArg 2>&1";
        
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);
        
        $result = json_decode(implode('', $output), true);
        if ($returnVar !== 0 || ($result && $result['result'] === 'error')) {
            // 郵件發送失敗不影響檢舉計數，但我們可以記錄日誌
            error_log("SMTP Error: " . implode('', $output));
        }
    }
    
    echo json_encode(['result' => 'success'], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(['result' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
