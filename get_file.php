<?php
/**
 * Authorization-aware storage file proxy.
 *
 * Cloud-backed assets are streamed through this endpoint so the browser does
 * not receive a redirect to the upstream storage URL.
 */
session_start();
require_once 'config/database.php';
require_once 'config/upload.php';

use Aws\S3\S3Client;

function failStorageRequest($statusCode, $message) {
    http_response_code($statusCode);
    exit($message);
}

function normalizeRequestedStoragePath($rawPath) {
    $path = rawurldecode((string)$rawPath);

    if ($path === '' || str_contains($path, "\0") || str_contains($path, '\\')) {
        return '';
    }

    $path = ltrim($path, '/');
    if ($path === '' || preg_match('#(^|/)\.\.?(/|$)#', $path)) {
        return '';
    }

    return $path;
}

/**
 * Parse a single byte range. Multiple ranges are intentionally rejected.
 */
function parseRequestedRange($rangeHeader, $totalSize) {
    if ($rangeHeader === '') {
        return null;
    }

    if ($totalSize <= 0 || !preg_match('/^bytes=(\d*)-(\d*)$/', trim($rangeHeader), $matches)) {
        return false;
    }

    $startValue = $matches[1];
    $endValue = $matches[2];

    if ($startValue === '' && $endValue === '') {
        return false;
    }

    if ($startValue === '') {
        $suffixLength = (int)$endValue;
        if ($suffixLength <= 0) {
            return false;
        }

        $start = max(0, $totalSize - $suffixLength);
        $end = $totalSize - 1;
    } else {
        $start = (int)$startValue;
        $end = $endValue === '' ? $totalSize - 1 : (int)$endValue;

        if ($start >= $totalSize || $start > $end) {
            return false;
        }

        $end = min($end, $totalSize - 1);
    }

    return [
        'start' => $start,
        'end' => $end,
        'length' => $end - $start + 1,
        'header' => "bytes={$start}-{$end}",
        'content_range' => "bytes {$start}-{$end}/{$totalSize}"
    ];
}

function safeAssetFileName($path) {
    $fileName = basename(str_replace('\\', '/', (string)$path));
    $fileName = preg_replace('/[^A-Za-z0-9._ -]/', '_', $fileName);
    return $fileName !== '' ? $fileName : 'download';
}

function sendStorageHeaders($asset, $contentType, $contentLength, $range = null) {
    header('Content-Type: ' . ($contentType ?: 'application/octet-stream'));
    header('Content-Length: ' . (int)$contentLength);
    header('Accept-Ranges: bytes');
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: inline; filename="' . addcslashes(safeAssetFileName($asset['path'] ?? ''), '"\\') . '"');

    if (!empty($asset['password'])) {
        header('Cache-Control: private, no-store');
    } else {
        header('Cache-Control: public, max-age=31536000, immutable');
    }

    if ($range !== null) {
        http_response_code(206);
        header('Content-Range: ' . $range['content_range']);
    }
}

function createProxyS3Client($config) {
    $endpoint = trim((string)($config['s3_endpoint'] ?? ''));
    if ($endpoint !== '' && !preg_match('/^https?:\/\//i', $endpoint)) {
        $endpoint = 'https://' . $endpoint;
    }

    $clientConfig = [
        'region' => $config['s3_region'] ?? 'us-east-1',
        'version' => 'latest',
        'credentials' => [
            'key' => $config['s3_access_key_id'] ?? '',
            'secret' => $config['s3_access_key_secret'] ?? ''
        ],
        'http' => [
            'verify' => true
        ]
    ];

    if ($endpoint !== '') {
        $clientConfig['endpoint'] = $endpoint;
    }

    return new S3Client($clientConfig);
}

function streamS3Asset($asset, $config, $rangeHeader) {
    $client = createProxyS3Client($config);
    $bucket = trim((string)($config['s3_bucket'] ?? ''));
    $key = ltrim((string)($asset['path'] ?? ''), '/');

    if ($bucket === '' || $key === '') {
        failStorageRequest(404, 'Storage object not found');
    }

    $objectParams = [
        'Bucket' => $bucket,
        'Key' => $key
    ];
    $head = $client->headObject($objectParams);
    $totalSize = (int)($head['ContentLength'] ?? $asset['size'] ?? 0);
    $contentType = $asset['mime_type'] ?: ($head['ContentType'] ?? 'application/octet-stream');
    $range = parseRequestedRange($rangeHeader, $totalSize);

    if ($range === false) {
        header('Content-Range: bytes */' . $totalSize);
        failStorageRequest(416, 'Requested range is not satisfiable');
    }

    sendStorageHeaders($asset, $contentType, $range === null ? $totalSize : $range['length'], $range);

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
        exit;
    }

    if ($range !== null) {
        $objectParams['Range'] = $range['header'];
    }

    $object = $client->getObject($objectParams);
    $body = $object['Body'];
    while (!$body->eof()) {
        echo $body->read(1024 * 1024);
        if (connection_aborted()) {
            break;
        }
    }
    exit;
}

