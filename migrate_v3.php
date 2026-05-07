<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $configs = [
        ['smtp_host', 'smtp.gmail.com', 'SMTP 伺服器'],
        ['smtp_port', '587', 'SMTP 端口'],
        ['smtp_user', '', 'SMTP 帳號'],
        ['smtp_pass', '', 'SMTP 密碼'],
        ['smtp_tls', 'true', '啟用 TLS'],
        ['admin_emails', '', '管理員收件信箱']
    ];
    
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO configs (`key`, value, description) VALUES (?, ?, ?)");
    foreach ($configs as $config) {
        $stmt->execute($config);
        echo "Ensured config: {$config[0]}\n";
    }
    
    echo "Migration V3 completed.\n";
} catch (Exception $e) {
    echo "Migration V3 failed: " . $e->getMessage() . "\n";
}
