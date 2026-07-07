<?php
session_start();
header("Cache-Control: max-age=10800");

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    require 'login.php';
    exit;
}

require_once '../config/database.php';
require_once '../config/theme_helper.php';
require_once '../config/admin_ui.php';

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
// 過濾掉影片、音訊與文件，僅保留圖片
$total_rows = (int)($pdo->query("SELECT COUNT(id) FROM images WHERE is_video = 0 AND is_audio = 0 AND is_file = 0")->fetchColumn() ?: 0);

$total_pages = max(1, ceil($total_rows / $items_per_page));
$current_page = min(max(1, $_GET['page'] ?? 1), $total_pages);

// 获取图片数据
$offset = ($current_page - 1) * $items_per_page;
$stmt = $pdo->prepare("SELECT * FROM images WHERE is_video = 0 AND is_audio = 0 AND is_file = 0 ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->execute([$items_per_page, $offset]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($images as &$image) {
    $image['url'] = getMaskedUrl($image['url'], $image['path']);
}
unset($image);

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
    <title>圖片管理後台 - 888box</title>
    <link rel="shortcut icon" href="/static/favicon.svg">
    <link rel="stylesheet" href="/static/css/admin.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/static/css/fancybox.min.css?v=<?php echo time(); ?>">
    <?php renderThemeStyles($pdo); ?>
</head>
<body>
    <?php renderAdminHeader('image', '圖片管理後台', [
        ['label' => '上傳圖片', 'href' => '/upload_image.php'],
        ['label' => '系統設定', 'href' => '#', 'class' => 'settings-link'],
        ['label' => '返回首頁', 'href' => '/'],
        ['label' => '登出', 'href' => '/admin/index.php?logout=true'],
    ]); ?>
    <div id="gallery" class="gallery"><?= $images_html ?></div>
    <div class="rightside">
        <a href="/admin/video.php" class="floating-link" title="影片管理" style="background-color: #7aa2f7;">
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
    <?php renderAdminFooter(); ?>
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
        $id          = htmlspecialchars($image['id']);
        $url         = htmlspecialchars($image['url']);
        $shareUrl    = htmlspecialchars(buildAssetShareUrl($image));
        $path        = htmlspecialchars($image['path']);
        $size        = number_format($image['size'] / 1024, 2);
        $ip          = htmlspecialchars($image['upload_ip']);
        $time        = htmlspecialchars($image['created_at']);
        $title       = htmlspecialchars($image['title'] ?? '');
        $description = htmlspecialchars($image['description'] ?? '');
        $hasPassword = empty($image['password']) ? '0' : '1';
        
        $reportBadge = $image['report_count'] > 0 
            ? "<div style=\"position: absolute; top: 10px; left: 10px; background: #f7768e; color: #1a1b26; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; z-index: 10;\">檢舉: {$image['report_count']}</div>" 
            : "";
        $passBadge = !empty($image['password'])
            ? '<div style="position:absolute;top:10px;left:10px;background:#e0af68;color:#1a1b26;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:bold;z-index:10;">🔒 密碼</div>'
            : '';
        if ($image['report_count'] > 0) $passBadge = ''; // 檢舉優先
            
        $html .= <<<HTML
        <div class="gallery-item" id="image-{$id}"
             data-title="{$title}"
             data-description="{$description}"
             data-has-password="{$hasPassword}">
            {$reportBadge}{$passBadge}
            <div class="image-wrapper">
                <div class="image-placeholder"><div class="spinner"></div></div>
                <img class="lazy" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="{$url}" data-fancybox="gallery">
            </div>
            <div class="action-buttons">
                <button class="img-btn share-btn" data-url="{$shareUrl}" title="複製分享連結">分享</button>
                <button class="img-btn direct-btn" data-url="{$url}" title="複製圖片直連">直連</button>
                <button class="img-btn edit-btn" data-id="{$id}" title="編輯資訊">編輯</button>
                <button class="img-btn delete-btn" data-id="{$id}" data-path="{$path}" title="刪除">刪除</button>
            </div>
            <div class="image-info">
                <p class="info-p">大小：<span>{$size} KB</span></p>
                <p class="info-p">時間：<span>{$time}</span></p>
                <p class="info-p">瀏覽：<span>{$image['view_count']} 次</span></p>
                <div style="display:flex; justify-content: space-between; align-items: center; margin-top:5px;">
                    <span class="info-p" style="font-size:10px; color:#565f89;">IP: {$ip}</span>
                    <a href="{$shareUrl}" target="_blank" style="color: #7aa2f7; text-decoration:none; font-size:12px;">預覽</a>
                </div>
            </div>
        </div>
HTML;
    }
    return $html;
}

?>