/**
 * Keep compatibility with other remote storage backends without exposing a
 * redirect. S3 uses the SDK above so private buckets work as well.
 */
function streamHttpAsset($asset, $config, $range) {
    if (!function_exists('curl_init')) {
        failStorageRequest(502, 'Storage proxy is unavailable');
    }

    $url = resolveAssetOriginUrl($asset, $config);
    if ($url === '') {
        failStorageRequest(404, 'Storage object not found');
    }

    $totalSize = (int)($asset['size'] ?? 0);
    if ($range === false) {
        header('Content-Range: bytes */' . $totalSize);
        failStorageRequest(416, 'Requested range is not satisfiable');
    }

    sendStorageHeaders(
        $asset,
        $asset['mime_type'] ?: 'application/octet-stream',
        $range === null ? $totalSize : $range['length'],
        $range
    );

    $ch = curl_init($url);
    $curlOptions = [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_FAILONERROR => true,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_HEADER => false,
        CURLOPT_WRITEFUNCTION => static function ($curl, $chunk) {
            echo $chunk;
            return strlen($chunk);
        }
    ];

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
        $curlOptions[CURLOPT_NOBODY] = true;
    }

    if ($range !== null) {
        $curlOptions[CURLOPT_RANGE] = $range['start'] . '-' . $range['end'];
    }

    curl_setopt_array($ch, $curlOptions);
    $success = curl_exec($ch);
    curl_close($ch);

    if ($success === false) {
        error_log('Storage proxy request failed for asset ' . (int)($asset['id'] ?? 0));
        exit;
    }

    exit;
}

$requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if (!in_array($requestMethod, ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    failStorageRequest(405, 'Method not allowed');
}

$path = normalizeRequestedStoragePath($_GET['path'] ?? '');
if ($path === '') {
    failStorageRequest(404, 'Asset not found');
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $config = Database::getConfig($pdo);

    $fullPath = str_starts_with($path, 'storage/') ? $path : 'storage/' . $path;
    $stmt = $pdo->prepare('SELECT * FROM images WHERE path = ? OR path = ? LIMIT 1');
    $stmt->execute([$fullPath, $path]);
    $asset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$asset) {
        failStorageRequest(404, 'Asset not found');
    }

    $sessionKey = 'auth_asset_' . (int)$asset['id'];
    $isAdmin = !empty($_SESSION['loggedin']);
    if (!empty($asset['password']) && empty($_SESSION[$sessionKey]) && !$isAdmin) {
        failStorageRequest(403, 'This asset requires a password');
    }
    session_write_close();

    $storage = $asset['storage'] ?? 'local';
    if ($storage === 'local') {
        $localPath = __DIR__ . '/' . ltrim($asset['path'], '/');
        if (!is_file($localPath)) {
            failStorageRequest(404, 'File not found');
        }

        $fileSize = filesize($localPath);
        $range = parseRequestedRange($_SERVER['HTTP_RANGE'] ?? '', $fileSize);
        if ($range === false) {
            header('Content-Range: bytes */' . $fileSize);
            failStorageRequest(416, 'Requested range is not satisfiable');
        }

        $contentType = $asset['mime_type'] ?: (mime_content_type($localPath) ?: 'application/octet-stream');
        sendStorageHeaders($asset, $contentType, $range === null ? $fileSize : $range['length'], $range);
        if ($requestMethod === 'HEAD') {
            exit;
        }

        $handle = fopen($localPath, 'rb');
        if ($handle === false) {
            failStorageRequest(500, 'File could not be opened');
        }
        if ($range !== null) {
            fseek($handle, $range['start']);
        }

        $remaining = $range === null ? $fileSize : $range['length'];
        while ($remaining > 0 && !feof($handle)) {
            $chunk = fread($handle, min(1024 * 1024, $remaining));
            if ($chunk === false || $chunk === '') {
                break;
            }
            echo $chunk;
            $remaining -= strlen($chunk);
            if (connection_aborted()) {
                break;
            }
        }
        fclose($handle);
        exit;
    }

    $rangeHeader = $_SERVER['HTTP_RANGE'] ?? '';
    if ($storage === 's3') {
        streamS3Asset($asset, $config, $rangeHeader);
    }

    $fileSize = (int)($asset['size'] ?? 0);
    $range = parseRequestedRange($rangeHeader, $fileSize);
    streamHttpAsset($asset, $config, $range);
} catch (\Aws\Exception\AwsException $e) {
    $statusCode = (int)$e->getStatusCode();
    error_log('Storage proxy AWS request failed with status ' . $statusCode);
    failStorageRequest($statusCode === 404 ? 404 : 502, 'Storage request failed');
} catch (Throwable $e) {
    error_log('Storage proxy request failed: ' . get_class($e));
    failStorageRequest(500, 'Storage request failed');
}
