<?php

function getRssPublicPath($type) {
    return $type === 'audio' ? 'storage/podcast_audio.xml' : 'storage/podcast.xml';
}

function getRssCachePath($type) {
    return $type === 'audio' ? 'storage/podcast_audio.internal.xml' : 'storage/podcast.internal.xml';
}

function getRssLockPath($type) {
    return getRssCachePath($type) . '.lock';
}

function isRssTokenEnabled($config) {
    return ($config['rss_token_enabled'] ?? 'false') === 'true';
}

function getRssTokenValue($config) {
    return trim((string)($config['rss_token'] ?? ''));
}

function generateRssToken($length = 48) {
    $rawLength = max(16, (int)ceil($length / 2));
    return substr(bin2hex(random_bytes($rawLength)), 0, $length);
}

function resolveRssBaseUrl($config) {
    if (!empty($_SERVER['HTTP_HOST'])) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        $protocol = $isHttps ? 'https://' : 'http://';
        return $protocol . $_SERVER['HTTP_HOST'];
    }

    $configuredDomains = array_filter(array_map('trim', explode(',', (string)($config['site_domain'] ?? ''))));
    foreach ($configuredDomains as $domain) {
        if ($domain === '*') {
            continue;
        }

        if (preg_match('/^https?:\/\//i', $domain)) {
            return rtrim($domain, '/');
        }

        return 'https://' . rtrim($domain, '/');
    }

    return 'http://localhost';
}

function buildRssUrl($type, $config, $includeToken = false) {
    $baseUrl = resolveRssBaseUrl($config);
    $url = $baseUrl . '/' . ltrim(getRssPublicPath($type), '/');

    if ($includeToken && isRssTokenEnabled($config)) {
        $token = getRssTokenValue($config);
        if ($token !== '') {
            $url .= '?rss_token=' . rawurlencode($token);
        }
    }

    return $url;
}

function cleanupLegacyPublicRssFiles() {
    foreach (['video', 'audio'] as $type) {
        $publicPath = getRssPublicPath($type);
        $cachePath = getRssCachePath($type);

        if ($publicPath !== $cachePath && file_exists($publicPath)) {
            @unlink($publicPath);
        }
    }
}

function resolveRssRequestType($requestPath) {
    if (substr($requestPath, -17) === 'podcast_audio.xml') {
        return 'audio';
    }

    return 'video';
}
