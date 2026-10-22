<?php
session_start();
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    require 'login.php';
    exit;
}

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    header('HTTP/1.1 403 Forbidden');
    exit('禁止直接存取');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/rss.php';
$db = Database::getInstance();
$pdo = $db->getConnection();
$demoMode = ($_ENV['DEMO_MODE'] ?? 'false') === 'true';

// 權限檢查：僅啟用狀態之管理員可存取系統設定
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
if (!$currentUserId) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => '請先登入']));
}
$stmtRole = $pdo->prepare("SELECT role, status FROM users WHERE id = ?");
$stmtRole->execute([$currentUserId]);
$currentUserRecord = $stmtRole->fetch(PDO::FETCH_ASSOC);
if (!$currentUserRecord || (int)$currentUserRecord['status'] === 0 || $currentUserRecord['role'] !== 'admin') {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => '權限不足：僅系統管理員可修改或查看系統設定']));
}
$_SESSION['role'] = 'admin';

// 处理AJAX请求
if (!empty($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        switch ($_POST['action']) {
            case 'test_storage':
                if (empty($_POST['storage_type'])) throw new Exception('未指定儲存類型');
                
                require_once __DIR__ . '/../config/storage.php';
                $config = Database::getConfig($pdo);
                
                try {
                    StorageHelper::testConnection($_POST['storage_type'], $config);
                    echo json_encode(['success' => true, 'message' => '儲存連線測試成功'], JSON_UNESCAPED_UNICODE);
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false, 
                        'message' => '儲存連線失敗，請檢查設定',
                        'error' => $e->getMessage()
                    ], JSON_UNESCAPED_UNICODE);
                }
                break;
                
            case 'check_update':
                $currentVersion = json_decode(file_get_contents(__DIR__ . '/../package.json'), true)['version'] ?? '2.0';
                
                $context = stream_context_create([
                    'http' => [
                        'header' => "User-Agent: PHP\r\nAccept: application/vnd.github.v3+json",
                        'timeout' => 10
                    ]
                ]);
                
                $response = @file_get_contents('https://api.github.com/repos/tbdavid2019/888box/releases/latest', false, $context);
                if (!$response) throw new Exception('無法連線到 GitHub API');
                
                $latestVersion = ltrim(json_decode($response, true)['tag_name'] ?? '', 'v');
                $compareResult = version_compare($currentVersion, $latestVersion);
                
                $isDev = $compareResult > 0;
                $hasUpdate = $compareResult < 0;
                
                echo json_encode([
                    'success' => true,
                    'current' => $currentVersion,
                    'latest' => $latestVersion,
                    'hasUpdate' => $hasUpdate,
                    'isDev' => $isDev,
                    'url' => $isDev ? 'https://github.com/tbdavid2019/888box/tree/dev' : 'https://github.com/tbdavid2019/888box/releases/latest',
                    'message' => $isDev ? "您正在使用測試版本 V{$currentVersion}" 
                        : ($hasUpdate ? "發現新版本 V{$latestVersion}" : '已是最新版本')
                ], JSON_UNESCAPED_UNICODE);
                break;
                
            case 'optimize_db':
                $dbPath = dirname(__DIR__) . '/storage/database.db';
                $sizeBefore = filesize($dbPath);
                
                $pdo->exec('VACUUM');
                $pdo->exec('ANALYZE');
                
                clearstatcache();
                $sizeAfter = filesize($dbPath);
                
                echo json_encode([
                    'success' => true,
                    'message' => '資料庫最佳化完成',
                    'saved' => round(($sizeBefore - $sizeAfter) / 1024 / 1024, 2)
                ], JSON_UNESCAPED_UNICODE);
                break;

            case 'test_turnstile':
                require_once __DIR__ . '/../config/turnstile.php';
                $secretKey = $_POST['secret_key'] ?? '';
                if (empty($secretKey)) {
                    $config = Database::getConfig($pdo);
                    $secretKey = $config['turnstile_secret_key'] ?? '';
                }
                $testRes = testTurnstileSecretKey((string)$secretKey);
                echo json_encode($testRes, JSON_UNESCAPED_UNICODE);
                break;

            case 'test_mail':
                require_once __DIR__ . '/../config/mail.php';
                $customConfig = array_merge(Database::getConfig($pdo), $_POST);
                $driver = strtolower(trim((string)($customConfig['mail_driver'] ?? 'resend')));
                $recipient = trim((string)($_POST['test_recipient'] ?? ''));
                if (empty($recipient)) {
                    $recipient = trim((string)($customConfig['admin_emails'] ?? ''));
                }
                if (empty($recipient)) {
                    throw new Exception('請填寫測試收件信箱或設定管理員收件信箱');
                }

                $driverName = ($driver === 'resend') ? 'Resend REST API' : 'SMTP 伺服器';
                $subject = "【888 BOX】發信測試成功 ({$driverName})";
                $body = "您好！\n\n這是一封來自 888 BOX 系統設定的測試郵件。\n\n發信通道: {$driverName}\n測試時間: " . date('Y-m-d H:i:s') . "\n\n如果您收到此郵件，代表系統郵件發送服務已成功連線並運作正常！";

                $testResult = sendMail($recipient, $subject, $body, false, $customConfig);
                if ($testResult['success']) {
                    echo json_encode([
                        'success' => true,
                        'message' => "測試郵件已成功寄出！請至 {$recipient} 查收" . (!empty($testResult['id']) ? " (ID: {$testResult['id']})" : '')
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => $testResult['message']
                    ], JSON_UNESCAPED_UNICODE);
                }
                break;
                
            default:
                throw new Exception('未知操作');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// 处理POST请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($demoMode) {
        exit(json_encode(['success' => false, 'message' => '示範模式下禁止修改設定']));
    }
    
    try {
        $pdo->beginTransaction();
        
        // 更新密碼
        if (!empty($_POST['new_password'])) {
            if (empty($_POST['confirm_password'])) throw new Exception('請確認新密碼');
            if ($_POST['new_password'] !== $_POST['confirm_password']) throw new Exception('兩次輸入的密碼不一致');
            
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([password_hash($_POST['new_password'], PASSWORD_DEFAULT), $_SESSION['user_id']]);
        }

        // 更新Token
        if (!empty($_POST['token'])) {
            $stmt = $pdo->prepare("UPDATE users SET token = ? WHERE id = ?");
            $stmt->execute([$_POST['token'], $_SESSION['user_id']]);
        }
        
        // 同步存储配置
        if (!empty($_POST['storage'])) {
            $storageConfig = Database::getStorageConfig($_POST['storage']);
            if ($storageConfig) {
                $existingKeys = array_column($pdo->query("SELECT `key` FROM configs")->fetchAll(PDO::FETCH_ASSOC), 'key');
                $stmt = $pdo->prepare("INSERT INTO configs (`key`, value, description) VALUES (?, ?, ?) ON CONFLICT(`key`) DO UPDATE SET description = excluded.description");
                foreach ($storageConfig['configs'] as $config) {
                    if (!in_array($config['key'], $existingKeys)) {
                        $stmt->execute([$config['key'], $config['default'], $config['description'] ?? '']);
                    } else {
                        $stmt = $pdo->prepare("UPDATE configs SET description = ? WHERE `key` = ?");
                        $stmt->execute([$config['description'] ?? '', $config['key']]);
                    }
                }
            }
        }
        
        // 更新或寫入配置
        $stmt = $pdo->prepare("INSERT INTO configs (`key`, value) VALUES (?, ?) ON CONFLICT(`key`) DO UPDATE SET value = excluded.value");

        if (($_POST['rss_token_enabled'] ?? 'false') === 'true' && empty($_POST['rss_token'])) {
            $_POST['rss_token'] = generateRssToken();
        }

        foreach ($_POST as $key => $value) {
            if (!in_array($key, ['submit', 'token', 'new_password', 'confirm_password', 'test_recipient', 'test_email_recipient'])) {
                // 自動清理誤存的 textarea style 垃圾字串
                if ($key === 'custom_tracking_code') {
                    $cleanVal = trim((string)$value);
                    if (strpos($cleanVal, 'style="width:') === 0 || strpos($cleanVal, '" style="') === 0 || (strpos($cleanVal, 'box-sizing: border-box') !== false && strpos($cleanVal, '<script') === false)) {
                        $value = '';
                    }
                }

                // 自动处理域名和endpoint
                if (!empty($value) && (strpos($key, '_cdn_domain') !== false || strpos($key, '_endpoint') !== false)) {
                    $value = rtrim($value, '/');
                    // 自动添加 https:// 协议头
                    if (!preg_match('/^https?:\/\//', $value)) {
                        $value = 'https://' . $value;
                    }
                }
                $stmt->execute([$key, $value]);
            }
        }

        cleanupLegacyPublicRssFiles();
        
        $pdo->commit();
        exit(json_encode(['success' => true, 'message' => '設定已更新']));
    } catch (Exception $e) {
        $pdo->rollback();
        exit(json_encode(['success' => false, 'message' => '更新失敗：' . $e->getMessage()]));
    }
}

