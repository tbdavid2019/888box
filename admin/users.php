<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    require 'login.php';
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/theme_helper.php';
require_once __DIR__ . '/../config/admin_ui.php';

$db = Database::getInstance();
$pdo = $db->getConnection();
$config = Database::getConfig($pdo);
$demoMode = ($_ENV['DEMO_MODE'] ?? 'false') === 'true';

// 權限檢查：嚴格由資料庫重新驗證當前登入者角色與啟用狀態
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
if (!$currentUserId) {
    header('Location: /admin/login.php');
    exit;
}
$stmtCurrentUser = $pdo->prepare("SELECT role, status FROM users WHERE id = ?");
$stmtCurrentUser->execute([$currentUserId]);
$currentUserRecord = $stmtCurrentUser->fetch(PDO::FETCH_ASSOC);
if (!$currentUserRecord || (int)$currentUserRecord['status'] === 0 || $currentUserRecord['role'] !== 'admin') {
    $_SESSION = [];
    session_destroy();
    header('Location: /admin/login.php?error=no_permission');
    exit;
}
$_SESSION['role'] = 'admin';

// 確保 Session 包含 CSRF 權杖
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

// 處理登出
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_destroy();
    header('Location: /admin/login.php');
    exit();
}

// 處理 AJAX 請求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    // CSRF 權杖防護
    $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$csrf)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'CSRF 安全驗證失敗，請重新整理頁面後再試'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($demoMode) {
        echo json_encode(['success' => false, 'message' => '示範模式下禁止修改帳號'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $action = $_POST['action'];

    try {
        switch ($action) {
            case 'create':
                $username = trim((string)($_POST['username'] ?? ''));
                $password = (string)($_POST['password'] ?? '');
                $note = trim((string)($_POST['note'] ?? ''));
                $role = in_array($_POST['role'] ?? '', ['admin', 'user'], true) ? $_POST['role'] : 'user';
                $dailyLimitType = $_POST['daily_limit_type'] ?? 'unlimited';
                
                if ($dailyLimitType === 'unlimited') {
                    $dailyLimit = 0; // 0 代表無限制，可突破 100 則限制
                } elseif ($dailyLimitType === 'default') {
                    $dailyLimit = -1; // -1 代表依循全站預設 (100)
                } else {
                    $dailyLimit = max(1, (int)($_POST['custom_daily_limit'] ?? 100));
                }

                $status = isset($_POST['status']) && (int)$_POST['status'] === 0 ? 0 : 1;

                if (empty($username)) {
                    throw new Exception('請填寫使用者名稱');
                }
                if (strlen($username) < 2 || strlen($username) > 64) {
                    throw new Exception('使用者名稱長度需介於 2 至 64 個字元');
                }
                if (!preg_match('/^[a-zA-Z0-9_\-\.@]+$/', $username)) {
                    throw new Exception('使用者名稱僅能包含英文、數字、底線、減號、點或 @');
                }
                if (empty($password) || strlen($password) < 6) {
                    throw new Exception('密碼長度至少需為 6 個字元');
                }

                // 檢查使用者名稱是否重複
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $checkStmt->execute([$username]);
                if ($checkStmt->fetch()) {
                    throw new Exception("使用者名稱「{$username}」已被使用，請更換");
                }

                $token = bin2hex(random_bytes(16));
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("INSERT INTO users (username, password, token, role, daily_limit, status, note, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, datetime('now'))");
                $stmt->execute([$username, $hashedPassword, $token, $role, $dailyLimit, $status, $note]);
                $newId = (int)$pdo->lastInsertId();

                echo json_encode([
                    'success' => true,
                    'message' => '同事帳號已成功建立！',
                    'data' => [
                        'id' => $newId,
                        'username' => $username,
                        'token' => $token,
                        'role' => $role,
                        'daily_limit' => $dailyLimit,
                        'status' => $status,
                        'note' => $note
                    ]
                ], JSON_UNESCAPED_UNICODE);
                break;

            case 'update':
                $id = (int)($_POST['id'] ?? 0);
                if (!$id) {
                    throw new Exception('未指定帳號 ID');
                }

                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$targetUser) {
                    throw new Exception('找不到該帳號');
                }

                $note = trim((string)($_POST['note'] ?? ''));
                $role = in_array($_POST['role'] ?? '', ['admin', 'user'], true) ? $_POST['role'] : $targetUser['role'];
                $status = isset($_POST['status']) && (int)$_POST['status'] === 0 ? 0 : 1;
                $dailyLimitType = $_POST['daily_limit_type'] ?? 'unlimited';

                if ($dailyLimitType === 'unlimited') {
                    $dailyLimit = 0;
                } elseif ($dailyLimitType === 'default') {
                    $dailyLimit = -1;
                } else {
                    $dailyLimit = max(1, (int)($_POST['custom_daily_limit'] ?? 100));
                }

                // 不能停用自己目前的登入帳號
                if ($id === $currentUserId && $status === 0) {
                    throw new Exception('無法停用您目前登入的帳號');
                }

                // 不能將自己從管理員降級為一般帳號（若為最後一個管理員）
                if ($id === $currentUserId && $role !== 'admin') {
                    $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
                    if ($adminCount <= 1) {
                        throw new Exception('系統必須保留至少一位管理員');
                    }
                }

                $newPassword = (string)($_POST['new_password'] ?? '');
                if ($newPassword !== '') {
                    if (strlen($newPassword) < 6) {
                        throw new Exception('新密碼長度至少需為 6 個字元');
                    }
                    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                    $updateStmt = $pdo->prepare("UPDATE users SET note = ?, role = ?, daily_limit = ?, status = ?, password = ? WHERE id = ?");
                    $updateStmt->execute([$note, $role, $dailyLimit, $status, $hashed, $id]);
                } else {
                    $updateStmt = $pdo->prepare("UPDATE users SET note = ?, role = ?, daily_limit = ?, status = ? WHERE id = ?");
                    $updateStmt->execute([$note, $role, $dailyLimit, $status, $id]);
                }

                $stmt = $pdo->prepare("SELECT id, username, token, role, daily_limit, status, note FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);

                echo json_encode([
                    'success' => true,
                    'message' => '帳號設定已儲存更新',
                    'data' => $updatedUser
                ], JSON_UNESCAPED_UNICODE);
                break;

            case 'regenerate_token':
                $id = (int)($_POST['id'] ?? 0);
                if (!$id) {
                    throw new Exception('未指定帳號 ID');
                }

                $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
                $stmt->execute([$id]);
                if (!$stmt->fetch()) {
                    throw new Exception('找不到該帳號');
                }

                $newToken = bin2hex(random_bytes(16));
                $updateStmt = $pdo->prepare("UPDATE users SET token = ? WHERE id = ?");
                $updateStmt->execute([$newToken, $id]);

                echo json_encode([
                    'success' => true,
                    'message' => 'API Token 已重新產生',
                    'token' => $newToken
                ], JSON_UNESCAPED_UNICODE);
                break;

            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                if (!$id) {
                    throw new Exception('未指定帳號 ID');
                }
                if ($id === $currentUserId) {
                    throw new Exception('無法刪除您自己目前登入中的帳號');
                }

                $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $userToDelete = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$userToDelete) {
                    throw new Exception('該帳號不存在或已被刪除');
                }

                if ($userToDelete['role'] === 'admin') {
                    $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
                    if ($adminCount <= 1) {
                        throw new Exception('系統必須保留至少一位管理員，無法刪除');
                    }
                }

                $delStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $delStmt->execute([$id]);

                echo json_encode(['success' => true, 'message' => '帳號已成功刪除'], JSON_UNESCAPED_UNICODE);
                break;

            default:
                throw new Exception('未知的操作指令');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// 取得所有帳號列表與關聯數據
$stmt = $pdo->query("
    SELECT u.*, 
           (SELECT COUNT(id) FROM images WHERE user_id = u.id) as upload_count
    FROM users u 
    ORDER BY u.id ASC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 統計數據
$totalUsers = count($users);
$adminCount = 0;
$colleagueCount = 0;
$unlimitedCount = 0;
$systemDefaultLimit = (int)Database::getConfig($pdo, 'max_uploads_per_day');
if ($systemDefaultLimit <= 0) {
    $systemDefaultLimit = 100;
}

foreach ($users as $u) {
    if (($u['role'] ?? 'user') === 'admin') {
        $adminCount++;
    } else {
        $colleagueCount++;
    }
    $dailyLimit = isset($u['daily_limit']) ? (int)$u['daily_limit'] : 0;
    if ($dailyLimit === 0 || ($u['role'] ?? '') === 'admin') {
        $unlimitedCount++;
    }
}

$forwardedProto = strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || $forwardedProto === 'https'
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
$scheme = $isHttps ? 'https://' : 'http://';
$siteUrl = $scheme . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$apiUploadUrl = rtrim($siteUrl, '/') . '/api.php?action=upload';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <title>帳號管理後台 - 888 BOX</title>
    <link rel="shortcut icon" href="/static/favicon.svg">
    <link rel="stylesheet" href="/static/css/admin.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/static/css/admin/shared.css?v=<?= time(); ?>">
    <?php renderThemeStyles($pdo); ?>
    <?php renderCustomTrackingCode($pdo); ?>
    <style>
        :root {
            --bg-canvas: #1a1b26;
            --bg-surface: #24283b;
            --bg-subtle: #1f2335;
            --border-subtle: #414868;
            --border-focus: #7aa2f7;
            --accent-primary: #7aa2f7;
            --accent-cyan: #7dcfff;
            --accent-green: #9ece6a;
            --accent-red: #f7768e;
            --accent-purple: #bb9af7;
            --accent-yellow: #e0af68;
            --text-primary: #c0caf5;
            --text-secondary: #7f88b2;
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-pill: 9999px;
            --transition-fast: 180ms cubic-bezier(0.16, 1, 0.3, 1);
        }

        @media (prefers-reduced-motion: reduce) {
            *, ::before, ::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }

        body {
            background: radial-gradient(circle at top, rgba(122, 162, 247, 0.12), transparent 32%), linear-gradient(180deg, #1f2335 0%, #1a1b26 42%, #16161e 100%);
            color: var(--text-primary);
            font-family: var(--font-ui);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            box-sizing: border-box;
        }

        .users-container {
            max-width: 1240px;
            margin: 0 auto 60px auto;
        }

        /* 標題與簡介 */
        .page-header {
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 16px;
        }
        .page-title-group h1 {
            margin: 0 0 6px 0;
            font-size: 1.65rem;
            font-weight: 700;
            color: #c0caf5;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .page-title-group p {
            margin: 0;
            color: #7f88b2;
            font-size: 0.92rem;
            line-height: 1.4;
        }

        /* 統計指標卡片 */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }
        .metric-card {
            background: rgba(36, 40, 59, 0.7);
            border: 1px solid rgba(122, 162, 247, 0.18);
            backdrop-filter: blur(12px);
            border-radius: 12px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.2s, border-color 0.2s;
        }
        .metric-card:hover {
            border-color: rgba(122, 162, 247, 0.4);
            transform: translateY(-2px);
        }
        .metric-info h3 {
            margin: 0 0 6px 0;
            font-size: 0.85rem;
            font-weight: 600;
            color: #7f88b2;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .metric-info .value {
            font-size: 1.85rem;
            font-weight: 700;
            color: #c0caf5;
            line-height: 1;
        }
        .metric-icon-wrap {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .icon-blue { background: rgba(122, 162, 247, 0.15); color: #7aa2f7; }
        .icon-green { background: rgba(158, 206, 106, 0.15); color: #9ece6a; }
        .icon-purple { background: rgba(187, 154, 247, 0.15); color: #bb9af7; }
        .icon-gold { background: rgba(224, 175, 104, 0.18); color: #e0af68; }

        /* 工具列 */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 20px;
            background: rgba(30, 34, 51, 0.8);
            border: 1px solid #414868;
            padding: 14px 18px;
            border-radius: 12px;
        }
        .search-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(26, 27, 38, 0.85);
            border: 1px solid #414868;
            border-radius: 8px;
            padding: 8px 14px;
            min-width: 260px;
        }
        .search-wrap input {
            background: transparent;
            border: none;
            color: #c0caf5;
            outline: none;
            font-size: 0.9rem;
            width: 100%;
        }
        .search-wrap input::placeholder {
            color: #565f89;
        }
        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #7aa2f7 0%, #bb9af7 100%);
            color: #1a1b26;
            font-weight: 700;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.92rem;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(122, 162, 247, 0.25);
        }
        .btn-primary:hover {
            filter: brightness(1.1);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(122, 162, 247, 0.35);
        }
        .btn-secondary {
            background: rgba(41, 46, 66, 0.8);
            color: #c0caf5;
            border: 1px solid #414868;
            padding: 9px 15px;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .btn-secondary:hover {
            background: rgba(65, 72, 104, 0.9);
            border-color: #7aa2f7;
            color: #fff;
        }

        /* 列表表格容器 */
        .table-responsive {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #414868;
            background: rgba(36, 40, 59, 0.85);
            backdrop-filter: blur(8px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.25);
        }
        table.users-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.9rem;
        }
        table.users-table th {
            background: rgba(26, 27, 38, 0.9);
            color: #7f88b2;
            font-weight: 600;
            padding: 14px 16px;
            border-bottom: 1px solid #414868;
            white-space: nowrap;
        }
        table.users-table td {
            padding: 14px 16px;
            border-bottom: 1px solid rgba(65, 72, 104, 0.4);
            vertical-align: middle;
            color: #c0caf5;
        }
        table.users-table tr:last-child td {
            border-bottom: none;
        }
        table.users-table tr:hover td {
            background: rgba(41, 46, 66, 0.45);
        }

        /* 欄位樣式 */
        .user-identity {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .avatar-admin {
            background: linear-gradient(135deg, #bb9af7, #7aa2f7);
            color: #1a1b26;
        }
        .avatar-user {
            background: linear-gradient(135deg, #7dcfff, #9ece6a);
            color: #1a1b26;
        }
        .user-meta .username {
            font-weight: 700;
            color: #c0caf5;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.95rem;
        }
        .user-meta .note {
            font-size: 0.8rem;
            color: #7f88b2;
            margin-top: 2px;
        }
        .current-badge {
            font-size: 0.7rem;
            padding: 1px 6px;
            background: rgba(122, 162, 247, 0.2);
            color: #7aa2f7;
            border: 1px solid rgba(122, 162, 247, 0.4);
            border-radius: 4px;
            font-weight: 600;
        }

        /* 徽章 Badge */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .badge-role-admin {
            background: rgba(187, 154, 247, 0.16);
            color: #bb9af7;
            border: 1px solid rgba(187, 154, 247, 0.35);
        }
        .badge-role-user {
            background: rgba(125, 207, 255, 0.16);
            color: #7dcfff;
            border: 1px solid rgba(125, 207, 255, 0.35);
        }
        .badge-unlimited {
            background: rgba(224, 175, 104, 0.18);
            color: #e0af68;
            border: 1px solid rgba(224, 175, 104, 0.45);
        }
        .badge-custom {
            background: rgba(158, 206, 106, 0.16);
            color: #9ece6a;
            border: 1px solid rgba(158, 206, 106, 0.35);
        }
        .badge-default {
            background: rgba(127, 136, 178, 0.16);
            color: #a9b1d6;
            border: 1px solid rgba(127, 136, 178, 0.3);
        }

        .badge-active {
            background: rgba(158, 206, 106, 0.15);
            color: #9ece6a;
            border: 1px solid rgba(158, 206, 106, 0.3);
        }
        .badge-disabled {
            background: rgba(247, 118, 142, 0.15);
            color: #f7768e;
            border: 1px solid rgba(247, 118, 142, 0.3);
        }

        /* Token 區塊 */
        .token-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .token-code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            background: rgba(26, 27, 38, 0.8);
            padding: 4px 8px;
            border-radius: 6px;
            border: 1px solid #414868;
            font-size: 0.82rem;
            color: #7dcfff;
            max-width: 140px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .btn-icon {
            background: transparent;
            border: none;
            color: #7f88b2;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .btn-icon:hover {
            color: #7aa2f7;
            background: rgba(122, 162, 247, 0.12);
        }
        .btn-icon.danger:hover {
            color: #f7768e;
            background: rgba(247, 118, 142, 0.12);
        }

        /* 操作按鈕組 */
        .actions-cell {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* 模態視窗 */
        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 18, 32, 0.78);
            backdrop-filter: blur(6px);
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-box {
            background: #24283b;
            border: 1px solid #414868;
            border-radius: 16px;
            width: 100%;
            max-width: 540px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
            animation: modalFadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.96) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid rgba(65, 72, 104, 0.5);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            color: #c0caf5;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .modal-close {
            background: transparent;
            border: none;
            color: #7f88b2;
            cursor: pointer;
            padding: 4px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-close:hover {
            color: #f7768e;
            background: rgba(247, 118, 142, 0.12);
        }
        .modal-body {
            padding: 24px;
        }
        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid rgba(65, 72, 104, 0.5);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background: rgba(26, 27, 38, 0.4);
            border-bottom-left-radius: 16px;
            border-bottom-right-radius: 16px;
        }

        /* 表單元件 */
        .form-item {
            margin-bottom: 18px;
        }
        .form-item label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.88rem;
            font-weight: 600;
            color: #a9b1d6;
        }
        .form-item .tip {
            font-size: 0.8rem;
            color: #7f88b2;
            margin-top: 4px;
        }
        .form-control {
            width: 100%;
            background: rgba(26, 27, 38, 0.85);
            border: 1px solid #414868;
            color: #c0caf5;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.92rem;
            outline: none;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            border-color: #7aa2f7;
        }

        .radio-pills {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .radio-pill-label {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: rgba(26, 27, 38, 0.7);
            border: 1px solid #414868;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.88rem;
            color: #c0caf5;
            transition: all 0.2s;
        }
        .radio-pill-label:hover {
            border-color: #7aa2f7;
        }
        .radio-pill-label input[type="radio"] {
            margin: 0;
            accent-color: #7aa2f7;
        }
        .radio-pill-label.selected {
            background: rgba(122, 162, 247, 0.15);
            border-color: #7aa2f7;
            color: #7dcfff;
        }

        /* 程式碼與呼叫範例卡片 */
        .code-block {
            background: #1a1b26;
            border: 1px solid #414868;
            border-radius: 8px;
            padding: 14px 16px;
            position: relative;
            margin: 12px 0;
        }
        .code-block pre {
            margin: 0;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.84rem;
            color: #7dcfff;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .code-copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(41, 46, 66, 0.8);
            border: 1px solid #414868;
            color: #c0caf5;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .code-copy-btn:hover {
            background: #7aa2f7;
            color: #1a1b26;
        }

        /* 鍵盤可存取性無障礙外環 */
        button:focus-visible, input:focus-visible, select:focus-visible {
            outline: 2px solid var(--border-focus);
            outline-offset: 2px;
        }

        /* 複製 Token 成功微反饋 */
        .btn-icon.is-copied {
            background: rgba(158, 206, 106, 0.25) !important;
            border-color: rgba(158, 206, 106, 0.6) !important;
            color: #9ece6a !important;
            transform: scale(1.08);
        }

        @keyframes lucideSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .spin-icon {
            animation: lucideSpin 0.8s linear infinite;
        }

        @media (max-width: 768px) {
            body { padding: 12px; }
            .toolbar { flex-direction: column; align-items: stretch; }
            .search-wrap { width: 100%; min-width: auto; }
            .toolbar-actions { justify-content: stretch; }
            .btn-primary, .btn-secondary { flex: 1; justify-content: center; }
            .users-table th, .users-table td { padding: 12px 10px; }
        }
    </style>
</head>
<body>
    <?php renderAdminHeader('users', '帳號管理後台', [
        ['label' => '返回首頁', 'href' => '/'],
        ['label' => '登出', 'href' => '/admin/users.php?logout=true'],
    ]); ?>

    <div class="users-container">
        <!-- 頂部標題與快捷導覽 -->
        <div class="page-header">
            <div class="page-title-group">
                <h1><i data-lucide="users" style="color: #7aa2f7;"></i> 帳號管理</h1>
                <p>管理團隊成員與同事專屬帳號，設定獨立 API Key 並開放突破每日 100 則上傳上限。</p>
            </div>
            <div>
                <button type="button" class="btn-primary" onclick="openAddUserModal()">
                    <i data-lucide="user-plus"></i> 新增同事帳號
                </button>
            </div>
        </div>

        <!-- 數據統計看版 -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-info">
                    <h3>總帳號數</h3>
                    <div class="value"><?= $totalUsers ?></div>
                </div>
                <div class="metric-icon-wrap icon-blue">
                    <i data-lucide="users" style="width: 24px; height: 24px;"></i>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-info">
                    <h3>同事 / 成員帳號</h3>
                    <div class="value"><?= $colleagueCount ?></div>
                </div>
                <div class="metric-icon-wrap icon-green">
                    <i data-lucide="user-check" style="width: 24px; height: 24px;"></i>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-info">
                    <h3>突破 100 則上限</h3>
                    <div class="value"><?= $unlimitedCount ?></div>
                </div>
                <div class="metric-icon-wrap icon-gold">
                    <i data-lucide="zap" style="width: 24px; height: 24px;"></i>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-info">
                    <h3>全站訪客預設上限</h3>
                    <div class="value"><?= $systemDefaultLimit ?><span style="font-size:0.9rem; font-weight:normal; color:#7f88b2; margin-left:4px;">次/日</span></div>
                </div>
                <div class="metric-icon-wrap icon-purple">
                    <i data-lucide="shield" style="width: 24px; height: 24px;"></i>
                </div>
            </div>
        </div>

        <!-- 搜尋與篩選工具列 -->
        <div class="toolbar">
            <div class="search-wrap">
                <i data-lucide="search" style="width: 18px; height: 18px; color: #7f88b2;"></i>
                <input type="text" id="user-search-input" placeholder="搜尋帳號名稱或備註..." oninput="filterUsers()">
            </div>
            <div class="toolbar-actions">
                <button type="button" class="btn-secondary" onclick="openApiGuideModal()">
                    <i data-lucide="code"></i> 同事 API 介接指南
                </button>
            </div>
        </div>

        <!-- 使用者列表表格 -->
        <div class="table-responsive">
            <table class="users-table" id="users-table">
                <thead>
                    <tr>
                        <th>使用者 / 備註</th>
                        <th>角色權限</th>
                        <th>每日上傳配額</th>
                        <th>API Key / Token</th>
                        <th>累計上傳</th>
                        <th>狀態</th>
                        <th>建立時間</th>
                        <th style="text-align: right;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 50px; color: #7f88b2;">
                                目前尚無使用者帳號
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <?php
                            $uid = (int)$user['id'];
                            $isCurrent = ($uid === $currentUserId);
                            $isAdmin = (($user['role'] ?? 'user') === 'admin');
                            $dailyLimit = isset($user['daily_limit']) ? (int)$user['daily_limit'] : 0;
                            $isActive = !isset($user['status']) || (int)$user['status'] === 1;
                            $firstLetter = strtoupper(substr($user['username'], 0, 1));
                            ?>
                            <tr class="user-row" 
                                data-id="<?= $uid ?>" 
                                data-username="<?= htmlspecialchars($user['username']) ?>"
                                data-note="<?= htmlspecialchars($user['note'] ?? '') ?>"
                                data-role="<?= htmlspecialchars($user['role'] ?? 'user') ?>"
                                data-daily-limit="<?= $dailyLimit ?>"
                                data-status="<?= $isActive ? '1' : '0' ?>"
                                data-token="<?= htmlspecialchars($user['token'] ?? '') ?>">
                                
                                <td>
                                    <div class="user-identity">
                                        <div class="user-avatar <?= $isAdmin ? 'avatar-admin' : 'avatar-user' ?>">
                                            <?= htmlspecialchars($firstLetter) ?>
                                        </div>
                                        <div class="user-meta">
                                            <div class="username">
                                                <?= htmlspecialchars($user['username']) ?>
                                                <?php if ($isCurrent): ?>
                                                    <span class="current-badge">您目前</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="note">
                                                <?= htmlspecialchars($user['note'] ?: ($isAdmin ? '系統最高權限' : '未設定備註')) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <?php if ($isAdmin): ?>
                                        <span class="badge badge-role-admin">
                                            <i data-lucide="shield" style="width: 13px; height: 13px;"></i> 管理員
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-role-user">
                                            <i data-lucide="user" style="width: 13px; height: 13px;"></i> 同事帳號
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($dailyLimit === 0 || $isAdmin): ?>
                                        <span class="badge badge-unlimited" title="不受全站每日 100 則限制">
                                            <i data-lucide="zap" style="width: 13px; height: 13px;"></i> 無限制（可破 100）
                                        </span>
                                    <?php elseif ($dailyLimit > 0): ?>
                                        <span class="badge badge-custom">
                                            <?= $dailyLimit ?> 則 / 日
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-default">
                                            跟隨全站 (<?= $systemDefaultLimit ?> 則)
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="token-cell">
                                        <span class="token-code" title="<?= htmlspecialchars($user['token']) ?>">
                                            <?= htmlspecialchars(substr($user['token'], 0, 8) . '...' . substr($user['token'], -6)) ?>
                                        </span>
                                        <button type="button" class="btn-icon" title="複製 API Key" onclick="copyToken('<?= htmlspecialchars($user['token']) ?>', this)">
                                            <i data-lucide="copy" style="width: 15px; height: 15px;"></i>
                                        </button>
                                        <button type="button" class="btn-icon" title="複製同事專用通知訊息" onclick="copyColleagueInfo('<?= htmlspecialchars($user['username']) ?>', '<?= htmlspecialchars($user['token']) ?>', <?= $dailyLimit ?>, this)">
                                            <i data-lucide="share-2" style="width: 15px; height: 15px;"></i>
                                        </button>
                                        <button type="button" class="btn-icon" title="查看 API 呼叫範例與完整資訊" onclick="showColleagueApiGuide('<?= htmlspecialchars($user['username']) ?>', '<?= htmlspecialchars($user['token']) ?>', <?= $dailyLimit ?>)">
                                            <i data-lucide="terminal" style="width: 15px; height: 15px;"></i>
                                        </button>
                                    </div>
                                </td>

                                <td>
                                    <span style="font-weight: 600; color: #a9b1d6;"><?= number_format((int)$user['upload_count']) ?></span>
                                    <span style="font-size: 0.8rem; color: #7f88b2;">筆</span>
                                </td>

                                <td>
                                    <?php if ($isActive): ?>
                                        <span class="badge badge-active">啟用中</span>
                                    <?php else: ?>
                                        <span class="badge badge-disabled">已停用</span>
                                    <?php endif; ?>
                                </td>

                                <td style="color: #7f88b2; font-size: 0.82rem;">
                                    <?= htmlspecialchars(substr($user['created_at'] ?? '', 0, 16) ?: '—') ?>
                                </td>

                                <td style="text-align: right;">
                                    <div class="actions-cell" style="justify-content: flex-end;">
                                        <button type="button" class="btn-icon" title="編輯帳號" onclick="openEditUserModal(<?= $uid ?>)">
                                            <i data-lucide="edit-3" style="width: 16px; height: 16px;"></i>
                                        </button>
                                        <button type="button" class="btn-icon" title="重新產生 API Token" onclick="regenerateToken(<?= $uid ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                            <i data-lucide="refresh-cw" style="width: 16px; height: 16px;"></i>
                                        </button>
                                        <?php if (!$isCurrent): ?>
                                            <button type="button" class="btn-icon danger" title="刪除帳號" onclick="deleteUser(<?= $uid ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                                <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 模態視窗：新增帳號 -->
    <div id="modal-add-user" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i data-lucide="user-plus" style="color: #7aa2f7;"></i> 新增同事帳號</h3>
                <button type="button" class="modal-close" onclick="closeModal('modal-add-user')">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form id="form-add-user" onsubmit="submitCreateUser(event)">
                <div class="modal-body">
                    <div class="form-item">
                        <label for="add-username">帳號名稱 (Username) <span style="color: #f7768e;">*</span></label>
                        <input type="text" id="add-username" name="username" class="form-control" placeholder="例如：colleague_alex 或 alex.chen" required autocomplete="off">
                        <div class="tip">供同事登入或識別使用，需為唯一識別名稱。</div>
                    </div>

                    <div class="form-item">
                        <label for="add-password">登入密碼 <span style="color: #f7768e;">*</span></label>
                        <input type="password" id="add-password" name="password" class="form-control" placeholder="至少 6 位字元密碼" required autocomplete="new-password">
                        <div class="tip">同事亦可使用此密碼登入後台瀏覽或上傳。</div>
                    </div>

                    <div class="form-item">
                        <label for="add-note">同事備註說明</label>
                        <input type="text" id="add-note" name="note" class="form-control" placeholder="例如：行銷部 Alex、專案串接帳號">
                    </div>

                    <div class="form-item">
                        <label>角色權限</label>
                        <div class="radio-pills">
                            <label class="radio-pill-label selected" id="role-pill-user">
                                <input type="radio" name="role" value="user" checked onchange="updatePillHighlight()">
                                <span>同事帳號 (一般上傳者)</span>
                            </label>
                            <label class="radio-pill-label" id="role-pill-admin">
                                <input type="radio" name="role" value="admin" onchange="updatePillHighlight()">
                                <span>系統管理員</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-item">
                        <label>每日上傳上限配額</label>
                        <div class="radio-pills" style="margin-bottom: 8px;">
                            <label class="radio-pill-label selected" id="limit-pill-unlimited">
                                <input type="radio" name="daily_limit_type" value="unlimited" checked onchange="toggleLimitCustomInput('add')">
                                <span>⚡ 無限制（突破 100 則限制）</span>
                            </label>
                            <label class="radio-pill-label" id="limit-pill-custom">
                                <input type="radio" name="daily_limit_type" value="custom" onchange="toggleLimitCustomInput('add')">
                                <span>自訂上限</span>
                            </label>
                            <label class="radio-pill-label" id="limit-pill-default">
                                <input type="radio" name="daily_limit_type" value="default" onchange="toggleLimitCustomInput('add')">
                                <span>跟隨全站 (<?= $systemDefaultLimit ?> 則)</span>
                            </label>
                        </div>
                        <div id="add-custom-limit-wrap" style="display: none; margin-top: 8px;">
                            <input type="number" name="custom_daily_limit" class="form-control" placeholder="請輸入每日允許上限次數 (例如 500)" min="1">
                        </div>
                        <div class="tip">選擇「無限制」時，該同事持專屬 API Key 上傳可完全突破 100 則次數限制。</div>
                    </div>

                    <div class="form-item">
                        <label>帳號狀態</label>
                        <div class="radio-pills">
                            <label class="radio-pill-label selected">
                                <input type="radio" name="status" value="1" checked>
                                <span>啟用中</span>
                            </label>
                            <label class="radio-pill-label">
                                <input type="radio" name="status" value="0">
                                <span>停用</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('modal-add-user')">取消</button>
                    <button type="submit" class="btn-primary" id="btn-save-new-user">建立並生成 API Key</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 模態視窗：編輯帳號 -->
    <div id="modal-edit-user" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i data-lucide="edit-3" style="color: #7aa2f7;"></i> 編輯帳號設定</h3>
                <button type="button" class="modal-close" onclick="closeModal('modal-edit-user')">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form id="form-edit-user" onsubmit="submitEditUser(event)">
                <input type="hidden" id="edit-id" name="id">
                <div class="modal-body">
                    <div class="form-item">
                        <label for="edit-username">帳號名稱</label>
                        <input type="text" id="edit-username" class="form-control" readonly style="opacity: 0.7; cursor: not-allowed;">
                    </div>

                    <div class="form-item">
                        <label for="edit-note">備註說明</label>
                        <input type="text" id="edit-note" name="note" class="form-control">
                    </div>

                    <div class="form-item">
                        <label for="edit-password">重設新密碼 (留空則保持原密碼)</label>
                        <input type="password" id="edit-password" name="new_password" class="form-control" placeholder="留空不修改密碼" autocomplete="new-password">
                    </div>

                    <div class="form-item">
                        <label>角色權限</label>
                        <div class="radio-pills">
                            <label class="radio-pill-label" id="edit-role-user">
                                <input type="radio" name="role" value="user">
                                <span>同事帳號</span>
                            </label>
                            <label class="radio-pill-label" id="edit-role-admin">
                                <input type="radio" name="role" value="admin">
                                <span>系統管理員</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-item">
                        <label>每日上傳上限配額</label>
                        <div class="radio-pills" style="margin-bottom: 8px;">
                            <label class="radio-pill-label" id="edit-limit-unlimited">
                                <input type="radio" name="daily_limit_type" value="unlimited" onchange="toggleLimitCustomInput('edit')">
                                <span>⚡ 無限制（突破 100 則限制）</span>
                            </label>
                            <label class="radio-pill-label" id="edit-limit-custom">
                                <input type="radio" name="daily_limit_type" value="custom" onchange="toggleLimitCustomInput('edit')">
                                <span>自訂上限</span>
                            </label>
                            <label class="radio-pill-label" id="edit-limit-default">
                                <input type="radio" name="daily_limit_type" value="default" onchange="toggleLimitCustomInput('edit')">
                                <span>跟隨全站 (<?= $systemDefaultLimit ?> 則)</span>
                            </label>
                        </div>
                        <div id="edit-custom-limit-wrap" style="display: none; margin-top: 8px;">
                            <input type="number" id="edit-custom-daily-limit" name="custom_daily_limit" class="form-control" placeholder="請輸入每日允許上限次數" min="1">
                        </div>
                    </div>

                    <div class="form-item">
                        <label>帳號狀態</label>
                        <div class="radio-pills">
                            <label class="radio-pill-label" id="edit-status-1">
                                <input type="radio" name="status" value="1">
                                <span>啟用中</span>
                            </label>
                            <label class="radio-pill-label" id="edit-status-0">
                                <input type="radio" name="status" value="0">
                                <span>停用</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <button type="button" class="btn-secondary" onclick="copyFromEditUserModal()" title="依目前設定產生並複製同事通知訊息">
                            <i data-lucide="share-2" style="width: 14px; height: 14px;"></i> 複製同事通知訊息
                        </button>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" class="btn-secondary" onclick="closeModal('modal-edit-user')">取消</button>
                        <button type="submit" class="btn-primary">儲存變更</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 模態視窗：帳號建立成功提示 -->
    <div id="modal-created-success" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i data-lucide="check-circle" style="color: #9ece6a;"></i> 同事帳號建立成功</h3>
                <button type="button" class="modal-close" onclick="closeModal('modal-created-success')">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="modal-body">
                <p id="created-success-desc" style="margin-top: 0; color: #a9b1d6;">已為同事成功建立專用帳號與 API Key。</p>
                
                <div class="form-item">
                    <label>帳號名稱</label>
                    <input type="text" id="created-username" class="form-control" readonly>
                </div>

                <div class="form-item">
                    <label>同事專用 API Key (Token)</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="created-token" class="form-control" readonly style="font-family: monospace; color: #7dcfff;">
                        <button type="button" class="btn-primary" style="flex-shrink: 0;" onclick="copyCreatedToken()">
                            <i data-lucide="copy"></i> 複製
                        </button>
                    </div>
                </div>

                <div class="form-item">
                    <label>快速傳送給同事的訊息範本</label>
                    <div class="code-block">
                        <pre id="created-colleague-message"></pre>
                        <button type="button" class="code-copy-btn" onclick="copyCreatedMessage()">
                            <i data-lucide="copy" style="width: 13px; height: 13px;"></i> 複製訊息
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-primary" onclick="closeModalAndReload('modal-created-success')">完成並重整清單</button>
            </div>
        </div>
    </div>

    <!-- 模態視窗：同事 API 介接指南 -->
    <div id="modal-api-guide" class="modal-overlay">
        <div class="modal-box" style="max-width: 620px;">
            <div class="modal-header">
                <h3><i data-lucide="terminal" style="color: #7aa2f7;"></i> 同事 API 上傳調用方式</h3>
                <button type="button" class="modal-close" onclick="closeModal('modal-api-guide')">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="modal-body">
                <p style="margin-top: 0; color: #a9b1d6; font-size: 0.9rem;">
                    同事只要在上傳請求時帶上其專屬的 <strong>API Key</strong>，即可套用專屬上傳配額（如自訂上限或突破限制），支援多種帶入方式：
                </p>

                <div class="form-item" style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label style="margin: 0; font-weight: 600; color: #7dcfff;">快速傳送給同事的訊息範本</label>
                        <button type="button" class="code-copy-btn" style="position: static;" onclick="copyColleagueGuideMessage()">
                            <i data-lucide="copy" style="width: 13px; height: 13px;"></i> 複製整段訊息
                        </button>
                    </div>
                    <div class="code-block">
                        <pre id="guide-full-colleague-message"></pre>
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <div style="font-size: 0.85rem; font-weight: 600; color: #e0af68; margin-bottom: 6px;">
                        方式 1：HTTP Authorization Header (標準建議)
                    </div>
                    <div class="code-block">
                        <pre id="guide-curl-header">curl -X POST "<?= htmlspecialchars($apiUploadUrl) ?>" \
  -H "Authorization: Bearer <span class="guide-placeholder-token">YOUR_API_KEY</span>" \
  -F "file=@your_image.jpg"</pre>
                        <button type="button" class="code-copy-btn" onclick="copySnippet('guide-curl-header')">
                            <i data-lucide="copy" style="width: 13px; height: 13px;"></i> 複製
                        </button>
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <div style="font-size: 0.85rem; font-weight: 600; color: #7aa2f7; margin-bottom: 6px;">
                        方式 2：X-API-Key 自訂 Header
                    </div>
                    <div class="code-block">
                        <pre id="guide-curl-xapikey">curl -X POST "<?= htmlspecialchars($apiUploadUrl) ?>" \
  -H "X-API-Key: <span class="guide-placeholder-token">YOUR_API_KEY</span>" \
  -F "file=@your_image.jpg"</pre>
                        <button type="button" class="code-copy-btn" onclick="copySnippet('guide-curl-xapikey')">
                            <i data-lucide="copy" style="width: 13px; height: 13px;"></i> 複製
                        </button>
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <div style="font-size: 0.85rem; font-weight: 600; color: #9ece6a; margin-bottom: 6px;">
                        方式 3：POST 表單參數 token
                    </div>
                    <div class="code-block">
                        <pre id="guide-curl-form">curl -X POST "<?= htmlspecialchars($apiUploadUrl) ?>" \
  -F "token=<span class="guide-placeholder-token">YOUR_API_KEY</span>" \
  -F "file=@your_image.jpg"</pre>
                        <button type="button" class="code-copy-btn" onclick="copySnippet('guide-curl-form')">
                            <i data-lucide="copy" style="width: 13px; height: 13px;"></i> 複製
                        </button>
                    </div>
                </div>

                <div>
                    <div style="font-size: 0.85rem; font-weight: 600; color: #bb9af7; margin-bottom: 6px;">
                        Python 程式調用範例
                    </div>
                    <div class="code-block">
                        <pre id="guide-python">import requests

url = "<?= htmlspecialchars($apiUploadUrl) ?>"
headers = {"Authorization": "Bearer <span class="guide-placeholder-token">YOUR_API_KEY</span>"}
files = {"file": open("photo.png", "rb")}

response = requests.post(url, headers=headers, files=files)
print(response.json())</pre>
                        <button type="button" class="code-copy-btn" onclick="copySnippet('guide-python')">
                            <i data-lucide="copy" style="width: 13px; height: 13px;"></i> 複製
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-primary" onclick="closeModal('modal-api-guide')">我知道了</button>
            </div>
        </div>
    </div>

    <?php renderAdminFooter(); ?>
    <script src="/static/js/lucide.min.js"></script>
    <script src="/static/js/admin.js?v=<?= time(); ?>"></script>
    <script>
        const API_UPLOAD_URL = <?= json_encode($apiUploadUrl, JSON_UNESCAPED_SLASHES) ?>;
        const SYSTEM_DEFAULT_LIMIT = <?= (int)$systemDefaultLimit ?>;
        let currentGuideMessage = '';

        function getQuotaDescription(dailyLimit) {
            const limit = parseInt(dailyLimit, 10);
            if (limit === 0) {
                return `無限制（已開放突破 ${SYSTEM_DEFAULT_LIMIT} 則限制）`;
            } else if (limit > 0) {
                return `每日 ${limit.toLocaleString()} 則（自訂上限）`;
            } else {
                return `跟隨全站預設（每日 ${SYSTEM_DEFAULT_LIMIT.toLocaleString()} 則）`;
            }
        }

        function formatColleagueMessage(username, token, dailyLimit) {
            const quotaDesc = getQuotaDescription(dailyLimit);
            return `888 BOX 同事專用帳號資訊：\n\n帳號：${username}\nAPI Key (Token)：${token}\n上傳端點：${API_UPLOAD_URL}\n每日配額：${quotaDesc}\n\n上傳範例 (cURL)：\ncurl -X POST "${API_UPLOAD_URL}" -H "Authorization: Bearer ${token}" -F "file=@your_image.jpg"`;
        }

        function copyColleagueInfo(username, token, dailyLimit, btnEl) {
            const msg = formatColleagueMessage(username, token, dailyLimit);
            if (navigator.clipboard) {
                navigator.clipboard.writeText(msg).then(() => {
                    if (typeof UI !== 'undefined') UI.showNotification(`已複製「${username}」的同事專用通知訊息`, 'success');
                    if (btnEl) {
                        btnEl.classList.add('is-copied');
                        setTimeout(() => btnEl.classList.remove('is-copied'), 1500);
                    }
                });
            }
        }

        function copyColleagueGuideMessage() {
            if (navigator.clipboard && currentGuideMessage) {
                navigator.clipboard.writeText(currentGuideMessage).then(() => {
                    if (typeof UI !== 'undefined') UI.showNotification('已複製給同事的通知訊息', 'success');
                });
            }
        }

        function copyFromEditUserModal() {
            const id = document.getElementById('edit-id').value;
            const row = document.querySelector(`.user-row[data-id="${id}"]`);
            const username = document.getElementById('edit-username').value;
            const token = row ? row.dataset.token : '';

            const limitTypeRadio = document.querySelector('#modal-edit-user input[name="daily_limit_type"]:checked');
            const limitType = limitTypeRadio ? limitTypeRadio.value : 'unlimited';
            let dailyLimit = 0;
            if (limitType === 'custom') {
                dailyLimit = parseInt(document.getElementById('edit-custom-daily-limit').value, 10) || 100;
            } else if (limitType === 'default') {
                dailyLimit = -1;
            }

            const msg = formatColleagueMessage(username, token, dailyLimit);
            if (navigator.clipboard) {
                navigator.clipboard.writeText(msg).then(() => {
                    if (typeof UI !== 'undefined') UI.showNotification(`已依目前設定複製「${username}」的通知訊息`, 'success');
                });
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
        });

        // 搜尋使用者（200ms 防抖 Debounce）
        let searchTimer;
        function filterUsers() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                const query = (document.getElementById('user-search-input').value || '').trim().toLowerCase();
                const rows = document.querySelectorAll('.user-row');
                rows.forEach(row => {
                    const username = (row.dataset.username || '').toLowerCase();
                    const note = (row.dataset.note || '').toLowerCase();
                    if (username.includes(query) || note.includes(query)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }, 200);
        }

        // 開關模態視窗
        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.add('active');
                if (window.lucide) lucide.createIcons();
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) modal.classList.remove('active');
        }

        function closeModalAndReload(id) {
            closeModal(id);
            window.location.reload();
        }

        // 點擊背景關閉
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) overlay.classList.remove('active');
            });
        });

        // 鍵盤無障礙快捷鍵：Escape 關閉 Modal、/ 快速聚焦搜尋
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
            }
            if (e.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
                e.preventDefault();
                document.getElementById('user-search-input')?.focus();
            }
        });

        // 複製 Token（含按鈕微反饋動畫）
        function copyToken(token, btn) {
            const onCopied = () => {
                if (typeof UI !== 'undefined') UI.showNotification('API Token 已複製到剪貼簿', 'success');
                if (btn) {
                    btn.classList.add('is-copied');
                    setTimeout(() => btn.classList.remove('is-copied'), 1500);
                }
            };
            if (navigator.clipboard) {
                navigator.clipboard.writeText(token).then(onCopied);
            } else {
                const el = document.createElement('textarea');
                el.value = token;
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
                onCopied();
            }
        }

        // 切換自訂額度輸入框顯示
        function toggleLimitCustomInput(mode) {
            const wrap = document.getElementById(`${mode}-custom-limit-wrap`);
            const selected = document.querySelector(`input[name="daily_limit_type"]:checked`)?.value;
            if (wrap) {
                wrap.style.display = (selected === 'custom') ? 'block' : 'none';
            }
            updatePillHighlight();
        }

        function updatePillHighlight() {
            document.querySelectorAll('.radio-pill-label').forEach(label => {
                const radio = label.querySelector('input[type="radio"]');
                if (radio && radio.checked) {
                    label.classList.add('selected');
                } else if (radio) {
                    label.classList.remove('selected');
                }
            });
        }

        // 打開新增帳號 Modal
        function openAddUserModal() {
            document.getElementById('form-add-user').reset();
            toggleLimitCustomInput('add');
            openModal('modal-add-user');
        }

        function getCsrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        }

        // 提交新增帳號
        async function submitCreateUser(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-save-new-user');
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="spin-icon" style="width: 15px; height: 15px;"></i> 建立中...';
            btn.setAttribute('aria-busy', 'true');
            if (window.lucide) lucide.createIcons();

            const formData = new FormData(e.target);
            formData.append('action', 'create');
            formData.append('csrf_token', getCsrfToken());

            try {
                const res = await fetch('users.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    closeModal('modal-add-user');
                    // 顯示建立成功視窗
                    document.getElementById('created-username').value = data.data.username;
                    document.getElementById('created-token').value = data.data.token;
                    
                    const dailyLimit = data.data.daily_limit;
                    const message = formatColleagueMessage(data.data.username, data.data.token, dailyLimit);
                    document.getElementById('created-colleague-message').textContent = message;

                    const quotaSummary = (dailyLimit === 0) 
                        ? `此帳號為無限制上傳（已突破每日 ${SYSTEM_DEFAULT_LIMIT} 則限制）。` 
                        : (dailyLimit > 0 ? `此帳號每日上限為 ${parseInt(dailyLimit, 10).toLocaleString()} 則。` : `此帳號跟隨全站預設配額。`);
                    const descEl = document.getElementById('created-success-desc');
                    if (descEl) descEl.textContent = `已為同事成功建立專用帳號與 API Key，${quotaSummary}`;

                    openModal('modal-created-success');
                } else {
                    if (typeof UI !== 'undefined') UI.showNotification(data.message || '建立失敗', 'error');
                }
            } catch (err) {
                console.error(err);
                if (typeof UI !== 'undefined') UI.showNotification('網路或系統回應錯誤', 'error');
            } finally {
                btn.disabled = false;
                btn.removeAttribute('aria-busy');
                btn.textContent = '建立並生成 API Key';
            }
        }

        function copyCreatedToken() {
            const token = document.getElementById('created-token').value;
            copyToken(token);
        }

        function copyCreatedMessage() {
            const text = document.getElementById('created-colleague-message').textContent;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    if (typeof UI !== 'undefined') UI.showNotification('已複製給同事的通知訊息', 'success');
                });
            }
        }

        // 編輯帳號
        function openEditUserModal(id) {
            const row = document.querySelector(`.user-row[data-id="${id}"]`);
            if (!row) return;

            document.getElementById('edit-id').value = id;
            document.getElementById('edit-username').value = row.dataset.username;
            document.getElementById('edit-note').value = row.dataset.note || '';
            document.getElementById('edit-password').value = '';

            const role = row.dataset.role || 'user';
            const roleRadio = document.querySelector(`#modal-edit-user input[name="role"][value="${role}"]`);
            if (roleRadio) roleRadio.checked = true;

            const dailyLimit = parseInt(row.dataset.dailyLimit, 10);
            if (dailyLimit === 0) {
                document.querySelector('#modal-edit-user input[name="daily_limit_type"][value="unlimited"]').checked = true;
                document.getElementById('edit-custom-limit-wrap').style.display = 'none';
            } else if (dailyLimit > 0) {
                document.querySelector('#modal-edit-user input[name="daily_limit_type"][value="custom"]').checked = true;
                document.getElementById('edit-custom-daily-limit').value = dailyLimit;
                document.getElementById('edit-custom-limit-wrap').style.display = 'block';
            } else {
                document.querySelector('#modal-edit-user input[name="daily_limit_type"][value="default"]').checked = true;
                document.getElementById('edit-custom-limit-wrap').style.display = 'none';
            }

            const status = row.dataset.status === '1' ? '1' : '0';
            const statusRadio = document.querySelector(`#modal-edit-user input[name="status"][value="${status}"]`);
            if (statusRadio) statusRadio.checked = true;

            updatePillHighlight();
            openModal('modal-edit-user');
        }

        // 提交編輯
        async function submitEditUser(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.dataset.origText = btn.textContent;
                btn.innerHTML = '<i data-lucide="loader-2" class="spin-icon" style="width: 15px; height: 15px;"></i> 儲存中...';
                if (window.lucide) lucide.createIcons();
            }

            const formData = new FormData(e.target);
            formData.append('action', 'update');
            formData.append('csrf_token', getCsrfToken());

            try {
                const res = await fetch('users.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    if (typeof UI !== 'undefined') UI.showNotification(data.message, 'success');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    if (typeof UI !== 'undefined') UI.showNotification(data.message || '更新失敗', 'error');
                }
            } catch (err) {
                console.error(err);
                if (typeof UI !== 'undefined') UI.showNotification('連線出錯，請稍後重試', 'error');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = btn.dataset.origText || '儲存設定';
                }
            }
        }

        // 重新產生 Token
        function regenerateToken(id, username) {
            const confirmMsg = `確定要為帳號「${username}」重新產生 API Key 嗎？\n重新產生後，該同事使用舊 Token 的呼叫將立即失效！`;
            if (typeof UI !== 'undefined' && UI.createConfirmDialog) {
                UI.createConfirmDialog(confirmMsg, () => doRegenerateToken(id), {
                    type: 'warning',
                    confirmText: '確定重新產生'
                });
            } else if (confirm(confirmMsg)) {
                doRegenerateToken(id);
            }
        }

        async function doRegenerateToken(id) {
            const formData = new FormData();
            formData.append('action', 'regenerate_token');
            formData.append('id', id);
            formData.append('csrf_token', getCsrfToken());

            try {
                const res = await fetch('users.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    if (typeof UI !== 'undefined') UI.showNotification(data.message, 'success');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    if (typeof UI !== 'undefined') UI.showNotification(data.message || '產生失敗', 'error');
                }
            } catch (err) {
                console.error(err);
                if (typeof UI !== 'undefined') UI.showNotification('系統出錯', 'error');
            }
        }

        // 刪除帳號
        function deleteUser(id, username) {
            const confirmMsg = `確定要刪除帳號「${username}」嗎？此動作無法復原！`;
            if (typeof UI !== 'undefined' && UI.createConfirmDialog) {
                UI.createConfirmDialog(confirmMsg, () => doDeleteUser(id), {
                    type: 'danger',
                    confirmText: '確認刪除'
                });
            } else if (confirm(confirmMsg)) {
                doDeleteUser(id);
            }
        }

        async function doDeleteUser(id) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            formData.append('csrf_token', getCsrfToken());

            try {
                const res = await fetch('users.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    if (typeof UI !== 'undefined') UI.showNotification(data.message, 'success');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    if (typeof UI !== 'undefined') UI.showNotification(data.message || '刪除失敗', 'error');
                }
            } catch (err) {
                console.error(err);
                if (typeof UI !== 'undefined') UI.showNotification('系統出錯', 'error');
            }
        }

        // 同事 API 呼叫指南 Modal
        function openApiGuideModal() {
            document.querySelectorAll('.guide-placeholder-token').forEach(el => el.textContent = 'YOUR_API_KEY');
            currentGuideMessage = formatColleagueMessage('YOUR_USERNAME', 'YOUR_API_KEY', 0);
            const msgEl = document.getElementById('guide-full-colleague-message');
            if (msgEl) msgEl.textContent = currentGuideMessage;
            openModal('modal-api-guide');
        }

        function showColleagueApiGuide(username, token, dailyLimit = 0) {
            document.querySelectorAll('.guide-placeholder-token').forEach(el => el.textContent = token);
            currentGuideMessage = formatColleagueMessage(username, token, dailyLimit);
            const msgEl = document.getElementById('guide-full-colleague-message');
            if (msgEl) msgEl.textContent = currentGuideMessage;
            openModal('modal-api-guide');
        }

        function copySnippet(elementId) {
            const text = document.getElementById(elementId).innerText;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    if (typeof UI !== 'undefined') UI.showNotification('已複製指令範例', 'success');
                });
            }
        }
    </script>
</body>
</html>
