<?php
session_start();
require_once 'config/database.php';
$db = Database::getInstance();
$pdo = $db->getConnection();

// 獲取統計數據
$stats = [
    'image' => (int)$pdo->query("SELECT COUNT(*) FROM images WHERE url LIKE '%.jpg' OR url LIKE '%.jpeg' OR url LIKE '%.png' OR url LIKE '%.gif' OR url LIKE '%.webp' OR url LIKE '%.svg'")->fetchColumn(),
    'video' => (int)$pdo->query("SELECT COUNT(*) FROM images WHERE url LIKE '%.mp4' OR url LIKE '%.webm' OR url LIKE '%.mov' OR url LIKE '%.mkv'")->fetchColumn(),
];
$total = (int)$pdo->query("SELECT COUNT(*) FROM images")->fetchColumn();
$stats['file'] = $total - $stats['image'] - $stats['video'];
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>888box - 統一資產管理</title>
    <link rel="shortcut icon" href="/static/favicon.svg">
    <link rel="stylesheet" href="/static/css/portal.css">
    <style>
        .stats-badge {
            background: rgba(255,255,255,0.1);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            margin-top: 10px;
            display: inline-block;
        }
    </style>
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
                <div class="stats-badge"><?= $stats['image'] ?> 份資產</div>
            </div>
        </a>

        <!-- 影片中心 -->
        <a href="/upload_video.php" class="card card-videos">
            <div>
                <div class="card-icon">🎬</div>
                <h2 class="card-title">影片中心</h2>
                <p class="card-desc">自動提取 MetaData 與 Podcast RSS 同步</p>
                <div class="stats-badge"><?= $stats['video'] ?> 部影片</div>
            </div>
        </a>

        <!-- 文件中心 -->
        <a href="/upload_file.php" class="card card-files">
            <div>
                <div class="card-icon">📂</div>
                <h2 class="card-title">文件託管</h2>
                <p class="card-desc">支援 ZIP, PDF, Word 及 EPUB 線上閱讀</p>
                <div class="stats-badge"><?= $stats['file'] ?> 份文件</div>
            </div>
        </a>
    </div>


    <footer style="margin-top: 60px; color: rgba(255,255,255,0.3); font-size: 0.8rem;">
        &copy; <?= date('Y') ?> 888box. All rights reserved. <br>
        Created by <a href="https://david888.com" target="_blank" style="color: rgba(255,255,255,0.5); text-decoration: none; font-weight: bold;">DAVID888</a> | 
        <a href="/skill.php" target="_blank" style="color: rgba(255,255,255,0.5); text-decoration: none;">AI Agent Skills</a>
    </footer>

</body>
</html>
