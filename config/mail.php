<?php
/**
 * 888 BOX - 郵件發送服務核心模組
 * 支援原生 Resend REST API (HTTPS) 與 傳統 SMTP 協定
 */

require_once __DIR__ . '/database.php';

/**
 * 發送郵件
 *
 * @param string|array $to 收件者（單一信箱、信箱陣列或逗號分隔字串）
 * @param string $subject 郵件主旨
 * @param string $content 郵件內容
 * @param bool $isHtml 是否為 HTML 內容（預設 false）
 * @param array|null $config 系統設定（可選，為空時自動讀取 DB）
 * @return array ['success' => bool, 'message' => string, 'id' => string|null]
 */
function sendMail($to, $subject, $content, $isHtml = false, $config = null) {
    if ($config === null) {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        $config = Database::getConfig($pdo);
    }

    // 檢查郵件傳送是否啟用
    $mailEnabled = strtolower(trim((string)($config['mail_enabled'] ?? 'true')));
    if ($mailEnabled === 'false' || $mailEnabled === '0' || $mailEnabled === 'off') {
        return [
            'success' => false,
            'message' => '郵件傳送功能已停用（請在「系統設定 > 郵件通知」開啟啟用郵件傳送）'
        ];
    }

    // 整理收件者陣列
    $recipients = [];
    if (is_array($to)) {
        $recipients = array_values(array_filter(array_map('trim', $to)));
    } elseif (is_string($to)) {
        $recipients = array_values(array_filter(array_map('trim', explode(',', $to))));
    }

    if (empty($recipients)) {
        return ['success' => false, 'message' => '未指定有效的收件者信箱'];
    }

    // 判斷發信通道 (resend 或 smtp)
    $driver = strtolower(trim((string)($config['mail_driver'] ?? ($config['mail_service'] ?? ''))));
    if ($driver !== 'smtp' && $driver !== 'resend') {
        if (!empty($config['resend_api_key'])) {
            $driver = 'resend';
        } elseif (!empty($config['smtp_user']) || !empty($config['smtp_host'])) {
            $driver = 'smtp';
        } else {
            $driver = 'resend';
        }
    }

    if ($driver === 'resend') {
        return sendViaResend($recipients, $subject, $content, $isHtml, $config);
    } else {
        return sendViaSmtp($recipients, $subject, $content, $config);
    }
}

/**
 * 透過 Resend REST API (HTTPS POST https://api.resend.com/emails) 發送
 */
function sendViaResend(array $recipients, $subject, $content, $isHtml, array $config) {
    $apiKey = trim((string)($config['resend_api_key'] ?? ''));
    if (empty($apiKey)) {
        return [
            'success' => false,
            'message' => '尚未設定 Resend API Key，請至「系統設定」>「郵件通知」填寫 API Key'
        ];
    }

    // 發件人處理 (名稱 + 信箱)
    $fromName = trim((string)($config['mail_from_name'] ?? '888 BOX'));
    $fromEmail = trim((string)($config['mail_from_email'] ?? ($config['resend_from_email'] ?? 'onboarding@resend.dev')));
    if (empty($fromEmail)) {
        $fromEmail = 'onboarding@resend.dev';
    }

    // 若未包含 < >，組裝完整格式
    if (strpos($fromEmail, '<') === false && !empty($fromName)) {
        $from = "{$fromName} <{$fromEmail}>";
    } else {
        $from = $fromEmail;
    }

    $payload = [
        'from' => $from,
        'to' => $recipients,
        'subject' => $subject,
    ];

    // Reply-To 支援
    $replyTo = trim((string)($config['mail_reply_to'] ?? ''));
    if (!empty($replyTo)) {
        $payload['reply_to'] = $replyTo;
    }

    if ($isHtml) {
        $payload['html'] = $content;
        $payload['text'] = strip_tags($content);
    } else {
        $payload['text'] = $content;
        $payload['html'] = nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8'));
    }

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'User-Agent: 888box-Mailer/1.0'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 5
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'message' => 'Resend 連線失敗：' . $curlError];
    }

    $data = json_decode($response, true);
    if ($httpCode >= 200 && $httpCode < 300 && !empty($data['id'])) {
        return [
            'success' => true,
            'id' => $data['id'],
            'message' => 'Resend 發送成功'
        ];
    }

    $errorMsg = $data['message'] ?? ($data['error'] ?? '未知錯誤');
    return [
        'success' => false,
        'message' => "Resend API 錯誤 ({$httpCode}): {$errorMsg}",
        'raw' => $data
    ];
}

/**
 * 透過傳統 Python SMTP 橋接發送
 */
function sendViaSmtp(array $recipients, $subject, $content, array $config) {
    $mailData = [
        'smtp_host' => $config['smtp_host'] ?? '',
        'smtp_port' => $config['smtp_port'] ?? '',
        'smtp_user' => $config['smtp_user'] ?? '',
        'smtp_pass' => $config['smtp_pass'] ?? '',
        'smtp_tls' => ($config['smtp_tls'] ?? 'true') === 'true',
        'recipients' => implode(',', $recipients),
        'subject' => $subject,
        'body' => $content
    ];

    $scriptPath = dirname(__DIR__) . '/scripts/report_mail.py';
    if (!file_exists($scriptPath)) {
        return ['success' => false, 'message' => 'SMTP 發信腳本不存在: ' . $scriptPath];
    }

    $jsonArg = escapeshellarg(json_encode($mailData, JSON_UNESCAPED_UNICODE));
    $command = "python3 {$scriptPath} {$jsonArg} 2>&1";

    $output = [];
    $returnVar = 0;
    exec($command, $output, $returnVar);

    $result = json_decode(implode('', $output), true);
    if ($returnVar === 0 && $result && ($result['result'] ?? '') === 'success') {
        return ['success' => true, 'message' => 'SMTP 發送成功'];
    }

    $errorMsg = $result['message'] ?? implode(' ', $output);
    return ['success' => false, 'message' => 'SMTP 發信失敗：' . $errorMsg];
}
?>
