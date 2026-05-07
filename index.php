<?php
session_start();
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>888box - 統一資產管理</title>
    <link rel="shortcut icon" href="/static/favicon.svg">
    <link rel="stylesheet" href="/static/css/portal.css">
</head>
<body>
    <div class="header">
        <h1>888box</h1>
        <p>專業、高效、安全的個人資產中心</p>
    </div>

    <div class="bento-grid">
        <!-- 圖片中心 -->
        <a href="/upload_image.php" class="card card-images">
            <div>
                <div class="card-icon">🖼️</div>
                <h2 class="card-title">圖片託管</h2>
                <p class="card-desc">支援 WebP 高效壓縮與瀑布流展示</p>
            </div>
        </a>

        <!-- 影片中心 -->
        <a href="/upload_video.php" class="card card-videos">
            <div>
                <div class="card-icon">🎬</div>
                <h2 class="card-title">影片中心</h2>
                <p class="card-desc">自動提取 MetaData 與 Podcast RSS 同步</p>
            </div>
        </a>

        <!-- 文件中心 -->
        <a href="/upload_file.php" class="card card-files">
            <div>
                <div class="card-icon">📂</div>
                <h2 class="card-title">文件託管</h2>
                <p class="card-desc">支援 ZIP, PDF, Word 及 EPUB 線上閱讀</p>
            </div>
        </a>

        <!-- 系統管理 -->
        <a href="/admin/" class="card card-settings">
            <div>
                <div class="card-icon">⚙️</div>
                <h2 class="card-title">系統管理</h2>
                <p class="card-desc">配置儲存空間、SMTP 及查看舉報統計</p>
            </div>
        </a>
    </div>

    <footer style="margin-top: 60px; color: rgba(255,255,255,0.3); font-size: 0.8rem;">
        &copy; <?= date('Y') ?> 888box. All rights reserved.
    </footer>
</body>
</html>
