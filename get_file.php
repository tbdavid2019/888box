<?php
/**
 * Dynamic Storage File Router & Proxy
 */
session_start();
require_once 'config/database.php';
require_once 'config/upload.php';

$path = $_GET['path'] ?? '';
if (empty($path)) {
    http_response_code(404);
    exit('File path is required');
}

// Security check to prevent directory traversal
$path = str_replace(['../', '..\\'], '', $path);
$path = ltrim($path, '/');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $config = Database::getConfig($pdo);
    
    // Look up the asset in the database by path
    // Path can be either 'storage/i/...' or 'storage/file/...'
    $fullPath = 'storage/' . $path;
    $stmt = $pdo->prepare("SELECT * FROM images WHERE path = ? OR path = ?");
    $stmt->execute([$fullPath, $path]);
    $asset = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$asset) {
        // Fallback: If not in DB, but the file exists locally, serve it
        $localPath = __DIR__ . '/storage/' . $path;
        if (is_file($localPath)) {
            $mimeType = mime_content_type($localPath) ?: 'application/octet-stream';
            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . filesize($localPath));
            readfile($localPath);
            exit;
        }
        
        http_response_code(404);
        exit('Asset not found');
    }
    
    $storage = $asset['storage'] ?? 'local';
    
    if ($storage === 'local') {
        $localPath = __DIR__ . '/' . ltrim($asset['path'], '/');
        if (!is_file($localPath)) {
            http_response_code(404);
            exit('File not found');
        }
        $mimeType = $asset['mime_type'] ?: (mime_content_type($localPath) ?: 'application/octet-stream');
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($localPath));
        readfile($localPath);
        exit;
    }
    
    // For S3/OSS/Upyun, redirect to the secure remote URL
    $url = resolveAssetOriginUrl($asset, $config);
    if (empty($url)) {
        http_response_code(404);
        exit('URL not found');
    }
    
    // Perform a 302 Redirect so the browser fetches the file directly from cloud storage
    header('Location: ' . $url, true, 302);
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    exit('Server Error: ' . $e->getMessage());
}
