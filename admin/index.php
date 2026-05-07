<?php
session_start();
header("Cache-Control: max-age=10800");

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    require 'login.php';
    exit;
}

require_once '../config/database.php';
require 'pagination.php';

$db = Database::getInstance();
$pdo = $db->getConnection();
$demoMode = ($_ENV['DEMO_MODE'] ?? 'false') === 'true';
$isDemoAutoLogin = isset($_SESSION['demo_auto_login']) && $_SESSION['demo_auto_login'];

// 处理登出
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_destroy();
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit();
}

// 获取配置和分页数据
$items_per_page = (int)($pdo->query("SELECT value FROM configs WHERE `key` = 'per_page'")->fetchColumn() ?: 20);
// 過濾掉影片
$total_rows = (int)($pdo->query("SELECT COUNT(id) FROM images WHERE url NOT LIKE '%.mp4' AND url NOT LIKE '%.webm' AND url NOT LIKE '%.mov' AND url NOT LIKE '%.mkv'")->fetchColumn() ?: 0);

$total_pages = max(1, ceil($total_rows / $items_per_page));
$current_page = min(max(1, $_GET['page'] ?? 1), $total_pages);

// 获取图片数据
$offset = ($current_page - 1) * $items_per_page;
$stmt = $pdo->prepare("SELECT * FROM images WHERE url NOT LIKE '%.mp4' AND url NOT LIKE '%.webm' AND url NOT LIKE '%.mov' AND url NOT LIKE '%.mkv' ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->execute([$items_per_page, $offset]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 处理AJAX请求
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'html' => renderImagesList($images),
        'pagination' => renderPagination($current_page, $total_pages),
        'currentPage' => $current_page,
        'totalPages' => $total_pages
    ]);
    exit;
}

// 渲染页面
$images_html = renderImagesList($images);
$pagination = renderPagination($current_page, $total_pages);
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理後台</title>
    <link rel="shortcut icon" href="/static/favicon.svg">
    <link rel="stylesheet" href="/static/css/admin.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/static/css/fancybox.min.css?v=<?php echo time(); ?>">
</head>
<body>
    <div style="width: 100%; text-align: center; margin-bottom: 20px; padding: 15px; background: linear-gradient(135deg, #3b82f6, #8b5cf6); border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
        <a href="/admin/video.php" style="color: white; font-size: 1.2rem; font-weight: bold; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 10px;">
            <span>🎬</span> 尋找影片管理？點此進入【影片專屬管理後台】 <span>👉</span>
        </a>
    </div>
    <div id="gallery" class="gallery"><?= $images_html ?></div>
    <div class="rightside">
        <a href="/admin/video.php" class="floating-link" title="影片管理" style="background-color: #3b82f6;">
            <svg class="icon" aria-hidden="true" style="fill: white;"><use xlink:href="#icon-Right-arrow"></use></svg>
        </a>
        <a href="/" class="floating-link" title="返回首頁">
            <svg class="icon" aria-hidden="true"><use xlink:href="#icon-home"></use></svg>
        </a>
        <a class="select-link" title="多選模式">
            <svg class="icon" aria-hidden="true"><use xlink:href="#icon-select"></use></svg>
        </a>
        <a href="#" class="settings-link" title="系統設定">
            <svg class="icon" aria-hidden="true"><use xlink:href="#icon-Setting"></use></svg>
        </a>
        <a href="?logout=true" class="logout-link" title="登出">
            <svg class="icon" aria-hidden="true"><use xlink:href="#icon-logout"></use></svg>
        </a>
        <a class="top-link" id="scroll-to-top" title="回到頂部">
            <svg class="icon" aria-hidden="true"><use xlink:href="#icon-top"></use></svg>
        </a>
        <span id="current-total-pages"><?= $current_page ?>/<?= $total_pages ?></span>
    </div>
    <div id="pagination" class="pagination"><?= $pagination ?></div>
    <div id="settings-modal" class="modal">
        <div class="modal-content"></div>
    </div>
    <script src="//at.alicdn.com/t/c/font_4623353_hb4c04qfi4u.js"></script>
    <script src="/static/js/fancybox.umd.min.js?v=<?php echo time(); ?>"></script>
    <script src="/static/js/lazyload.min.js?v=<?php echo time(); ?>"></script>
    <script src="/static/js/admin.js?v=<?php echo time(); ?>"></script>
    <script src="/static/js/settings.js?v=<?php echo time(); ?>"></script>
</body>
</html>
<?php
// 辅助函数
function renderImagesList($images) {
    if (empty($images)) {
        return '<div class="empty-state"><div class="empty-icon"></div><p>目前沒有圖片</p></div>';
    }
    
    $html = '';
    foreach ($images as $image) {
        $id = htmlspecialchars($image['id']);
        $url = htmlspecialchars($image['url']);
        $path = htmlspecialchars($image['path']);
        $size = number_format($image['size'] / 1024, 2);
        $ip = htmlspecialchars($image['upload_ip']);
        $time = htmlspecialchars($image['created_at']);
        
        $html .= <<<HTML
        <div class="gallery-item" id="image-{$id}">
            <div class="image-wrapper">
                <div class="image-placeholder"><div class="spinner"></div></div>
                <img class="lazy" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="{$url}" data-fancybox="gallery">
            </div>
            <div class="action-buttons">
                <button class="copy-btn" data-url="{$url}">
                    <svg class="icon" aria-hidden="true"><use xlink:href="#icon-link"></use></svg>
                </button>
                <button class="delete-btn" data-id="{$id}" data-path="{$path}">
                    <svg class="icon" aria-hidden="true"><use xlink:href="#icon-xmark"></use></svg>
                </button>
            </div>
            <div class="image-info">
                <p class="info-p">大小：<span>{$size} KB</span></p>
                <p class="info-p">IP: <span>{$ip}</span></p>
                <p class="info-p">時間：<span>{$time}</span></p>
            </div>
        </div>
HTML;
    }
    return $html;
}
?>
