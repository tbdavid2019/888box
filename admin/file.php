<?php
session_start();
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: /admin/login.php');
    exit;
}

require_once '../config/database.php';
$db = Database::getInstance();
$pdo = $db->getConnection();

// 撈取文件 (排除圖片和影片)
// 我們利用 mime_type 欄位來過濾，或者排除常見影片後綴
$stmt = $pdo->prepare("SELECT * FROM images WHERE 
    (mime_type LIKE 'application/%' OR mime_type LIKE 'text/%') 
    AND url NOT LIKE '%.mp4' AND url NOT LIKE '%.webm' AND url NOT LIKE '%.mov' AND url NOT LIKE '%.mkv'
    ORDER BY id DESC");
$stmt->execute();
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文件管理後台 - 888box</title>
    <link rel="shortcut icon" href="/static/favicon.svg">
    <style>
        body { background-color: #1a1a1a; color: #eee; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #333; padding-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; color: #fff; }
        .nav-links a { color: #34c759; text-decoration: none; margin-left: 15px; font-weight: bold; }
        .nav-links a:hover { text-decoration: underline; }
        .file-list { max-width: 1200px; margin: 0 auto; }
        .file-item { background: #242424; border: 1px solid #333; border-radius: 12px; padding: 20px; margin-bottom: 15px; display: flex; align-items: center; gap: 20px; transition: border-color 0.2s; }
        .file-item:hover { border-color: #444; }
        .file-icon { font-size: 2rem; width: 60px; height: 60px; background: #333; display: flex; align-items: center; justify-content: center; border-radius: 12px; }
        .file-info { flex: 1; min-width: 0; }
        .file-info h3 { margin: 0 0 5px 0; font-size: 18px; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .file-info p { margin: 0; font-size: 14px; color: #888; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; margin-left: 5px; }
        .badge-report { background: #ff3b30; color: #fff; }
        .badge-pass { background: #ff9500; color: #fff; }
        .actions { display: flex; gap: 10px; }
        .btn { padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-weight: bold; transition: opacity 0.2s; }
        .btn:hover { opacity: 0.8; }
        .btn-view { background: #34c759; color: #fff; }
        .btn-delete { background: #ff3b30; color: #fff; }
        .empty-state { text-align: center; color: #555; padding: 100px; font-size: 1.2rem; }
    </style>
</head>
<body>
    <div class="header" style="max-width: 1200px; margin: 0 auto 30px auto;">
        <h1>📂 文件專屬管理後台</h1>
        <div class="nav-links">
            <a href="/">🏠 返回首頁</a>
            <a href="/admin/index.php">🖼️ 圖片管理</a>
            <a href="/admin/video.php">🎬 影片管理</a>
            <a href="/upload_file.php">➕ 上傳文件</a>
        </div>
    </div>

    <div class="file-list">
        <?php if (empty($files)): ?>
            <div class="empty-state">尚未上傳任何文件</div>
        <?php else: ?>
            <?php foreach ($files as $file): ?>
                <div class="file-item" id="file-<?= $file['id'] ?>">
                    <div class="file-icon">📄</div>
                    <div class="file-info">
                        <h3>
                            <?= htmlspecialchars($file['title'] ?: basename($file['path'])) ?>
                            <?php if ($file['report_count'] > 0): ?>
                                <span class="badge badge-report">被檢舉 <?= $file['report_count'] ?> 次</span>
                            <?php endif; ?>
                            <?php if (!empty($file['password'])): ?>
                                <span class="badge badge-pass">密碼保護</span>
                            <?php endif; ?>
                        </h3>
                        <p>
                            大小: <?= number_format($file['size'] / 1024 / 1024, 2) ?> MB | 
                            MIME: <?= htmlspecialchars($file['mime_type'] ?: '未知') ?> | 
                            時間: <?= $file['created_at'] ?> |
                            瀏覽: <?= $file['view_count'] ?> 次
                        </p>
                    </div>
                    <div class="actions">
                        <a href="/view.php?id=<?= $file['id'] ?>" target="_blank" style="text-decoration:none;">
                            <button class="btn btn-view">預覽/分享</button>
                        </a>
                        <button class="btn btn-delete" onclick="deleteFile(<?= $file['id'] ?>, '<?= htmlspecialchars($file['path']) ?>')">刪除</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function deleteFile(id, path) {
            if (!confirm('確定要永久刪除此文件嗎？')) return;
            
            const formData = new FormData();
            formData.append('id', id);
            formData.append('path', path);
            
            fetch('/api_delete_file.php', { // 我們需要建立這個 API
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.result === 'success') {
                    const el = document.getElementById('file-' + id);
                    el.style.opacity = '0';
                    setTimeout(() => el.remove(), 300);
                } else {
                    alert('刪除失敗：' + data.message);
                }
            })
            .catch(err => alert('網路錯誤'));
        }
    </script>
</body>
</html>