// 获取配置
$configs = array_column($pdo->query("SELECT * FROM configs ORDER BY id")->fetchAll(PDO::FETCH_ASSOC), null, 'key');
$storageConfigs = json_decode(file_get_contents(__DIR__ . '/../config/configs.json'), true);
$stmt = $pdo->prepare("SELECT token FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userToken = $stmt->fetch(PDO::FETCH_ASSOC)['token'] ?? '';
if (empty($userToken)) {
    $userToken = bin2hex(random_bytes(16));
}
$rssToken = $configs['rss_token']['value'] ?? '';
if (empty($rssToken)) {
    $rssToken = generateRssToken();
}
$rssTokenEnabled = ($configs['rss_token_enabled']['value'] ?? 'false') === 'true';
$videoRssPreviewUrl = buildRssUrl('video', [
    'site_domain' => $configs['site_domain']['value'] ?? '',
    'rss_token_enabled' => $rssTokenEnabled ? 'true' : 'false',
    'rss_token' => $rssToken,
], true);
$audioRssPreviewUrl = buildRssUrl('audio', [
    'site_domain' => $configs['site_domain']['value'] ?? '',
    'rss_token_enabled' => $rssTokenEnabled ? 'true' : 'false',
    'rss_token' => $rssToken,
], true);

// 渲染表單欄位輔助函式
function renderFields($fields, $configs) {
    $halfWidthCount = 0;
    foreach($fields as $key => $field): 
        $isHalfWidth = $field['half_width'] ?? false;
        if ($isHalfWidth && $halfWidthCount % 2 === 0) echo '<div class="form-row">';
        
        $value = $key === 'max_file_size' ? ($configs[$key]['value'] / (1024 * 1024)) : ($configs[$key]['value'] ?? '');
        ?>
        <div class="<?= $isHalfWidth ? 'form-group form-group-half' : 'form-group' ?>">
            <label for="<?= htmlspecialchars($key) ?>">
                <?= htmlspecialchars($field['name'] ?? $field['label'] ?? '') ?>
                <?php if (!empty($field['description'])): ?>
                    <span class="label-description"><?= htmlspecialchars($field['description']) ?></span>
                <?php endif; ?>
            </label>
            <?php if (($field['type'] ?? '') === 'radio'): ?>
                <div class="radio-group">
                    <?php foreach($field['options'] as $val => $label): ?>
                        <label>
                            <input type="radio" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars((string)$val) ?>" 
                                   <?= ($configs[$key]['value'] ?? '') === (string)$val ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php elseif (($field['type'] ?? '') === 'password'): ?>
                <div class="password-wrapper">
                    <input type="password" name="<?= htmlspecialchars($key) ?>" id="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars((string)$value) ?>"
                           placeholder="<?= htmlspecialchars($field['placeholder'] ?? '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="new-password">
                    <span class="toggle-password" title="顯示/隱藏密碼">
                        <i data-lucide="eye" class="icon"></i>
                    </span>
                </div>
            <?php elseif (($field['type'] ?? '') === 'select'): ?>
                <select name="<?= htmlspecialchars($key) ?>" id="<?= htmlspecialchars($key) ?>"
                        style="width: 100%; box-sizing: border-box; padding: 9px 36px 9px 12px; border-radius: 8px; border: 1px solid rgba(122, 162, 247, 0.25); background-color: rgba(22, 26, 43, 0.95); color: #c0caf5; font-size: 14px; outline: none; cursor: pointer; appearance: none; -webkit-appearance: none; background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%237aa2f7' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E&quot;); background-repeat: no-repeat; background-position: right 12px center; background-size: 16px;">
                    <?php 
                    $curVal = (string)($configs[$key]['value'] ?? ($field['default'] ?? ''));
                    foreach($field['options'] as $val => $label): 
                    ?>
                        <option value="<?= htmlspecialchars((string)$val) ?>" <?= $curVal === (string)$val ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php elseif (($field['type'] ?? '') === 'textarea'): ?>
                <textarea name="<?= htmlspecialchars($key) ?>" id="<?= htmlspecialchars($key) ?>" rows="<?= $field['rows'] ?? 5 ?>" 
                          placeholder="<?= htmlspecialchars($field['placeholder'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                          style="width: 100%; box-sizing: border-box; padding: 10px; border-radius: 8px; border: 1px solid rgba(122, 162, 247, 0.25); background: rgba(26, 27, 38, 0.85); color: #c0caf5; font-size: 13px; font-family: monospace; outline: none; resize: vertical;"><?= htmlspecialchars((string)$value) ?></textarea>
            <?php else: ?>
                <input type="<?= htmlspecialchars($field['type'] ?? 'text') ?>" name="<?= htmlspecialchars($key) ?>" id="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars((string)$value) ?>"
                       <?= isset($field['min']) ? "min=\"{$field['min']}\"" : '' ?>
                       <?= isset($field['max']) ? "max=\"{$field['max']}\"" : '' ?>
                       placeholder="<?= htmlspecialchars($field['placeholder'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <?php endif; ?>
        </div>
        <?php
        if ($isHalfWidth && ++$halfWidthCount % 2 === 0) echo '</div>';
    endforeach;
    if ($isHalfWidth && $halfWidthCount % 2 !== 0) echo '</div>';
}

$generalSettings = [
    'max_uploads_per_day' => [
        'label' => '每日限制',
        'type' => 'number',
        'min' => 1,
        'placeholder' => '建議設定為 100',
        'description' => '每日上傳次數限制（管理員不受此限）',
        'half_width' => true
    ],
    'per_page' => [
        'label' => '單頁數量',
        'type' => 'number',
        'min' => 1,
        'max' => 100,
        'placeholder' => '建議設定為 20',
        'description' => '後台管理單頁顯示資產數量',
        'half_width' => true
    ],
    'max_file_size' => [
        'label' => '圖片大小上限',
        'type' => 'number',
        'min' => 1,
        'placeholder' => '建議設定為 10',
        'description' => '單一圖片大小限制（MB）',
        'half_width' => true
    ],
    'max_video_size' => [
        'label' => '影片大小上限',
        'type' => 'number',
        'min' => 1,
        'placeholder' => '建議設定為 500',
        'description' => '單一影片大小限制（MB）',
        'half_width' => true
    ],
    'max_audio_size' => [
        'label' => '音訊大小上限',
        'type' => 'number',
        'min' => 1,
        'placeholder' => '建議設定為 100',
        'description' => '單一音訊大小限制（MB）',
        'half_width' => true
    ],
    'url_prefix' => [
        'label' => '圖片代理 (CDN / Proxy)',
        'type' => 'text',
        'placeholder' => '例如：https://i1.wp.com/（留空則不使用）',
        'description' => '圖片 URL 代理位址，可用於 CDN 加速或圖片處理服務',
        'half_width' => true
    ],
    'site_domain' => [
        'label' => '網站授權網域',
        'type' => 'text',
        'placeholder' => '例如：https://example.com,http://localhost',
        'description' => '用於驗證上傳來源，多個網域請用英文逗號分隔，支援萬用字元 "*"'
    ],
    'output_format' => [
        'label' => '預設輸出格式',
        'type' => 'radio',
        'options' => [
            'original' => '原始格式',
            'webp' => 'WebP',
            'avif' => 'AVIF'
        ],
        'description' => '公開上傳後的預設圖片轉換格式'
    ],
];

$appearanceSettings = [
    'active_theme' => [
        'label' => '全站配色主題',
        'type' => 'radio',
        'options' => [
            'default' => '2MD：藏紅花與暖紙',
            'middle_east_dart' => '2MD：藏紅花與暖紙（相容舊設定）',
            'kanagawa_wave' => '2MD：藏紅花與暖紙（相容舊設定）'
        ],
        'description' => '全站統一使用高對比暖紙、藏藍與藏紅花黃配色'
    ],
    'custom_tracking_code' => [
        'label' => '全站自訂追蹤碼 (如 Google Analytics 或 GTM)',
        'type' => 'textarea',
        'rows' => 5,
        'placeholder' => '在此貼入完整的 Google Analytics (GA4) 或 GTM 追蹤程式碼（含 <script> 標籤）',
        'description' => '這段程式碼將會被自動注入至所有公開頁面（包含首頁、圖片/影片/文件/音訊上傳中心與資產分享頁面）的 <head> 區塊中。'
    ],
];

$securitySettings = [
    'login_restriction' => [
        'label' => '啟用登入保護',
        'type' => 'radio',
        'options' => ['true' => '啟用', 'false' => '關閉'],
        'description' => '關閉後允許訪客公開存取前台各類上傳中心',
        'half_width' => true
    ],
    'turnstile_enabled' => [
        'label' => '啟用 Cloudflare Turnstile',
        'type' => 'radio',
        'options' => ['true' => '啟用', 'false' => '停用（預設 / Fallback）'],
        'description' => '未啟用或未填寫金鑰時完全不載入（支援 .env TURNSTILE_ENABLED 覆蓋）',
        'half_width' => true
    ],
    'turnstile_protect_login' => [
        'label' => '保護後台登入',
        'type' => 'radio',
        'options' => ['true' => '是', 'false' => '否'],
        'description' => '為後台登入表單啟用 Turnstile 驗證，防止暴力破解',
        'half_width' => true
    ],
    'turnstile_protect_upload' => [
        'label' => '保護公開上傳',
        'type' => 'radio',
        'options' => ['true' => '是', 'false' => '否'],
        'description' => '為公開網頁上傳啟用驗證（合法 Token 工具如 ShareX、PicGo、MCP 自動放行）',
        'half_width' => true
    ],
    'turnstile_site_key' => [
        'label' => 'Turnstile Site Key (公鑰)',
        'type' => 'text',
        'placeholder' => '0x4AAAAAA...',
        'description' => 'Cloudflare Turnstile 儀表板公鑰（支援 .env TURNSTILE_SITE_KEY 覆蓋）',
        'half_width' => true
    ],
    'turnstile_secret_key' => [
        'label' => 'Turnstile Secret Key (密鑰)',
        'type' => 'password',
        'placeholder' => '0x4AAAAAA...',
        'description' => 'Cloudflare Turnstile 儀表板密鑰（支援 .env TURNSTILE_SECRET_KEY 覆蓋）',
        'half_width' => true
    ],
];

if (empty($configs['mail_from_email']['value']) && !empty($configs['resend_from_email']['value'])) {
    $configs['mail_from_email'] = ['value' => $configs['resend_from_email']['value']];
}
if (empty($configs['mail_from_name']['value'])) {
    $configs['mail_from_name'] = ['value' => '888 BOX'];
}

$mailGeneralSettings = [
    'mail_enabled' => [
        'label' => '啟用郵件傳送',
        'type' => 'select',
        'options' => [
            'false' => '關閉',
            'true' => '開啟'
        ],
        'default' => 'true',
        'half_width' => true
    ],
    'mail_driver' => [
        'label' => '郵件服務',
        'type' => 'select',
        'options' => [
            'resend' => 'Resend',
            'smtp' => 'SMTP'
        ],
        'default' => 'resend',
        'half_width' => true
    ],
    'mail_from_name' => [
        'label' => '發件人名稱',
        'type' => 'text',
        'placeholder' => 'InsightFlare',
        'half_width' => true
    ],
    'mail_from_email' => [
        'label' => '發件人信箱',
        'type' => 'text',
        'placeholder' => 'notify@aiurl.tw',
        'half_width' => true
    ],
];

$resendSettings = [
    'mail_reply_to' => [
        'label' => 'Reply-To 信箱',
        'type' => 'text',
        'placeholder' => '可選，預設使用發件人信箱',
        'half_width' => true
    ],
    'resend_api_key' => [
        'label' => 'Resend API Key',
        'type' => 'password',
        'placeholder' => '查看教學以獲取 Resend API Key',
        'half_width' => true
    ]
];

$smtpSettings = [
    'smtp_host' => [
        'label' => 'SMTP 伺服器',
        'type' => 'text',
        'placeholder' => '例如：smtp.gmail.com',
        'half_width' => true
    ],
    'smtp_port' => [
        'label' => 'SMTP 端口',
        'type' => 'number',
        'placeholder' => '587 或 465',
        'half_width' => true
    ],
    'smtp_user' => [
        'label' => 'SMTP 帳號 / 信箱',
        'type' => 'text',
        'placeholder' => 'your-email@example.com',
        'half_width' => true
    ],
    'smtp_pass' => [
        'label' => 'SMTP 密碼 / 應用程式密碼',
        'type' => 'password',
        'placeholder' => '••••••••',
        'half_width' => true
    ],
    'smtp_tls' => [
        'label' => '啟用 TLS 安全加密',
        'type' => 'radio',
        'options' => ['true' => '是', 'false' => '否'],
        'half_width' => true
    ],
    'mail_reply_to_smtp' => [
        'label' => 'Reply-To 信箱',
        'type' => 'text',
        'placeholder' => '可選，預設使用發件人信箱',
        'half_width' => true
    ]
];

$commonMailSettings = [
    'admin_emails' => [
        'label' => '管理員收件信箱',
        'type' => 'text',
        'placeholder' => '例如：admin@aiurl.tw, alert@example.com',
        'description' => '當資產被檢舉或系統發生警報時發送通知至此信箱（多個信箱請用英文逗號分隔）',
        'half_width' => false
    ]
];
?>

<div class="settings-modal-dialog">
    <!-- Modal Header -->
    <div class="settings-modal-header">
        <div class="settings-modal-title">
            <i data-lucide="sliders-horizontal" class="icon"></i>
            <h2>系統設定</h2>
        </div>
        <div class="settings-modal-header-actions">
            <button type="submit" form="settings-form" class="settings-save-btn settings-header-save-btn">
                <i data-lucide="check" class="icon"></i>
                <span>儲存設定</span>
            </button>
            <button type="button" class="close-modal" aria-label="關閉" title="關閉 (Esc)">
                <i data-lucide="x" class="icon"></i>
            </button>
        </div>
    </div>

    <!-- Sheet Tabs Bar -->
    <div class="settings-tabs-bar" role="tablist">
        <button type="button" class="settings-tab-btn is-active" data-tab="general" role="tab" aria-selected="true">
            <i data-lucide="settings"></i>
            <span>一般設定</span>
        </button>
        <button type="button" class="settings-tab-btn" data-tab="storage" role="tab" aria-selected="false">
            <i data-lucide="hard-drive"></i>
            <span>儲存空間</span>
        </button>
        <button type="button" class="settings-tab-btn" data-tab="security" role="tab" aria-selected="false">
            <i data-lucide="shield-check"></i>
            <span>安全防護</span>
        </button>
        <button type="button" class="settings-tab-btn" data-tab="rss" role="tab" aria-selected="false">
            <i data-lucide="rss"></i>
            <span>Podcast RSS</span>
        </button>
        <button type="button" class="settings-tab-btn" data-tab="appearance" role="tab" aria-selected="false">
            <i data-lucide="palette"></i>
            <span>外觀追蹤</span>
        </button>
        <button type="button" class="settings-tab-btn" data-tab="smtp" role="tab" aria-selected="false">
            <i data-lucide="mail"></i>
            <span>郵件通知</span>
        </button>
    </div>

    <!-- Settings Form -->
    <form id="settings-form" method="POST">
        <?php if ($demoMode): ?>
            <div class="demo-mode-warning">
                <span>⚠️ <strong>示範模式</strong> - 目前處於示範模式，所有設定修改都將被禁止</span>
            </div>
        <?php endif; ?>

        <div class="settings-modal-body">
            <!-- Tab 1: 一般設定 -->
            <div class="settings-tab-panel is-active" id="panel-general">
                <div class="settings-panel-section">
                    <?php renderFields($generalSettings, $configs); ?>
                </div>
            </div>

            <!-- Tab 2: 儲存空間 -->
            <div class="settings-tab-panel" id="panel-storage">
                <div class="settings-panel-section">
                    <div class="form-group">
                        <label>主要儲存策略</label>
                        <div class="radio-group">
                            <?php foreach($storageConfigs['storage_types'] as $value => $storage): ?>
                                <label>
                                    <input type="radio" name="storage" value="<?= htmlspecialchars($value) ?>" 
                                           <?= $configs['storage']['value'] === $value ? 'checked' : '' ?>>
                                    <span><?= htmlspecialchars($storage['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php foreach ($storageConfigs['storage_types'] as $type => $storage): ?>
                        <div class="storage-config-card" id="<?= $type ?>-settings" style="display: none;">
                            <div class="storage-card-header">
                                <h3><?= htmlspecialchars($storage['name']) ?> 設定</h3>
                                <?php if ($type !== 'local'): ?>
                                    <button type="button" class="test-storage-btn" data-storage="<?= htmlspecialchars($type) ?>">
                                        <i data-lucide="link-2" class="icon"></i>
                                        <span class="btn-text">連線測試</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                            <?php renderFields(array_column($storage['configs'], null, 'key'), $configs); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tab 3: 安全防護 -->
            <div class="settings-tab-panel" id="panel-security">
                <div class="settings-panel-section">
                    <?php renderFields($securitySettings, $configs); ?>

                    <div class="form-group" style="margin-top: 10px;">
                        <label>Turnstile 連線診斷</label>
                        <button type="button" id="test-turnstile-btn" class="update-btn" style="max-width: 200px;">
                            <i data-lucide="shield"></i> 測試 Turnstile 連線
                        </button>
                    </div>

                    <div class="settings-section-divider"></div>

                    <div class="form-group">
                        <label>
                            管理員 API Token
                            <span class="label-description">用於 API 介接驗證，請妥善保管</span>
                        </label>
                        <div class="token-input-group">
                            <input type="text" name="token" id="token-input" value="<?= htmlspecialchars($userToken) ?>" readonly>
                            <button type="button" class="token-action-btn copy-token" title="複製 Token">
                                <i data-lucide="copy" class="icon"></i>
                            </button>
                            <button type="button" class="token-action-btn refresh-token" title="重新產生 Token">
                                <i data-lucide="refresh-cw" class="icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group form-group-half">
                            <label for="new_password">修改管理員密碼</label>
                            <div class="password-wrapper">
                                <input type="password" name="new_password" id="new_password" placeholder="留空則不修改" autocomplete="new-password">
                                <span class="toggle-password" title="顯示/隱藏密碼">
                                    <i data-lucide="eye" class="icon"></i>
                                </span>
                            </div>
                        </div>
                        <div class="form-group form-group-half">
                            <label for="confirm_password">確認新密碼</label>
                            <div class="password-wrapper">
                                <input type="password" name="confirm_password" id="confirm_password" placeholder="請再次輸入新密碼" autocomplete="new-password">
                                <span class="toggle-password" title="顯示/隱藏密碼">
                                    <i data-lucide="eye" class="icon"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 4: Podcast RSS -->
            <div class="settings-tab-panel" id="panel-rss">
                <div class="settings-panel-section">
                    <div class="form-group">
                        <label>
                            RSS Token 保護
                            <span class="label-description">開啟後，影片與音訊 RSS 訂閱連結需攜帶 token 才能存取</span>
                        </label>
                        <div class="radio-group">
                            <?php foreach(['false' => '公開（無需 Token）', 'true' => '啟用 Token 保護'] as $value => $label): ?>
                                <label>
                                    <input type="radio" name="rss_token_enabled" value="<?= $value ?>"
                                           <?= ($configs['rss_token_enabled']['value'] ?? 'false') === $value ? 'checked' : '' ?>>
                                    <span><?= $label ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            Podcast RSS Token
                            <span class="label-description">重新產生後，舊的訂閱連結將立即失效</span>
                        </label>
                        <div class="token-input-group">
                            <input type="text" name="rss_token" id="rss-token-input" value="<?= htmlspecialchars($rssToken) ?>" readonly>
                            <button type="button" class="token-action-btn copy-rss-token" title="複製 RSS Token">
                                <i data-lucide="copy" class="icon"></i>
                            </button>
                            <button type="button" class="token-action-btn refresh-rss-token" title="重新產生 RSS Token">
                                <i data-lucide="refresh-cw" class="icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            RSS 訂閱連結預覽
                            <span class="label-description">可直接複製連結至 Podcast 播放器（如 Apple Podcasts、Overcast）訂閱</span>
                        </label>
                        <div class="rss-preview-group">
                            <div class="rss-preview-item">
                                <span class="rss-preview-label">影片 RSS</span>
                                <input type="text" id="rss-video-preview" value="<?= htmlspecialchars($videoRssPreviewUrl) ?>" readonly data-public-url="<?= htmlspecialchars(buildRssUrl('video', ['site_domain' => $configs['site_domain']['value'] ?? '', 'rss_token_enabled' => 'false', 'rss_token' => ''], false)) ?>">
                            </div>
                            <div class="rss-preview-item">
                                <span class="rss-preview-label">音訊 RSS</span>
                                <input type="text" id="rss-audio-preview" value="<?= htmlspecialchars($audioRssPreviewUrl) ?>" readonly data-public-url="<?= htmlspecialchars(buildRssUrl('audio', ['site_domain' => $configs['site_domain']['value'] ?? '', 'rss_token_enabled' => 'false', 'rss_token' => ''], false)) ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 5: 外觀與追蹤 -->
            <div class="settings-tab-panel" id="panel-appearance">
                <div class="settings-panel-section">
                    <?php renderFields($appearanceSettings, $configs); ?>
                </div>
            </div>

            <!-- Tab 6: 郵件通知 -->
            <div class="settings-tab-panel" id="panel-smtp">
                <div class="settings-panel-section">
                    <div style="margin-bottom: 22px;">
                        <h3 style="margin: 0 0 6px 0; font-size: 1.05rem; font-weight: 700; color: #c0caf5; display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="mail" style="width: 20px; height: 20px; color: #7aa2f7;"></i>
                            <span>郵件通知</span>
                        </h3>
                        <p style="margin: 0; font-size: 13px; color: #7f88b2;">組態系統用於傳送報告、告警和測試郵件的郵件服務。</p>
                    </div>

                    <!-- Row 1: 啟用郵件傳送 / 郵件服務, Row 2: 發件人名稱 / 發件人信箱 -->
                    <?php renderFields($mailGeneralSettings, $configs); ?>

                    <!-- Row 3: Resend 專屬設定區塊 (Reply-To 信箱 / Resend API Key) -->
                    <div id="mail-section-resend" class="mail-driver-section" style="margin-top: 4px;">
                        <?php renderFields($resendSettings, $configs); ?>
                    </div>

                    <!-- Row 3 alternative: SMTP 專屬設定區塊 -->
                    <div id="mail-section-smtp" class="mail-driver-section" style="margin-top: 4px; display: none;">
                        <?php renderFields($smtpSettings, $configs); ?>
                    </div>

                    <!-- 管理員收件信箱 -->
                    <div style="margin-top: 4px;">
                        <?php renderFields($commonMailSettings, $configs); ?>
                    </div>

                    <!-- Row 4: 測試收件人 -->
                    <div class="form-group" style="margin-top: 18px;">
                        <label for="test-email-recipient">測試收件人</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="email" id="test-email-recipient" name="test_email_recipient" 
                                   placeholder="demo@insightflare.net" 
                                   value="<?= htmlspecialchars(explode(',', $configs['admin_emails']['value'] ?? '')[0] ?? '') ?>"
                                   style="flex: 1; padding: 10px 14px; font-size: 13.5px; border-radius: 8px; border: 1px solid rgba(122, 162, 247, 0.25); background: rgba(22, 26, 43, 0.95); color: #c0caf5;">
                            <button type="button" id="btn-test-mail" class="update-btn" 
                                    style="white-space: nowrap; height: 42px; padding: 0 20px; display: inline-flex; align-items: center; gap: 8px; font-weight: 600; font-size: 13.5px; background: rgba(122, 162, 247, 0.15); border: 1px solid rgba(122, 162, 247, 0.4); color: #7dcfff; border-radius: 8px; cursor: pointer; transition: all 0.2s;">
                                <i data-lucide="send" style="width: 15px; height: 15px;"></i>
                                <span>傳送測試郵件</span>
                            </button>
                        </div>
                    </div>

                    <!-- 操作輔助列：保存組態與教學按鈕 -->
                    <div style="margin-top: 22px; display: flex; justify-content: space-between; align-items: center;">
                        <button type="submit" class="settings-save-btn" style="padding: 9px 20px; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; border-radius: 8px;">
                            <i data-lucide="save" class="icon" style="width: 15px; height: 15px;"></i>
                            <span>保存組態</span>
                        </button>
                        <a href="https://resend.com/docs" target="_blank" rel="noopener noreferrer" 
                           class="update-btn" 
                           style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; font-size: 13px; color: #a9b1d6; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 8px;">
                            <i data-lucide="book-open" style="width: 15px; height: 15px;"></i>
                            <span>教學</span>
                        </a>
                    </div>

                    <div class="settings-section-divider"></div>

                    <h3 style="margin: 0 0 16px 0; font-size: 0.98rem; color: #c0caf5; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="wrench" style="width: 18px; height: 18px; color: #e0af68;"></i>
                        <span>系統維護工具</span>
                    </h3>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <button type="button" id="optimize-db-btn" class="update-btn">
                            <i data-lucide="database"></i> 最佳化資料庫 (VACUUM)
                        </button>
                        <button type="button" id="check-update-btn" class="update-btn">
                            <i data-lucide="download-cloud"></i> 檢查系統更新
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Footer Action Bar -->
        <div class="settings-modal-footer">
            <div class="settings-footer-hint">
                <span>快捷鍵：<kbd>Ctrl/Cmd</kbd> + <kbd>S</kbd> 快速儲存</span>
            </div>
            <div class="settings-footer-actions">
                <button type="button" class="settings-cancel-btn close-modal">取消</button>
                <button type="submit" name="submit" class="settings-save-btn settings-footer-save-btn">
                    <i data-lucide="save" class="icon"></i>
                    <span>儲存設定</span>
                </button>
            </div>
        </div>
    </form>
</div>
<script>
    if (window.lucide) {
        lucide.createIcons();
    }
</script>
