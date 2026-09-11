<?php
/**
 * Cloudflare Turnstile Helper for 888box
 *
 * Provides site-level toggle checking, secret validation,
 * token verification with Cloudflare API, and graceful fallback.
 */

require_once __DIR__ . '/database.php';

/**
 * 檢查 Turnstile 是否在指定場景啟用
 *
 * @param PDO|null $pdo
 * @param string|null $feature 'login' | 'upload' | null (null 為僅檢查總開關與金鑰)
 * @return bool
 */
function isTurnstileEnabled($pdo, ?string $feature = null): bool {
    if (!$pdo) {
        return false;
    }

    try {
        $config = Database::getConfig($pdo);
    } catch (Throwable $e) {
        return false;
    }

    if (!$config) {
        return false;
    }

    // 1. 檢查總開關 (支援 configs 資料表與 .env TURNSTILE_ENABLED)
    $enabled = filter_var($config['turnstile_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
    if (!$enabled) {
        return false;
    }

    // 2. 檢查是否配置必要金鑰 (支援 .env 覆蓋)
    $siteKey = trim($config['turnstile_site_key'] ?? '');
    $secretKey = trim($config['turnstile_secret_key'] ?? '');
    if (empty($siteKey) || empty($secretKey)) {
        return false;
    }

    // 3. 檢查細項功能開關
    if ($feature === 'login') {
        return filter_var($config['turnstile_protect_login'] ?? true, FILTER_VALIDATE_BOOLEAN);
    }
    if ($feature === 'upload') {
        return filter_var($config['turnstile_protect_upload'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    return true;
}

/**
 * 取得 Turnstile Site Key (公鑰)
 */
function getTurnstileSiteKey($pdo): string {
    if (!isTurnstileEnabled($pdo)) {
        return '';
    }
    $config = Database::getConfig($pdo);
    return trim($config['turnstile_site_key'] ?? '');
}

/**
 * 向 Cloudflare siteverify 端點驗證 Turnstile Token
 *
 * @param string|null $token 前端回傳的 cf-turnstile-response
 * @param string $secretKey Cloudflare Turnstile 密鑰
 * @param string|null $clientIp 客戶端 IP (可選)
 * @return array ['success' => bool, 'error' => string|null]
 */
function verifyTurnstileToken(?string $token, string $secretKey, ?string $clientIp = null): array {
    $token = trim((string)$token);
    if (empty($token) || strlen($token) > 2048) {
        return ['success' => false, 'error' => '驗證碼為空或格式不正確'];
    }

    if (empty($secretKey)) {
        return ['success' => false, 'error' => '系統尚未配置 Turnstile 密鑰'];
    }

    $postData = [
        'secret' => $secretKey,
        'response' => $token
    ];
    if (!empty($clientIp)) {
        $postData['remoteip'] = $clientIp;
    }

    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    try {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($postData),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response === false || $httpCode !== 200) {
                return [
                    'success' => false,
                    'error' => '連線驗證伺服器失敗: ' . ($curlError ?: "HTTP $httpCode")
                ];
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'content' => http_build_query($postData),
                    'timeout' => 8
                ]
            ]);
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                return ['success' => false, 'error' => '無法連線至 Cloudflare 驗證伺服器'];
            }
        }

        $result = json_decode($response, true);
        if (!is_array($result) || empty($result['success'])) {
            $errorCodes = isset($result['error-codes']) ? implode(', ', (array)$result['error-codes']) : 'unknown';
            return [
                'success' => false,
                'error' => 'Turnstile 安全驗證未通過 (' . $errorCodes . ')'
            ];
        }

        return ['success' => true, 'error' => null];
    } catch (Throwable $e) {
        return ['success' => false, 'error' => '驗證過程發生異常: ' . $e->getMessage()];
    }
}

/**
 * 測試 Turnstile Secret Key 與 Cloudflare API 連線
 * （使用測試探針 Token 發送請求；若 Cloudflare 回傳 invalid-input-response 且無 invalid-input-secret，代表金鑰有效且連線正常）
 */
function testTurnstileSecretKey(string $secretKey): array {
    $secretKey = trim($secretKey);
    if (empty($secretKey)) {
        return ['success' => false, 'message' => '請填寫 Turnstile Secret Key'];
    }

    $postData = [
        'secret' => $secretKey,
        'response' => 'XXXX.DUMMY.TOKEN.XXXX'
    ];

    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    try {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($postData),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response === false || $httpCode !== 200) {
                return ['success' => false, 'message' => '無法連線至 Cloudflare: ' . ($curlError ?: "HTTP $httpCode")];
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'content' => http_build_query($postData),
                    'timeout' => 8
                ]
            ]);
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                return ['success' => false, 'message' => '無法連線至 Cloudflare'];
            }
        }

        $result = json_decode($response, true);
        $errorCodes = $result['error-codes'] ?? [];

        if (in_array('invalid-input-secret', $errorCodes, true)) {
            return ['success' => false, 'message' => 'Secret Key 無效 (invalid-input-secret)，請檢查金鑰'];
        }

        if (in_array('invalid-input-response', $errorCodes, true)) {
            return ['success' => true, 'message' => 'Turnstile 連線測試成功！Secret Key 與 Cloudflare 通訊正常'];
        }

        return ['success' => true, 'message' => '連線測試完成'];
    } catch (Throwable $e) {
        return ['success' => false, 'message' => '連線測試出錯: ' . $e->getMessage()];
    }
}

