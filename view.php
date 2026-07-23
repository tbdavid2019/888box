<?php
session_start();
require_once 'config/database.php';
require_once 'config/theme_helper.php';
require_once 'config/upload.php';


function outputInlinePdf($asset, $config) {
    $fileName = basename($asset['path'] ?: ($asset['title'] ?: 'document.pdf'));
    $fileSize = (int)($asset['size'] ?? 0);

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . addcslashes($fileName, '"\\') . '"');
    header('X-Content-Type-Options: nosniff');
    if ($fileSize > 0) {
        header('Content-Length: ' . $fileSize);
    }

    $storage = $asset['storage'] ?? 'local';
    if ($storage === 'local') {
        $localPath = __DIR__ . '/' . ltrim($asset['path'], '/');
        if (!is_file($localPath)) {
            http_response_code(404);
            exit('PDF 檔案不存在');
        }

        readfile($localPath);
        exit;
    }

    $url = resolveAssetOriginUrl($asset, $config);
    if (!$url) {
        http_response_code(404);
        exit('PDF 連結不存在');
    }

    if (function_exists('curl_init')) {
        $output = fopen('php://output', 'wb');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $output,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 120,
        ]);
        $success = curl_exec($ch);
        $hasError = ($success === false);
        curl_close($ch);
        fclose($output);

        if ($hasError) {
            http_response_code(502);
            exit('PDF 預覽載入失敗');
        }

        exit;
    }

    $content = @file_get_contents($url);
    if ($content === false) {
        http_response_code(502);
        exit('PDF 預覽載入失敗');
    }

    echo $content;
    exit;
}

$token = trim($_GET['token'] ?? '');
$id = trim($_GET['id'] ?? '');

if (empty($token) && empty($id)) {
    http_response_code(404);
    die("缺少資源 Token");
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $config = Database::getConfig($pdo);
    
    // 1. 撈取資源：優先用 share_token，支援完整 Token 與短 Token (如 /v/498dd84a)
    if (!empty($token)) {
        $stmt = $pdo->prepare("SELECT * FROM images WHERE share_token = ? OR share_token LIKE ?");
        $stmt->execute([$token, $token . '%']);
    } else {
        // 向下相容：將來產生的舊聯接（將來可移除）
        // 為防止序號枚舉，直接用 id 無 token 的請求一律回 404
        http_response_code(404);
        die("資源不存在");
    }
    $asset = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$asset) {
        http_response_code(404);
        die("找不到該資源");
    }

    // 內部統一用 id 作會話鍵（已知專屬）
    $id = $asset['id'];
    
    // 2. 處理密碼驗證
    $isAuthorized = empty($asset['password']);
    if (!$isAuthorized && isset($_SESSION['auth_asset_' . $id])) {
        $isAuthorized = true;
    }
    
    if (!$isAuthorized && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if (password_verify($_POST['password'], $asset['password'])) {
            $_SESSION['auth_asset_' . $id] = true;
            $isAuthorized = true;
        } else {
            $error = "密碼錯誤";
        }
    }
    
    // 3. 增加瀏覽次數 (僅在授權後或無密碼時)
    if ($isAuthorized && !isset($_GET['pdf_inline'])) {
        $pdo->prepare("UPDATE images SET view_count = view_count + 1 WHERE id = ?")->execute([$id]);
    }

    if ($isAuthorized && isset($_GET['pdf_inline'])) {
        outputInlinePdf($asset, $config);
    }
    
} catch (Exception $e) {
    die($e->getMessage());
}

// 判定資源類型
$url = getMaskedUrl($asset['url'], $asset['path']);
$mime = $asset['mime_type'] ?: '';
$ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));

