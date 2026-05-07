<?php
session_start();
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: /admin/login.php');
    exit;
}

require_once '../config/database.php';
$db = Database::getInstance();
$pdo = $db->getConnection();

// 自動資料庫遷移：為 images 表加上 title 和 description 欄位
try {
    $pdo->exec("ALTER TABLE images ADD COLUMN title VARCHAR(255) DEFAULT ''");
} catch (PDOException $e) {}
try {
    $pdo->exec("ALTER TABLE images ADD COLUMN description TEXT DEFAULT ''");
} catch (PDOException $e) {}

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
        .actions { margin-top: auto; display: flex; justify-content: space-between; align-items: center; gap: 10px; }
        .btn-copy { background: #4CAF50; color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; font-weight: bold; flex: 1; }
        .btn-edit { background: #2196F3; color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; font-weight: bold; flex: 1; }
        .btn-delete { background: #f44336; color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; font-weight: bold; flex: 1; }
        .btn-copy:hover { background: #45a049; }
        .btn-edit:hover { background: #1976D2; }
        .btn-delete:hover { background: #d32f2f; }
        .empty-state { text-align: center; color: #888; padding: 80px; font-size: 20px; grid-column: 1 / -1; background: #1e1e1e; border-radius: 12px; border: 1px dashed #555; }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); backdrop-filter: blur(5px); }
        .modal-content { background-color: #1e1e1e; margin: 10% auto; padding: 30px; border: 1px solid #444; width: 90%; max-width: 500px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        .modal-header { margin-bottom: 20px; font-size: 20px; font-weight: bold; color: #fff; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; color: #aaa; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; background: #121212; border: 1px solid #333; color: #fff; border-radius: 6px; box-sizing: border-box; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; }
    </style>
</head>
<body>
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">編輯影片資訊</div>
            <input type="hidden" id="editId">
            <div class="form-group">
                <label>影片標題</label>
                <input type="text" id="editTitle">
            </div>
            <div class="form-group">
                <label>影片描述</label>
                <textarea id="editDescription" rows="4"></textarea>
            </div>
            <div class="modal-actions">
                <button class="btn-copy" style="background:#555; flex:none;" onclick="closeModal()">取消</button>
                <button class="btn-copy" style="flex:none; min-width:100px;" onclick="saveMetadata()">儲存變更</button>
            </div>
        </div>
    </div>

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
                        <p><strong>標題：</strong> <span class="v-title"><?= htmlspecialchars($video['title'] ?: $video['path']) ?></span></p>
                        <p><strong>描述：</strong> <span class="v-desc"><?= htmlspecialchars($video['description'] ?: '無') ?></span></p>
                        <p><strong>大小：</strong> <?= number_format(floatval($video['size']) / 1024 / 1024, 2) ?> MB</p>
                        <p><strong>上傳時間：</strong> <?= htmlspecialchars($video['created_at']) ?></p>
                    </div>
                    <div class="actions">
                        <button class="btn-copy" onclick="copyUrl('<?= htmlspecialchars($video['url']) ?>')">複製</button>
                        <button class="btn-edit" onclick="openEditModal(<?= $video['id'] ?>)">編輯</button>
                        <button class="btn-delete" onclick="deleteVideo(<?= $video['id'] ?>, '<?= htmlspecialchars($video['path']) ?>')">刪除</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function openEditModal(id) {
            const card = document.getElementById('video-' + id);
            const title = card.querySelector('.v-title').textContent;
            const desc = card.querySelector('.v-desc').textContent;
            
            document.getElementById('editId').value = id;
            document.getElementById('editTitle').value = title;
            document.getElementById('editDescription').value = desc === '無' ? '' : desc;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function saveMetadata() {
            const id = document.getElementById('editId').value;
            const title = document.getElementById('editTitle').value;
            const desc = document.getElementById('editDescription').value;
            
            const formData = new FormData();
            formData.append('id', id);
            formData.append('title', title);
            formData.append('description', desc);
            
            fetch('/api_edit_video.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.result === 'success') {
                    const card = document.getElementById('video-' + id);
                    card.querySelector('.v-title').textContent = title;
                    card.querySelector('.v-desc').textContent = desc || '無';
                    closeModal();
                    alert('更新成功，RSS 已同步！');
                } else {
                    alert('更新失敗：' + data.message);
                }
            })
            .catch(err => {
                alert('網路錯誤');
            });
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) closeModal();
        }

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