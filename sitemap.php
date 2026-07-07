<?php
/**
 * sitemap.xml — 動態生成 Sitemap
 * 符合 https://www.sitemaps.org/protocol.html
 */
require_once __DIR__ . '/config/database.php';

$db     = Database::getInstance();
$pdo    = $db->getConnection();
$config = Database::getConfig($pdo);

// 取得站台網域
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'box.david888.com';
$baseUrl  = $scheme . '://' . $host;

// 撈取所有公開（無密碼）資產的 share_token，最多 50000 筆
$stmt = $pdo->prepare(
    "SELECT share_token, created_at
     FROM images
     WHERE (password IS NULL OR password = '')
       AND share_token IS NOT NULL
       AND share_token != ''
     ORDER BY created_at DESC
     LIMIT 50000"
);
$stmt->execute();
$assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// 靜態頁面
$staticPages = [
    ['loc' => $baseUrl . '/',                    'priority' => '1.0', 'changefreq' => 'daily'],
    ['loc' => $baseUrl . '/upload_image.php',    'priority' => '0.8', 'changefreq' => 'monthly'],
    ['loc' => $baseUrl . '/upload_video.php',    'priority' => '0.8', 'changefreq' => 'monthly'],
    ['loc' => $baseUrl . '/upload_file.php',     'priority' => '0.8', 'changefreq' => 'monthly'],
    ['loc' => $baseUrl . '/upload_audio.php',    'priority' => '0.8', 'changefreq' => 'monthly'],
];

foreach ($staticPages as $page) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($page['loc'], ENT_XML1) . "</loc>\n";
    echo '    <changefreq>' . $page['changefreq'] . "</changefreq>\n";
    echo '    <priority>' . $page['priority'] . "</priority>\n";
    echo "  </url>\n";
}

// 動態資產分享頁
foreach ($assets as $asset) {
    $loc     = $baseUrl . '/view.php?token=' . urlencode($asset['share_token']);
    $lastmod = substr($asset['created_at'], 0, 10); // YYYY-MM-DD
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($loc, ENT_XML1) . "</loc>\n";
    echo '    <lastmod>' . $lastmod . "</lastmod>\n";
    echo "    <changefreq>never</changefreq>\n";
    echo "    <priority>0.5</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>' . "\n";
