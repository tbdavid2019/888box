<?php
header('Content-Type: text/markdown; charset=utf-8');

// Determine Base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$baseUrl = $protocol . $host;

// Optional: Fetch token if session exists (convenience for the user's own agent)
session_start();
$tokenDisplay = "YOUR_API_TOKEN";
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
    require_once 'config/database.php';
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare("SELECT token FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['token'])) {
            $tokenDisplay = $row['token'];
        }
    } catch (Exception $e) {
        // Silently fail to guest mode
    }
}
?>
# 888box Asset Management Skill

This skill allows an AI agent to manage images, videos, and files on the 888box platform using a unified API and MCP server.

## Environment Setup
- **Base URL**: `<?= $baseUrl ?>`
- **Auth**: Pass the `token` in the POST body or as a Bearer token in the `Authorization` header.
- **Your Token**: `<?= $tokenDisplay ?>` (Use this token for your requests)

## Overview
888box is a programmable asset management platform. You can upload assets via URL, list recent items, and manage Podcast feeds.

## API Gateway (`/api.php`)
All requests require a valid `token`.

### Actions
1. **`upload_url`**: Ingest an asset from a remote URL.
   - Params: `url` (required), `title`, `description`, `password`.
2. **`list`**: Retrieve a list of assets.
   - Params: `type` (`image`|`video`|`file`|`all`), `page`.
3. **`stats`**: Get asset count statistics.
4. **`delete`**: Remove an asset.
   - Params: `id`.

## MCP Tools
If you are connected via MCP, use these tools:

- **`upload_asset_by_url`**: Best for transferring assets from other websites.
- **`list_assets`**: Use this to find IDs for deletion or viewing.
- **`get_stats`**: Check storage usage and counts.
- **`get_podcast_info`**: Retrieve the RSS feed for your videos.
- **`rebuild_podcast_rss`**: Run this if the RSS feed seems out of sync.

## Best Practices
- **Images**: Automatically converted to WebP for optimization.
- **Videos**: Automatically extracted metadata and generated thumbnails. Added to Podcast RSS if no password is set.
- **Security**: Always use the provided `token` for authenticated requests.
- **Error Handling**: Check the `result` field in JSON responses. `error` indicates a failure.
