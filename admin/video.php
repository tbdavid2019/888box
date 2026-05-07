<?php
session_start();
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: /admin/login.php');
    exit;
}

require_once '../config/database.php';
$db = Database::getInstance();
$pdo = $db->getConnection();

// 撈取影片
$stmt = $pdo->prepare("SELECT * FROM images WHERE url LIKE '%.mp4' OR url LIKE '%.webm' OR url LIKE '%.mov' OR url LIKE '%.mkv' ORDER BY id DESC");
$stmt->execute();
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>影片管理後台 - 888box</title>
    <link rel="shortcut icon" href="/static/favicon.svg">
    <style>
        body { background-color: #2b2b2b; color: #eee; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #444; padding-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; color: #fff; }
        .nav-links a { color: #54a2c2; text-decoration: none; margin-left: 15px; font-weight: bold; }
        .nav-links a:hover { color: #f45873; }
        .video-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; max-width: 1400px; margin: 0 auto; }
        .video-card { background: #1e1e1e; border: 1px solid #444; border-radius: 12px; padding: 20px; display: flex; flex-direction: column; gap: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); transition: transform 0.2s; }
        .video-card:hover { transform: translateY(-5px); border-color: #666; }
        .video-card video { width: 100%; border-radius: 8px; background: #000; box-shadow: 0 2px 8px rgba(0,0,0,0.5); }
        .video-info { font-size: 14px; color: #aaa; background: #121212; padding: 15px; border-radius: 8px; border: 1px solid #333; }
        .video-info p { margin: 8px 0; word-break: break-all; }
        .video-info strong { color: #fff; }
        .actions { margin-top: auto; display: flex; justify-content: space-between; align-items: center; }
        .btn-copy { background: #4CAF50; color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn-copy:hover { background: #45a049; }
        .btn-delete { background: #f44336; color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn-delete:hover { background: #d32f2f; }
        .empty-state { text-align: center; color: #888; padding: 80px; font-size: 20px; grid-column: 1 / -1; background: #1e1e1e; border-radius: 12px; border: 1px dashed #555; }
    </style>
</head>
<body>
    <div class="header" style="max-width: 1400px; margin: 0 auto 30px auto;">
        <h1>🎬 影片專屬管理後台</h1>
        <div class="nav-links">
            <a href="/admin/">🖼️ 圖片管理後台</a>
            <a href="/upload_video.php">➕ 上傳新影片</a>
            <a href="/storage/podcast.xml" target="_blank">🎧 Podcast RSS</a>
        </div>
    </div>

    <div class="video-grid">
        <?php if (empty($videos)): ?>
            <div class="empty-state">目前沒有影片</div>
        <?php else: ?>
            <?php foreach ($videos as $video): ?>
                <div class="video-card" id="video-<?= $video['id'] ?>">
                    <video src="<?= htmlspecialchars($video['url']) ?>" controls preload="metadata"></video>
                    <div class="video-info">
                        <p><strong>標題：</strong> <?= htmlspecialchars($video['title'] ?: $video['path']) ?></p>
                        <p><strong>描述：</strong> <?= htmlspecialchars($video['description'] ?: '無') ?></p>
                        <p><strong>大小：</strong> <?= number_format($video['size'] / 1024 / 1024, 2) ?> MB</p>
                        <p><strong>上傳時間：</strong> <?= htmlspecialchars($video['created_at']) ?></p>
                    </div>
                    <div class="actions">
                        <button class="btn-copy" onclick="copyUrl('<?= htmlspecialchars($video['url']) ?>')">複製網址</button>
                        <button class="btn-delete" onclick="deleteVideo(<?= $video['id'] ?>, '<?= htmlspecialchars($video['path']) ?>')">刪除影片</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function copyUrl(url) {
            navigator.clipboard.writeText(url).then(() => {
                alert('網址已複製！');
            }).catch(err => {
                console.error('複製失敗', err);
            });
        }

        function deleteVideo(id, path) {
            if (!confirm('確定要刪除這部影片嗎？（將會同步從 RSS 中移除）')) return;
            
            const formData = new FormData();
            formData.append('id', id);
            formData.append('path', path);
            
            fetch('/api_delete_video.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.result === 'success') {
                    const card = document.getElementById('video-' + id);
                    if (card) {
                        card.style.opacity = '0';
                        setTimeout(() => card.remove(), 300);
                    }
                    alert('刪除成功，RSS 已同步更新！');
                } else {
                    alert('刪除失敗：' + data.message);
                }
            })
            .catch(err => {
                alert('網路錯誤');
                console.error(err);
            });
        }
    </script>
</body>
</html>