/**
 * 檢查上傳請求是否需要並通過 Turnstile 驗證
 *
 * 具備完整 Fallback 與 API 工具自動放行：
 * 1. 站台未開啟 Turnstile 或未開啟上傳保護 -> 直接放行 (Fallback)
 * 2. 請求帶有合法 Token (Bearer 或 query/post token，如 ShareX, PicGo, MCP, CLI) -> 直接放行
 * 3. 管理員 Session 已登入 -> 直接放行
 * 4. 當前瀏覽器 Session 在 10 分鐘內已有驗證紀錄 -> 直接放行 (解決批次拖放多圖上傳 race condition)
 * 5. 匿名訪客一般上傳 -> 檢驗 cf-turnstile-response，通過後記錄 Session 寬限期
 *
 * @param PDO $pdo
 * @param array $config
 * @return array ['allowed' => bool, 'message' => string|null]
 */
function checkUploadTurnstilePermission($pdo, $config): array {
    // 1. 如果 Turnstile 未啟用上傳保護，直接放行 (Fallback)
    if (!isTurnstileEnabled($pdo, 'upload')) {
        return ['allowed' => true, 'message' => null];
    }

    // 2. 如果請求帶有合法 API Token，視為自動化工具/整合客戶端，直接放行
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (empty($authHeader) && function_exists('apache_request_headers')) {
        $reqHeaders = apache_request_headers();
        $authHeader = $reqHeaders['Authorization'] ?? $reqHeaders['authorization'] ?? '';
    }
    if (empty($authHeader) && function_exists('getallheaders')) {
        $reqHeaders = getallheaders();
        $authHeader = $reqHeaders['Authorization'] ?? $reqHeaders['authorization'] ?? '';
    }

    $token = '';
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = trim($matches[1]);
    } else {
        $token = trim($_POST['token'] ?? $_GET['token'] ?? '');
    }

    if (!empty($token)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE token = ?");
            $stmt->execute([$token]);
            if ($stmt->fetch()) {
                return ['allowed' => true, 'message' => null];
            }
        } catch (Throwable $e) {
            // DB 查詢異常則繼續後續檢查
        }
    }

    // 3. 如果是已登入管理員的瀏覽器 Session，直接放行
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        return ['allowed' => true, 'message' => null];
    }

    // 4. 如果當前 Session 在 10 分鐘內已有通過 Turnstile 驗證紀錄，直接放行
    if (!empty($_SESSION['turnstile_verified_at']) && (time() - (int)$_SESSION['turnstile_verified_at'] < 600)) {
        return ['allowed' => true, 'message' => null];
    }

    // 5. 檢驗 Turnstile Token
    $turnstileToken = $_POST['cf-turnstile-response']
        ?? $_SERVER['HTTP_CF_TURNSTILE_RESPONSE']
        ?? $_SERVER['HTTP_X_TURNSTILE_TOKEN']
        ?? '';

    if (empty($turnstileToken)) {
        return ['allowed' => false, 'message' => '請完成機器人安全驗證'];
    }

    require_once __DIR__ . '/upload.php';
    $clientIp = function_exists('getClientIp') ? getClientIp() : ($_SERVER['REMOTE_ADDR'] ?? '');
    $secretKey = trim($config['turnstile_secret_key'] ?? '');

    $verifyResult = verifyTurnstileToken($turnstileToken, $secretKey, $clientIp);
    if (!$verifyResult['success']) {
        return ['allowed' => false, 'message' => $verifyResult['error'] ?? '機器人驗證失敗，請重新整理重試'];
    }

    // 記錄驗證時間，給予 10 分鐘寬限期
    $_SESSION['turnstile_verified_at'] = time();
    return ['allowed' => true, 'message' => null];
}

/**
 * 在 HTML <head> 或適當位置輸出 Turnstile JS 載入標籤
 */
function renderTurnstileScript($pdo, ?string $feature = null): void {
    if (!isTurnstileEnabled($pdo, $feature)) {
        return;
    }
    echo '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>' . "\n";
}

/**
 * 輸出 Turnstile Widget 容器
 */
function renderTurnstileWidget($pdo, ?string $feature = 'upload', string $action = 'upload', string $extraStyle = 'margin: 15px auto; display: flex; justify-content: center;'): void {
    if (!isTurnstileEnabled($pdo, $feature)) {
        return;
    }
    $siteKey = getTurnstileSiteKey($pdo);
    if (empty($siteKey)) {
        return;
    }
    echo '<div style="' . htmlspecialchars($extraStyle) . '">';
    echo '<div class="cf-turnstile" data-sitekey="' . htmlspecialchars($siteKey) . '" data-action="' . htmlspecialchars($action) . '" data-theme="auto" data-callback="onTurnstileUploadSuccess"></div>';
    echo '</div>' . "\n";
}

/**
 * 輸出前端全域 Token 保存小工具
 */
function renderTurnstileJsHelper($pdo, ?string $feature = 'upload'): void {
    if (!isTurnstileEnabled($pdo, $feature)) {
        return;
    }
    echo '<script>
window.turnstileUploadToken = "";
window.onTurnstileUploadSuccess = function(token) {
    window.turnstileUploadToken = token;
};
</script>' . "\n";
}
