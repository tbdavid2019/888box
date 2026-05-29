<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/rss.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $config = Database::getConfig($pdo);
    $type = $_GET['type'] ?? resolveRssRequestType($_SERVER['REQUEST_URI'] ?? '');
    $type = $type === 'audio' ? 'audio' : 'video';

    if (isRssTokenEnabled($config)) {
        $requestToken = trim((string)($_GET['rss_token'] ?? ''));
        $validToken = getRssTokenValue($config);

        if ($requestToken === '' || $validToken === '' || !hash_equals($validToken, $requestToken)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            exit('RSS token 無效');
        }
    }

    $rssPath = getRssCachePath($type);
    if (!file_exists($rssPath)) {
        $legacyPath = getRssPublicPath($type);
        if (file_exists($legacyPath)) {
            $rssPath = $legacyPath;
        }
    }

    if (!file_exists($rssPath)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        exit('RSS 檔案不存在');
    }

    header('Content-Type: application/rss+xml; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow', true);
    readfile($rssPath);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('RSS 讀取失敗');
}
