<?php
session_start();
require_once 'config/database.php';
require_once 'config/theme_helper.php';
$db = Database::getInstance();
$pdo = $db->getConnection();

// 獲取統計數據
$stats = [
    'image' => (int)$pdo->query("SELECT COUNT(*) FROM images WHERE is_video = 0 AND is_audio = 0 AND is_file = 0")->fetchColumn(),
    'video' => (int)$pdo->query("SELECT COUNT(*) FROM images WHERE is_video = 1")->fetchColumn(),
    'audio' => (int)$pdo->query("SELECT COUNT(*) FROM images WHERE is_audio = 1")->fetchColumn(),
    'file'  => (int)$pdo->query("SELECT COUNT(*) FROM images WHERE is_file = 1")->fetchColumn(),
];

// Compute base URL for reuse in meta tags and Link headers
$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host    = $_SERVER['HTTP_HOST'] ?? 'box.david888.com';
$base    = $scheme . '://' . $host;

// RFC 8288 Link headers — AI agent 發現資源
if (!headers_sent()) {
    header('Link: <' . $base . '/.well-known/api-catalog>; rel="api-catalog"', false);
    header('Link: <' . $base . '/skill.php>; rel="service-doc"', false);
    header('Link: <' . $base . '/sitemap.xml>; rel="sitemap"; type="application/xml"', false);
    header('Link: <' . $base . '/.well-known/mcp/server-card.json>; rel="mcp-server-card"', false);
    header('Link: <' . $base . '/.well-known/agent-skills/index.json>; rel="agent-skills"', false);
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>888box - 統一資產管理</title>
    <meta name="description" content="888box 是專業、高效、安全的個人資產管理平台，支援圖片、影片、音訊與檔案託管，提供 WebP 壓縮、Podcast RSS 同步與線上閱讀功能。">

    <!-- Canonical URL -->
    <link rel="canonical" href="<?= $base ?>">

    <!-- Search engine favicon (PNG + ICO required by Google) -->
    <link rel="icon" type="image/svg+xml" href="/static/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/static/favicon-32x32.png">
    <link rel="icon" href="/static/favicon.ico" sizes="any">

    <!-- Apple touch icon -->
    <link rel="apple-touch-icon" sizes="180x180" href="/static/apple-touch-icon.png">

    <!-- Web app manifest -->
    <link rel="manifest" href="/static/site.webmanifest">

    <!-- Open Graph -->
    <meta property="og:title" content="888box - 統一資產管理">
    <meta property="og:description" content="專業、高效、安全的個人資產中心，支援圖片、影片、音訊與檔案託管。">
    <meta property="og:image" content="<?= $base ?>/static/og-image.png">
    <meta property="og:url" content="<?= $base ?>">
    <meta property="og:site_name" content="888box">
    <meta property="og:locale" content="zh_TW">
    <meta property="og:type" content="website">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="888box - 統一資產管理">
    <meta name="twitter:description" content="專業、高效、安全的個人資產中心，支援圖片、影片、音訊與檔案託管。">
    <meta name="twitter:image" content="<?= $base ?>/static/og-image.png">

    <!-- Browser theme color -->
    <meta name="theme-color" content="#1a1b26">

    <!-- Structured data (JSON-LD) -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "888box",
        "url": "<?= $base ?>",
        "description": "專業、高效、安全的個人資產中心，支援圖片、影片、音訊與檔案託管。",
        "potentialAction": {
            "@type": "SearchAction",
            "target": {
                "@type": "EntryPoint",
                "urlTemplate": "<?= $base ?>/api.php?action=search&q={search_term_string}"
            },
            "query-input": "required name=search_term_string"
        }
    }
    </script>

    <link rel="stylesheet" href="/static/css/portal.css?v=<?php echo time(); ?>">
    <?php renderThemeStyles($pdo); ?>
    <style>
        .stats-badge {
            background: rgba(122, 162, 247, 0.14);
            border: 1px solid rgba(122, 162, 247, 0.18);
            color: var(--text-primary);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            margin-top: 10px;
            display: inline-block;
        }

        .portal-footer {
            margin-top: 60px;
            color: var(--text-secondary);
            font-size: 0.8rem;
        }

        .portal-footer a {
            color: rgba(125, 207, 255, 0.72);
            text-decoration: none;
            font-weight: bold;
        }
    </style>
    <?php renderCustomTrackingCode($pdo); ?>
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

        <!-- 聲音大廳 -->
        <a href="/upload_audio.php" class="card card-audios">
            <div>
                <div class="card-icon">🎙️</div>
                <h2 class="card-title">聲音大廳</h2>
                <p class="card-desc">支援 MP3/WAV 上傳與 Podcast RSS 訂閱</p>
                <div class="stats-badge"><?= $stats['audio'] ?> 首音訊</div>
            </div>
        </a>
    </div>


    <footer class="portal-footer">
        &copy; <?= date('Y') ?> 888box. All rights reserved. <br>
        Created by <a href="https://david888.com" target="_blank">DAVID888</a> | 
        <a href="/skill.php" target="_blank">AI Agent Skills</a>
    </footer>

    <!-- WebMCP — AI agent 工具提供（navigator.modelContext API） -->
    <script>
    (function() {
        if (typeof navigator === 'undefined' || !navigator.modelContext) return;

        const BASE_URL = window.location.origin;

        navigator.modelContext.provideContext({
            tools: [
                {
                    name: 'upload_image',
                    description: 'Upload an image to 888box. Supports JPG, PNG, WebP, GIF, SVG. Returns a token-based share URL and direct CDN URL.',
                    inputSchema: {
                        type: 'object',
                        properties: {
                            file_url: { type: 'string', description: 'URL of the image to upload (remote ingestion)' },
                            title: { type: 'string', description: 'Optional title' }
                        }
                    },
                    execute: async ({ file_url, title }) => {
                        const fd = new FormData();
                        if (file_url) fd.append('url', file_url);
                        if (title) fd.append('title', title);
                        const r = await fetch(BASE_URL + '/api.php?action=upload', { method: 'POST', body: fd });
                        return r.json();
                    }
                },
                {
                    name: 'list_assets',
                    description: 'List stored assets on 888box with optional type filter and pagination.',
                    inputSchema: {
                        type: 'object',
                        properties: {
                            type:  { type: 'string', enum: ['all', 'image', 'video', 'audio', 'file'], default: 'all' },
                            page:  { type: 'integer', minimum: 1, default: 1 },
                            limit: { type: 'integer', minimum: 1, maximum: 100, default: 20 }
                        }
                    },
                    execute: async ({ type = 'all', page = 1, limit = 20 }) => {
                        const r = await fetch(`${BASE_URL}/api.php?action=list&type=${type}&page=${page}&limit=${limit}`);
                        return r.json();
                    }
                },
                {
                    name: 'search_assets',
                    description: 'Search assets on 888box by keyword across title, path, and URL.',
                    inputSchema: {
                        type: 'object',
                        required: ['query'],
                        properties: {
                            query: { type: 'string', description: 'Search keyword' },
                            type:  { type: 'string', enum: ['all', 'image', 'video', 'audio', 'file'], default: 'all' }
                        }
                    },
                    execute: async ({ query, type = 'all' }) => {
                        const r = await fetch(`${BASE_URL}/api.php?action=search&q=${encodeURIComponent(query)}&type=${type}`);
                        return r.json();
                    }
                },
                {
                    name: 'get_stats',
                    description: 'Get 888box site statistics: total count of images, videos, audio files, and documents.',
                    inputSchema: { type: 'object', properties: {} },
                    execute: async () => {
                        const r = await fetch(BASE_URL + '/api.php?action=stats');
                        return r.json();
                    }
                }
            ]
        });
    })();
    </script>

</body>
</html>
