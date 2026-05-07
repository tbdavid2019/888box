<?php
session_start();
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $config = Database::getConfig($pdo);
    
    // 检查是否需要登录限制
    if ($config && 
        isset($config['login_restriction']) && 
        filter_var($config['login_restriction'], FILTER_VALIDATE_BOOLEAN) && 
        (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin'])) {
        header('Location: /admin');
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
    <title>影片專屬上傳區 - 888box</title>
    <link rel="shortcut icon" href="static/favicon.svg">
    <link rel="stylesheet" href="static/css/video_ui.css?v=<?php echo time(); ?>">
</head>
<body>
    <header class="video-header">
        <h1>🎬 888box 影片託管中心</h1>
        <div class="nav-links">
            <a href="/">🖼️ 切換回圖片上傳</a>
            <a href="/admin/">⚙️ 管理後台</a>
        </div>
    </header>

    <main class="video-main">
        <div class="upload-panel" id="dropZone">
            <div id="uploadPrompt">
                <div class="icon-upload">📁</div>
                <h2>拖曳影片檔案至此</h2>
                <p>或點擊選擇檔案 (支援 mp4, webm, mov, mkv)</p>
                <input type="file" id="videoInput" accept="video/*" style="display:none;">
            </div>
            
            <div id="previewArea" style="display:none;">
                <video id="videoPlayer" controls></video>
                <div id="fileInfo"></div>
                <div class="progress-bar-container" id="progressContainer" style="display:none;">
                    <div class="progress-bar" id="progressBar">0%</div>
                </div>
                <div class="action-buttons">
                    <button id="cancelBtn" class="btn secondary">取消重選</button>
                    <button id="uploadBtn" class="btn primary">開始上傳影片</button>
                </div>
            </div>
        </div>

        <div class="result-panel" id="resultPanel" style="display:none;">
            <div class="success-icon">✅</div>
            <h3 class="success-title">影片上傳成功！</h3>
            
            <div class="result-item">
                <label>影片連結：</label>
                <input type="text" id="resVideoUrl" readonly>
                <button onclick="copyToClipboard('resVideoUrl')">複製</button>
            </div>
            <div class="result-item">
                <label>封面圖連結：</label>
                <input type="text" id="resThumbUrl" readonly>
                <button onclick="copyToClipboard('resThumbUrl')">複製</button>
            </div>
            
            <div class="metadata-grid">
                <div class="meta-item"><span>解析度：</span><b id="resRes"></b></div>
                <div class="meta-item"><span>影片時長：</span><b id="resDur"></b></div>
                <div class="meta-item"><span>檔案大小：</span><b id="resSize"></b></div>
            </div>
            
            <div class="rss-notify">
                <p>🚀 您的影片已自動加入至 Podcast 訂閱中！</p>
                <a href="/storage/podcast.xml" target="_blank" class="rss-link">🎧 點此查看 Podcast RSS 連結 (XML)</a>
            </div>
            
            <button id="newUploadBtn" class="btn primary mt-10">上傳下一部影片</button>
        </div>
    </main>

    <script src="static/js/video_app.js?v=<?php echo time(); ?>"></script>
</body>
</html>