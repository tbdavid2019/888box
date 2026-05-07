<?php
session_start();
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $config = Database::getConfig($pdo);
    
    if ($config && isset($config['login_restriction']) && 
        filter_var($config['login_restriction'], FILTER_VALIDATE_BOOLEAN) && 
        (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin'])) {
        header('Location: /admin/login.php');
        exit();
    }
} catch (Exception $e) {
    die($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>圖片託管中心 - 888box</title>
    <link rel="shortcut icon" href="/static/favicon.svg">
    <link rel="stylesheet" href="/static/css/upload/base.css">
    <link rel="stylesheet" href="/static/css/upload/components.css">
    <link rel="stylesheet" href="/static/css/upload/uploader.css">
    <style>
        .password-field { margin-top: 15px; }
        .password-field input { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #444; background: #222; color: #fff; }
    </style>
</head>
<body>
    <div class="upload-container">
        <header>
            <h1>🖼️ 圖片託管中心</h1>
            <nav>
                <a href="/">🏠 返回首頁</a>
                <a href="/admin/index.php">⚙️ 圖片管理</a>
            </nav>
        </header>

        <div id="imageUploadBox" class="upload-box">
            <div class="upload-prompt">
                <span class="icon">📁</span>
                <p>拖曳圖片至此或點擊上傳</p>
                <input type="file" id="imageInput" accept="image/*" multiple style="display:none;">
            </div>
        </div>

        <div id="progressContainer" class="progress-container" style="display:none;">
            <div id="progressBar" class="progress-bar">0%</div>
        </div>

        <div id="imagePreviewContainer" class="preview-container" style="display:none;">
            <div class="preview-header">
                <span id="imageCounter">0 / 0</span>
                <div class="nav-btns">
                    <button id="prevButton">◀</button>
                    <button id="nextButton">▶</button>
                </div>
            </div>
            <div id="imagePreview" class="preview-image"></div>
            
            <div class="image-meta">
                <div class="meta-row">
                    <span>原始: <span id="originalWidth">0</span>x<span id="originalHeight">0</span> (<span id="originalSize">0</span>)</span>
                    <span>壓縮後: <span id="compressedWidth">0</span>x<span id="compressedHeight">0</span> (<span id="compressedSize">0</span>)</span>
                </div>
                <div class="quality-control">
                    <label>壓縮率: <span id="qualityOutput">60</span>%</label>
                    <input type="range" id="qualityInput" min="10" max="100" value="60">
                </div>
                <!-- 密碼欄位 -->
                <div class="password-field">
                    <input type="password" id="imagePassword" placeholder="設定存取密碼 (選填)">
                </div>
            </div>
        </div>

        <div class="link-outputs">
            <div class="link-group">
                <label>圖片網址</label>
                <input type="text" id="imageUrl" readonly placeholder="上傳後自動產生">
            </div>
        </div>
    </div>

    <script type="module" src="/static/js/main.js" data-max-file-size="<?= $config['max_file_size'] ?>"></script>
    <script src="//at.alicdn.com/t/c/font_4623353_hb4c04qfi4u.js"></script>
</body>
</html>