$type = 'other';
if ($asset['is_audio'] == 1 || strpos($mime, 'audio/') !== false || in_array($ext, ['mp3', 'wav', 'aac', 'ogg', 'm4a', 'flac'])) {
    $type = 'audio';
} elseif ($asset['is_video'] == 1 || strpos($mime, 'video/') !== false || in_array($ext, ['mp4', 'webm', 'mov', 'mkv'])) {
    $type = 'video';
} elseif (strpos($mime, 'image/') !== false || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
    $type = 'image';
} elseif ($ext === 'pdf') {
    $type = 'pdf';
} elseif ($ext === 'epub') {
    $type = 'epub';
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($asset['title'] ?: '資源檢視') ?> - 888box</title>
    <link rel="shortcut icon" href="/static/favicon.svg">
    <link rel="stylesheet" href="/static/css/portal.css">
    <?php renderThemeStyles($pdo); ?>
    <style>
        body {
            padding: 32px 20px 56px;
        }

        .view-container {
            width: min(100%, 980px);
            margin: 64px auto 20px;
            padding: clamp(22px, 4vw, 48px);
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.065), rgba(255, 255, 255, 0.025));
            border: 1px solid var(--border, rgba(255, 255, 255, 0.12));
            border-radius: 28px;
            box-shadow: 0 24px 70px rgba(4, 6, 14, 0.28);
            backdrop-filter: blur(20px);
        }

        .asset-header {
            display: grid;
            gap: 12px;
            margin-bottom: 24px;
        }

        .asset-header-top {
            position: fixed;
            top: 14px;
            left: 50%;
            transform: translateX(-50%);
            width: min(calc(100% - 24px), 960px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            padding: 10px 18px;
            background: rgba(14, 17, 28, 0.88);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 999px;
            box-shadow: 0 10px 36px rgba(0, 0, 0, 0.45);
        }

        .asset-breadcrumb {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .breadcrumb-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--accent-cyan, #7dcfff);
            text-decoration: none;
            padding: 5px 12px;
            background: rgba(125, 207, 255, 0.1);
            border: 1px solid rgba(125, 207, 255, 0.25);
            border-radius: 999px;
            transition: all 0.2s ease;
        }

        .breadcrumb-link:hover {
            background: rgba(125, 207, 255, 0.22);
            border-color: rgba(125, 207, 255, 0.5);
            transform: translateY(-1px);
        }

        .breadcrumb-link svg {
            width: 14px;
            height: 14px;
        }

        .breadcrumb-sep {
            color: rgba(255, 255, 255, 0.3);
            font-size: 0.75rem;
        }

        .breadcrumb-tag {
            color: var(--text-secondary, rgba(255, 255, 255, 0.6));
            font-size: 0.78rem;
        }

        .btn-header-upload {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            font-size: 0.78rem;
            font-weight: 700;
            color: #1a1b26;
            background: linear-gradient(135deg, #7dcfff 0%, #7aa2f7 100%);
            border: none;
            border-radius: 999px;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(125, 207, 255, 0.2);
        }

        .btn-header-upload:hover {
            transform: translateY(-1px) scale(1.02);
            box-shadow: 0 6px 18px rgba(125, 207, 255, 0.35);
        }

        .btn-header-upload svg {
            width: 14px;
            height: 14px;
        }

        /* 檔案上傳引導 CTA 橫幅 */
        .user-guide-cta {
            margin-top: 24px;
            padding: 18px 22px;
            background: linear-gradient(135deg, rgba(122, 162, 247, 0.1) 0%, rgba(187, 154, 247, 0.08) 100%);
            border: 1px solid rgba(122, 162, 247, 0.25);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .cta-left {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
            flex: 1;
        }

        .cta-icon-box {
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: rgba(122, 162, 247, 0.18);
            border: 1px solid rgba(122, 162, 247, 0.3);
            color: #7aa2f7;
            flex: 0 0 auto;
        }

        .cta-icon-box svg {
            width: 22px;
            height: 22px;
        }

        .cta-text-content strong {
            display: block;
            color: #fff;
            font-size: 0.92rem;
            margin-bottom: 3px;
        }

        .cta-text-content p {
            margin: 0;
            color: var(--text-secondary, rgba(255, 255, 255, 0.65));
            font-size: 0.78rem;
            line-height: 1.4;
        }

        .btn-cta-upload {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            font-size: 0.84rem;
            font-weight: 700;
            color: #1a1b26;
            background: linear-gradient(135deg, #7dcfff 0%, #7aa2f7 100%);
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.2s ease;
            white-space: nowrap;
            box-shadow: 0 4px 14px rgba(122, 162, 247, 0.25);
        }

        .btn-cta-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(122, 162, 247, 0.4);
            filter: brightness(1.08);
        }

        .btn-cta-upload svg {
            width: 16px;
            height: 16px;
        }

        .portal-footer {
            margin-top: 30px;
            margin-bottom: 20px;
            text-align: center;
            color: var(--text-secondary, rgba(255, 255, 255, 0.5));
            font-size: 0.8rem;
            line-height: 1.6;
        }

        .portal-footer a {
            color: var(--accent-cyan, rgba(125, 207, 255, 0.85));
            text-decoration: none;
            font-weight: 600;
        }

        .portal-footer a:hover {
            text-decoration: underline;
            color: #7dcfff;
        }

        .asset-title {
            max-width: 760px;
            margin: 0;
            color: var(--text-white, #fff);
            font-size: clamp(1.55rem, 3.6vw, 2.35rem);
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.04em;
            overflow-wrap: anywhere;
        }

        .asset-meta {
            display: flex;
            gap: 8px !important;
            align-items: center;
            flex-wrap: wrap;
            margin: 4px 0 0;
            padding: 0;
            border: 0;
            color: var(--text-secondary, rgba(255, 255, 255, 0.58)) !important;
            font-size: 0.78rem;
        }

        .meta-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 10px;
            color: inherit !important;
            background: rgba(255, 255, 255, 0.045);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
        }

        .meta-item svg {
            width: 14px;
            height: 14px;
            color: var(--accent-blue, #7aa2f7);
        }

        .viewer-box {
            width: 100%;
            min-height: clamp(260px, 48vw, 520px);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px;
            background: #080a11;
            border: 1px solid rgba(255, 255, 255, 0.09);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
        }

        /* 嵌入與分享代碼面板 */
        .embed-panel {
            margin-top: 20px;
            padding: 18px 20px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            backdrop-filter: blur(12px);
        }

        .embed-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .embed-title-text {
            margin: 0;
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--text-primary, #c0caf5);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .embed-title-text svg {
            width: 16px;
            height: 16px;
            color: var(--accent-cyan, #7dcfff);
        }

        .embed-tabs {
            display: flex;
            gap: 6px;
            margin-bottom: 10px;
            overflow-x: auto;
            padding-bottom: 2px;
        }

        .embed-tab {
            padding: 5px 12px;
            font-size: 0.76rem;
            font-weight: 600;
            color: var(--text-secondary, rgba(255, 255, 255, 0.6));
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .embed-tab:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .embed-tab.active {
            background: rgba(125, 207, 255, 0.15);
            border-color: rgba(125, 207, 255, 0.4);
            color: #7dcfff;
        }

        .embed-input-box {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .embed-input-box input {
            flex: 1;
            min-width: 0;
            padding: 9px 12px;
            font-size: 0.8rem;
            font-family: monospace;
            color: #c0caf5;
            background: rgba(10, 12, 20, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 9px;
            outline: none;
        }

        .embed-input-box input:focus {
            border-color: var(--accent-cyan, #7dcfff);
        }

        .btn-copy-embed {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 9px 15px;
            font-size: 0.78rem;
            font-weight: 700;
            color: #1a1b26;
            background: #7dcfff;
            border: none;
            border-radius: 9px;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-copy-embed:hover {
            background: #9ece6a;
            transform: translateY(-1px);
        }

        .btn-copy-embed svg {
            width: 14px;
            height: 14px;
        }

        .viewer-box img {
            max-width: 100%;
            max-height: min(72vh, 720px);
            height: auto;
            display: block;
            object-fit: contain;
        }

        .viewer-box video {
            width: 100%;
            max-height: min(72vh, 720px);
        }

        .viewer-box iframe, .viewer-box object, .viewer-box embed, #epub-viewer {
            width: 100%;
            height: 600px;
            border: none;
            display: block;
        }

        .password-gate {
            text-align: center;
            padding: 60px 20px;
        }

        .password-gate input {
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #444;
            background: #222;
            color: #fff;
            width: 240px;
            margin-bottom: 20px;
        }

        .password-gate button {
            padding: 12px 30px;
            border-radius: 8px;
            border: none;
            background: #007aff;
            color: #fff;
            font-weight: bold;
            cursor: pointer;
        }

        .asset-description {
            margin-top: 20px;
            padding: 16px 18px;
            background: rgba(255, 255, 255, 0.035);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-left: 2px solid var(--accent-cyan, #7dcfff);
            border-radius: 10px 14px 14px 10px;
        }

        .description-label {
            display: block;
            margin-bottom: 6px;
            color: var(--text-secondary, rgba(255, 255, 255, 0.58)) !important;
            font-size: 0.7rem;
            letter-spacing: 0.12em;
        }

        .description-copy {
            color: var(--text-primary, #c0caf5) !important;
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .asset-actions {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(260px, 0.9fr);
            gap: 14px;
            margin-top: 24px;
        }

        .download-box {
            margin: 0;
            text-align: left;
        }

        .btn-download {
            width: 100%;
            min-height: 66px;
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 12px 16px;
            color: #fff;
            text-decoration: none;
            border-radius: 14px;
            font-weight: bold;
            transition: transform 0.2s ease, filter 0.2s ease;
        }

        .btn-download:hover {
            filter: brightness(1.08);
        }

        .download-icon {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            color: currentColor;
            background: rgba(255, 255, 255, 0.14);
            border-radius: 10px;
        }

        .download-copy {
            min-width: 0;
            display: grid;
            flex: 1;
            gap: 3px;
        }

        .download-copy strong {
            color: inherit !important;
            font-size: 0.95rem;
        }

        .download-copy small {
            color: rgba(255, 255, 255, 0.72) !important;
            font-size: 0.7rem;
            font-weight: 400;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .report-panel {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            background: rgba(224, 175, 104, 0.06);
            border: 1px solid rgba(224, 175, 104, 0.2);
            border-radius: 14px;
        }

        .report-copy {
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .report-copy strong,
        .report-copy small {
            display: block;
            color: inherit !important;
        }

        .report-copy strong {
            margin-bottom: 3px;
            font-size: 0.78rem;
        }

        .report-copy small {
            color: var(--text-secondary, rgba(255, 255, 255, 0.56)) !important;
            font-size: 0.68rem;
            line-height: 1.4;
        }

        .report-icon {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            color: var(--accent-orange, #e0af68) !important;
            background: rgba(224, 175, 104, 0.12);
            border-radius: 9px;
        }

        .btn-report {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex: 0 0 auto;
            padding: 9px 11px;
            color: var(--accent-orange, #e0af68) !important;
            background: transparent;
            border: 1px solid rgba(224, 175, 104, 0.32);
            border-radius: 9px;
            cursor: pointer;
            font: inherit;
            font-size: 0.72rem;
            transition: background 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
        }

        .btn-report:hover {
            background: rgba(224, 175, 104, 0.14);
            border-color: rgba(224, 175, 104, 0.62);
            transform: translateY(-1px);
        }

        .btn-report:focus-visible,
        .btn-download:focus-visible {
            outline: 3px solid rgba(125, 207, 255, 0.45);
            outline-offset: 3px;
        }

        .btn-report:disabled {
            cursor: wait;
            opacity: 0.65;
            transform: none;
        }

        .report-toast {
            position: fixed;
            z-index: 20;
            left: 50%;
            bottom: 24px;
            width: min(calc(100% - 32px), 420px);
            padding: 13px 16px;
            color: var(--text-primary, #c0caf5) !important;
            background: rgba(22, 24, 36, 0.94);
            border: 1px solid var(--border, rgba(255, 255, 255, 0.16));
            border-radius: 12px;
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.28);
            opacity: 0;
            transform: translate(-50%, 12px);
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .report-toast.is-visible {
            opacity: 1;
            transform: translate(-50%, 0);
        }

        .report-toast.is-error {
            border-color: rgba(255, 99, 99, 0.42);
        }

        #epub-viewer {
            width: 100%;
            height: 600px;
            background: #fff;
            color: #000;
        }

        @media (max-width: 680px) {
            body {
                padding: 12px 12px 40px;
            }

            .view-container {
                margin: 8px auto;
                border-radius: 20px;
            }

            .asset-actions {
                grid-template-columns: 1fr;
            }

            .report-panel {
                align-items: flex-start;
            }
        }
        
        /* Audio Preview Specific Styles */
        .audio-preview-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 40px;
            background: #111;
            border-radius: 12px;
        }
        .cd-disk {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: radial-gradient(circle, #333 15%, #000 40%, #222 75%, #000 100%);
            border: 5px solid #222;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5), inset 0 0 10px rgba(255,255,255,0.2);
            position: relative;
            margin-bottom: 30px;
            animation: spin 8s linear infinite;
            animation-play-state: paused;
            transition: transform 0.5s;
        }
        .cd-disk::before {
            content: '';
            position: absolute;
            top: 5%; left: 5%; right: 5%; bottom: 5%;
            border-radius: 50%;
            border: 2px dashed rgba(255,255,255,0.08);
        }
        .cd-disk::after {
            content: '';
            position: absolute;
            top: 20%; left: 20%; right: 20%; bottom: 20%;
            border-radius: 50%;
            border: 1px dashed rgba(255,255,255,0.05);
        }
        .cd-center {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #1a1b26;
            border: 4px solid var(--accent-blue, #7aa2f7);
            box-shadow: 0 0 5px rgba(0,0,0,0.5);
            z-index: 2;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        #audioPlayer {
            width: 100%;
            max-width: 400px;
            border-radius: 30px;
        }
    </style>
    <?php renderCustomTrackingCode($pdo); ?>
</head>
<body>
    <div class="view-container">
        <?php if (!$isAuthorized): ?>
            <div class="password-gate">
                <div style="margin-bottom: 20px; display: flex; justify-content: center;"><i data-lucide="lock" style="width: 52px; height: 52px; color: #7aa2f7;"></i></div>
                <h2 style="margin-bottom: 20px;">此資源受密碼保護</h2>
                <form method="POST">
                    <input type="password" name="password" placeholder="請輸入存取密碼" required autofocus>
                    <?php if (isset($error)): ?>
                        <p style="color: #ff3b30; margin-bottom: 15px;"><?= $error ?></p>
                    <?php endif; ?><br>
                    <button type="submit">驗證並進入</button>
                </form>
            </div>
        <?php else: ?>
            <?php 
            $customTitle = trim($asset['title'] ?? '');
            $hasTitle = ($customTitle !== '');
            ?>
            <div class="asset-header">
                <div class="asset-header-top">
                    <nav class="asset-breadcrumb" aria-label="麵包屑導覽">
                        <a href="/" class="breadcrumb-link" title="返回 888box 首頁門戶">
                            <i data-lucide="box"></i>
                            <span>888box 門戶</span>
                        </a>
                        <span class="breadcrumb-sep">/</span>
                        <span class="breadcrumb-tag">公開資源</span>
                    </nav>
                    <a href="/" class="btn-header-upload" title="前往免費上傳與託管檔案">
                        <i data-lucide="upload-cloud"></i>
                        <span>我也要上傳檔案</span>
                    </a>
                </div>
                <?php if ($hasTitle): ?>
                    <h1 class="asset-title"><?= htmlspecialchars($customTitle) ?></h1>
                <?php endif; ?>
                <div class="asset-meta">
                    <span class="meta-item"><i data-lucide="clock"></i>時間 <?= $asset['created_at'] ?></span>
                    <?php if ($type === 'image'): ?>
                        <span class="meta-item" id="meta-dimensions" style="display: none;"><i data-lucide="maximize-2"></i>尺寸 <span id="image-dimensions"></span></span>
                    <?php endif; ?>
                    <span class="meta-item"><i data-lucide="hard-drive"></i>大小 <?= number_format($asset['size'] / 1024 / 1024, 2) ?> MB</span>
                    <span class="meta-item"><i data-lucide="eye"></i>瀏覽 <?= $asset['view_count'] ?> 次</span>
                </div>
            </div>

            <div class="viewer-box">
                <?php if ($type === 'image'): ?>
                    <img src="<?= htmlspecialchars($url) ?>" alt="Image">
                <?php elseif ($type === 'video'): ?>
                    <video src="<?= htmlspecialchars($url) ?>" controls autoplay></video>
                <?php elseif ($type === 'audio'): ?>
                    <div class="audio-preview-container">
                        <div class="cd-disk" id="cdDisk">
                            <div class="cd-center"></div>
                        </div>
                        <audio id="audioPlayer" src="<?= htmlspecialchars($url) ?>" controls autoplay></audio>
                    </div>
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            const audio = document.getElementById('audioPlayer');
                            const cd = document.getElementById('cdDisk');
                            if (audio && cd) {
                                audio.addEventListener('play', () => {
                                    cd.style.animationPlayState = 'running';
                                });
                                audio.addEventListener('pause', () => {
                                    cd.style.animationPlayState = 'paused';
                                });
                                audio.addEventListener('ended', () => {
                                    cd.style.animationPlayState = 'paused';
                                });
                            }
                        });
                    </script>
                <?php elseif ($type === 'pdf'): ?>
                    <embed src="/view.php?token=<?= urlencode((string)($asset['share_token'] ?? '')) ?>&pdf_inline=1" type="application/pdf" width="100%" height="600px">
                <?php elseif ($type === 'epub'): ?>
                    <div id="epub-viewer"></div>
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/epub.js/0.3.88/epub.min.js"></script>
                    <script>
                        var book = ePub("<?= $url ?>");
                        var rendition = book.renderTo("epub-viewer", {
                            width: "100%",
                            height: "600px",
                            flow: "scrolled",
                            manager: "default"
                        });
                        rendition.display();
                    </script>
                <?php else: ?>
                    <div style="text-align: center; color: #888;">
                        <p>此類型檔案暫不支援直接預覽</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 嵌入與外鏈代碼面板 -->
            <div class="embed-panel">
                <div class="embed-header-row">
                    <h3 class="embed-title-text"><i data-lucide="code-2"></i> 嵌入與外鏈代碼</h3>
                </div>
                <div class="embed-tabs">
                    <button type="button" class="embed-tab active" data-type="url" onclick="selectEmbedType('url', this)">直連網址</button>
                    <button type="button" class="embed-tab" data-type="markdown" onclick="selectEmbedType('markdown', this)">Markdown</button>
                    <button type="button" class="embed-tab" data-type="html" onclick="selectEmbedType('html', this)">HTML</button>
                    <button type="button" class="embed-tab" data-type="bbcode" onclick="selectEmbedType('bbcode', this)">BBCode</button>
                </div>
                <div class="embed-input-box">
                    <input type="text" id="embedCodeInput" readonly value="<?= htmlspecialchars($url) ?>">
                    <button type="button" class="btn-copy-embed" id="btnCopyEmbed" onclick="copyEmbedCode(this)">
                        <i data-lucide="copy"></i>
                        <span>複製</span>
                    </button>
                </div>
            </div>

            <?php if (!empty($asset['description'])): ?>
                <div class="asset-description">
                    <span class="description-label">資源說明</span>
                    <p class="description-copy"><?= nl2br(htmlspecialchars($asset['description'])) ?></p>
                </div>
            <?php endif; ?>

            <div class="asset-actions">
                <div class="download-box">
                    <a href="<?= htmlspecialchars($url) ?>" download="<?= htmlspecialchars(basename($asset['path'])) ?>" class="btn-download">
                        <span class="download-icon"><i data-lucide="download"></i></span>
                        <span class="download-copy">
                            <strong>立即下載</strong>
                            <small><?= htmlspecialchars(basename($asset['path'])) ?></small>
                        </span>
                        <i data-lucide="arrow-up-right"></i>
                    </a>
                </div>

                <div class="report-panel">
                    <div class="report-copy">
                        <span class="report-icon"><i data-lucide="flag"></i></span>
                        <span>
                            <strong>內容有問題？</strong>
                            <small>發現不當內容，請通知管理員</small>
                        </span>
                    </div>
                    <button type="button" class="btn-report" onclick="reportAsset(<?= $id ?>, this)">
                        舉報 <i data-lucide="chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- 引導使用者上傳與託管的 CTA 橫幅 -->
            <div class="user-guide-cta">
                <div class="cta-left">
                    <div class="cta-icon-box">
                        <i data-lucide="sparkles"></i>
                    </div>
                    <div class="cta-text-content">
                        <strong>想要託管或分享你的圖片、影片與文件？</strong>
                        <p>888box 提供免費、高效、安全的資產中心，任何人皆可免登入直接上傳！</p>
                    </div>
                </div>
                <a href="/" class="btn-cta-upload">
                    <i data-lucide="plus-circle"></i>
                    <span>前往免費上傳</span>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <footer class="portal-footer">
        &copy; <?= date('Y') ?> 888box. All rights reserved. <br>
        Created by <a href="https://david888.com" target="_blank">DAVID888</a> | 
        <a href="/skill.php" target="_blank" style="display: inline-flex; align-items: center; gap: 4px;"><i data-lucide="bot" style="width: 15px; height: 15px;"></i> AI Agent Skills</a>
    </footer>

    <script src="/static/js/lucide.min.js"></script>
    <script>
        const embedTemplates = {
            url: <?= json_encode($url) ?>,
            markdown: <?= json_encode('![' . ($customTitle ?: 'image') . '](' . $url . ')') ?>,
            html: <?= json_encode('<img src="' . $url . '" alt="' . ($customTitle ?: 'image') . '">' ) ?>,
            bbcode: <?= json_encode('[img]' . $url . '[/img]') ?>
        };

        function selectEmbedType(type, btn) {
            document.querySelectorAll('.embed-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const input = document.getElementById('embedCodeInput');
            if (input && embedTemplates[type]) {
                input.value = embedTemplates[type];
            }
        }

        function copyEmbedCode(btn) {
            const input = document.getElementById('embedCodeInput');
            if (!input) return;
            input.select();
            navigator.clipboard.writeText(input.value).then(() => {
                showReportToast('已複製嵌入代碼！');
                const span = btn.querySelector('span');
                if (span) {
                    const originalText = span.textContent;
                    span.textContent = '已複製!';
                    setTimeout(() => span.textContent = originalText, 2000);
                }
            }).catch(() => {
                showReportToast('複製失敗，請手動複製', true);
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();

            const img = document.querySelector('.viewer-box img');
            if (img) {
                const updateDimensions = () => {
                    if (img.naturalWidth && img.naturalHeight) {
                        const metaDim = document.getElementById('meta-dimensions');
                        const dimSpan = document.getElementById('image-dimensions');
                        if (metaDim && dimSpan) {
                            dimSpan.textContent = `${img.naturalWidth} × ${img.naturalHeight} px`;
                            metaDim.style.display = 'inline-flex';
                        }
                    }
                };
                if (img.complete) {
                    updateDimensions();
                } else {
                    img.addEventListener('load', updateDimensions);
                }
            }
        });

        function showReportToast(message, isError = false) {
            const toast = document.createElement('div');
            toast.className = 'report-toast' + (isError ? ' is-error' : '');
            toast.setAttribute('role', 'status');
            toast.textContent = message;
            document.body.appendChild(toast);

            requestAnimationFrame(() => toast.classList.add('is-visible'));
            setTimeout(() => {
                toast.classList.remove('is-visible');
                setTimeout(() => toast.remove(), 220);
            }, 3600);
        }

        function reportAsset(id, button) {
            if (!confirm('確定要舉報此資源嗎？管理員將會收到通知。')) return;

            const originalContent = button.innerHTML;
            button.disabled = true;
            button.textContent = '送出中…';

            fetch('/api_report.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + id
            })
            .then(res => res.json().then(data => ({ ok: res.ok, data })))
            .then(data => {
                if (data.ok && data.data.result === 'success') {
                    showReportToast(data.data.message || '已收到您的舉報，感謝您的回饋。');
                } else {
                    showReportToast('舉報失敗：' + (data.data.message || '請稍後再試'), true);
                }
            })
            .catch(() => showReportToast('網路錯誤，請稍後再試', true))
            .finally(() => {
                button.disabled = false;
                button.innerHTML = originalContent;
                if (window.lucide) lucide.createIcons();
            });
        }
    </script>
    <!-- Webtalk Chat Widget -->
    <script async src="https://webtalk-nine.vercel.app/webtalk-chat.js" data-webtalk-scope="origin"></script>
</body>
</html>
