<?php
session_start();
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $config = Database::getConfig($pdo);
    
    // 檢查是否需要登入限制
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
                <h2>拖曳多部影片檔案至此</h2>
                <p>或點擊選擇檔案 (支援 mp4, webm, mov, mkv)</p>
                <input type="file" id="videoInput" accept="video/*" multiple style="display:none;">
            </div>
            
            <div id="queueArea" style="display:none; text-align: left;">
                <h3 style="margin-top:0; margin-bottom:20px; border-bottom:1px solid var(--border); padding-bottom:10px;">影片上傳佇列 (依序上傳)</h3>
                <div id="fileList"></div>
                
                <div class="action-buttons" style="margin-top: 30px;">
                    <button id="cancelBtn" class="btn secondary">清空列表</button>
                    <button id="uploadBtn" class="btn primary">開始依序上傳</button>
                </div>
            </div>
        </div>

        <div class="rss-notify" style="margin-top: 20px;">
            <p>🚀 上傳的影片將會自動加入至 Podcast 訂閱中！</p>
            <a href="/storage/podcast.xml" target="_blank" class="rss-link">🎧 點此查看 Podcast RSS 連結 (XML)</a>
        </div>
    </main>

    <script src="static/js/video_app.js?v=<?php echo time(); ?>"></script>
</body>
</html>