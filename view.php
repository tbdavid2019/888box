<?php
session_start();
require_once 'config/database.php';

$id = $_GET['id'] ?? '';
if (empty($id)) {
    die("缺少資源 ID");
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $config = Database::getConfig($pdo);
    
    // 1. 撈取資源
    $stmt = $pdo->prepare("SELECT * FROM images WHERE id = ?");
    $stmt->execute([$id]);
    $asset = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$asset) {
        die("找不到該資源");
    }
    
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
    if ($isAuthorized) {
        $pdo->prepare("UPDATE images SET view_count = view_count + 1 WHERE id = ?")->execute([$id]);
    }
    
} catch (Exception $e) {
    die($e->getMessage());
}

// 判定資源類型
$url = $asset['url'];
$mime = $asset['mime_type'] ?: '';
$ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));

$type = 'other';
if (strpos($mime, 'image/') !== false || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
    $type = 'image';
} elseif (strpos($mime, 'video/') !== false || in_array($ext, ['mp4', 'webm', 'mov', 'mkv'])) {
    $type = 'video';
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
    <style>
        body { padding: 20px; }
        .view-container { 
            max-width: 900px; 
            margin: 40px auto; 
            background: rgba(255, 255, 255, 0.03); 
            border: 1px solid rgba(255, 255, 255, 0.1); 
            border-radius: 24px; 
            padding: 30px; 
            backdrop-filter: blur(20px);
        }
        .asset-title { font-size: 1.8rem; margin-bottom: 10px; color: #fff; }
        .asset-meta { color: rgba(255,255,255,0.4); font-size: 0.9rem; margin-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; }
        .viewer-box { width: 100%; min-height: 300px; display: flex; justify-content: center; align-items: center; background: #000; border-radius: 12px; overflow: hidden; }
        .viewer-box img { max-width: 100%; height: auto; }
        .viewer-box video { width: 100%; }
        .viewer-box iframe { width: 100%; height: 600px; border: none; }
        .password-gate { text-align: center; padding: 60px 20px; }
        .password-gate input { padding: 12px; border-radius: 8px; border: 1px solid #444; background: #222; color: #fff; width: 240px; margin-bottom: 20px; }
        .password-gate button { padding: 12px 30px; border-radius: 8px; border: none; background: #007aff; color: #fff; font-weight: bold; cursor: pointer; }
        .btn-report { margin-top: 40px; background: rgba(255, 59, 48, 0.1); color: #ff3b30; border: 1px solid rgba(255, 59, 48, 0.2); padding: 10px 20px; border-radius: 8px; cursor: pointer; transition: all 0.2s; }
        .btn-report:hover { background: #ff3b30; color: #fff; }
        .download-box { margin-top: 30px; text-align: center; }
        .btn-download { display: inline-block; padding: 15px 40px; background: #34c759; color: #fff; text-decoration: none; border-radius: 12px; font-weight: bold; }
        #epub-viewer { width: 100%; height: 600px; background: #fff; color: #000; }
    </style>
</head>
<body>
    <div class="view-container">
        <?php if (!$isAuthorized): ?>
            <div class="password-gate">
                <div style="font-size: 3rem; margin-bottom: 20px;">🔒</div>
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
            <h1 class="asset-title"><?= htmlspecialchars($asset['title'] ?: '未命名資源') ?></h1>
            <div class="asset-meta">
                <span>時間: <?= $asset['created_at'] ?></span> | 
                <span>大小: <?= number_format($asset['size'] / 1024 / 1024, 2) ?> MB</span> | 
                <span>瀏覽: <?= $asset['view_count'] ?> 次</span>
            </div>

            <div class="viewer-box">
                <?php if ($type === 'image'): ?>
                    <img src="<?= htmlspecialchars($url) ?>" alt="Image">
                <?php elseif ($type === 'video'): ?>
                    <video src="<?= htmlspecialchars($url) ?>" controls autoplay></video>
                <?php elseif ($type === 'pdf'): ?>
                    <iframe src="<?= htmlspecialchars($url) ?>"></iframe>
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

            <div class="download-box">
                <a href="<?= htmlspecialchars($url) ?>" download="<?= htmlspecialchars(basename($asset['path'])) ?>" class="btn-download">⬇️ 立即下載</a>
            </div>

            <p style="margin-top: 30px; color: rgba(255,255,255,0.6); line-height: 1.6;"><?= nl2br(htmlspecialchars($asset['description'] ?: '')) ?></p>

            <button class="btn-report" onclick="reportAsset(<?= $id ?>)">⚠️ 舉報此資源</button>
        <?php endif; ?>
    </div>

    <script>
        function reportAsset(id) {
            if (!confirm('確定要舉報此資源嗎？管理員將會收到通知。')) return;
            
            fetch('/api_report.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + id
            })
            .then(res => res.json())
            .then(data => {
                if (data.result === 'success') {
                    alert('已收到您的舉報，感謝您的回饋。');
                } else {
                    alert('舉報失敗：' + data.message);
                }
            })
            .catch(err => alert('網路錯誤'));
        }
    </script>
</body>
</html>
