<?php
header('Content-Type: text/markdown; charset=utf-8');

// Determine Base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$baseUrl = $protocol . $host;

// Optional: Fetch token if session exists (convenience for the user's own agent)
session_start();
$tokenDisplay = "YOUR_API_TOKEN";
$videoRssPath = '/storage/podcast.xml';
$audioRssPath = '/storage/podcast_audio.xml';
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
    require_once 'config/database.php';
    require_once 'config/rss.php';
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare("SELECT token FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['token'])) {
            $tokenDisplay = $row['token'];
        }

        $config = Database::getConfig($pdo);
        $videoRssPath = buildRssUrl('video', $config, true);
        $audioRssPath = buildRssUrl('audio', $config, true);
    } catch (Exception $e) {
        // Silently fail to guest mode
    }
}
?>
---
name: 888box-asset-management
description: Use when managing 888box assets through the live server API or MCP endpoints. Supports uploading remote assets, listing images/videos/audios/files, reading stats, deleting assets, and checking podcast RSS information. This rendered skill includes the correct live Base URL and token hints for the current 888box deployment.
---

# 888box Asset Management

## Environment Setup
- **Base URL**: `<?= $baseUrl ?>`
- **Public Mode**: If this 888box instance has not enabled login restriction, public upload actions can be used without a token.
- **Token Auth**: For protected actions, pass the `token` in the POST body or as a Bearer token in the `Authorization` header.
- **Your Token**: `<?= $tokenDisplay ?>`

## When To Use

Use this skill when the user wants to:

- upload a remote image, video, audio, or file into this 888box instance
- list recent assets from this server
- inspect counts or asset stats
- delete an asset by `id`
- inspect podcast RSS information for uploaded videos or audios
- create, inspect, revoke, pulse, or burn a Seal for an asset
- operate against the live 888box deployment without hardcoding the wrong domain

## Workflow

1. Use the live Base URL shown above.
2. Prefer the unified API at `<?= $baseUrl ?>/api.php`.
3. For public upload flows, try the request without a token first.
4. For protected or admin-style operations, authenticate with the provided token.
5. If MCP tools are available for this server, prefer those tools over raw HTTP calls.
6. Check JSON responses for `result`.
   `success` means the call worked.
   `error` means the call failed and the `message` should be surfaced.

## API Gateway

Primary endpoint:

`<?= $baseUrl ?>/api.php`

Authentication depends on the action:

- `upload` public when login restriction is off
- `upload_url` public when login restriction is off
- `stats` public
- `list` token required
- `search` token required
- `delete` token required
- `seal_create` asset capability token, admin API token, or admin session
- `seal_capability_status` asset capability token, admin API token, or admin session
- `seal_revoke` asset capability token, admin API token, or admin session
- `seal_status` public with a Seal public token
- `seal_pulse` public with a private Pulse token
- `seal_burn` public with a private Pulse token and permanently deletes the asset

### Supported Actions

#### `upload`

Upload local files with multipart form data.

Authentication:
- public when login restriction is off
- otherwise token required

#### `upload_url`

Ingest an asset from a remote URL.

Authentication:
- public when login restriction is off
- otherwise token required

Parameters:
- `url` required
- `title` optional
- `description` optional
- `password` optional

#### `list`

Retrieve a list of assets.

Parameters:
- `type` one of `image`, `video`, `audio`, `file`, `all`
- `page` optional

#### `search`

Search assets by keyword across title, path, and URL.

Parameters:
- `q` required (keyword string)
- `type` optional (`image`, `video`, `audio`, `file`, `all`)

#### `stats`

Get asset count statistics.

#### `delete`

Remove an asset.

Parameters:
- `id` required

#### Asset capability and Seal actions

Every new upload returns a one-time `manage_token` inside `data`. Store it with the asset. It grants management access to that asset only and is accepted by `seal_create`, `seal_capability_status`, and `seal_revoke`.

```bash
# Create a timed Seal with the asset-scoped capability
curl -X POST '<?= $baseUrl ?>/api.php?action=seal_create' \
  -d 'asset_id=123' \
  -d 'manage_token=ASSET_MANAGE_TOKEN' \
  -d 'mode=timed' \
  -d 'unlock_at=2030-01-01T12:00:00'

# Read current Seal status
curl -X POST '<?= $baseUrl ?>/api.php?action=seal_capability_status' \
  -d 'asset_id=123' \
  -d 'manage_token=ASSET_MANAGE_TOKEN'
```

Modes:
- `timed`: unlocks at `unlock_at`.
- `dms`: requires `pulse_interval` in seconds. The response includes a private `pulse_url` and `pulse_token`; keep them private.
- `ephemeral`: deletes the asset after `max_views` successful deliveries. Images and documents are supported.

The public `seal_url` only controls access to the asset. The management capability and DMS Pulse token provide separate authority. Treat all three as secrets where applicable.

## Example HTTP Requests

### Public Upload From URL

```bash
curl -X POST '<?= $baseUrl ?>/api.php?action=upload_url' \
  -d 'url=https://example.com/file.jpg' \
  -d 'title=Example Asset'
```

### Authenticated List Assets

```bash
curl '<?= $baseUrl ?>/api.php?action=list&type=all&page=1&token=<?= $tokenDisplay ?>'
```

### JSON-RPC 2.0 MCP / WebMCP Call

```bash
curl -X POST '<?= $baseUrl ?>/mcp.php' \
  -H 'Content-Type: application/json' \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"get_stats","arguments":{}}}'
```

## MCP & WebMCP Tools
888box provides dual-mode MCP support over both CLI stdio and HTTP (compatible with Chrome 146+ WebMCP `document.modelContext` and Cloudflare WebMCP):

- **Endpoint**: `<?= $baseUrl ?>/mcp.php` (or `<?= $baseUrl ?>/mcp`)
- **Transport**: stdio (CLI) or JSON-RPC 2.0 over HTTP POST
- **Browser WebMCP**: Automatically registers tools in `document.modelContext` for browser AI agents.

Available tools:
- **`upload_asset_by_url`**: Best for transferring assets from other websites.
- **`list_assets`**: List recent assets with type filter and pagination.
- **`search_assets`**: Search assets by keyword across title, path, and URL.
- **`get_stats`**: Check storage usage and asset counts.
- **`get_podcast_info`**: Retrieve the RSS feeds for your videos or audios.
- **`rebuild_podcast_rss`**: Force rebuild of Podcast RSS feeds (Admin only).
- **`delete_asset`**: Remove an asset by ID (Admin only).
- **`create_asset_seal`**: Create a timed, DMS, or ephemeral Seal with an asset capability or owner token.
- **`get_asset_seal`**: Read an asset Seal status.
- **`revoke_asset_seal`**: Remove an asset Seal while retaining the asset.
- **`pulse_asset_seal`**: Reset a locked DMS timer using the private Pulse token.
- **`burn_asset_seal`**: Permanently delete a locked DMS asset using the private Pulse token.

## Best Practices
- **Images**: Automatically converted to WebP for optimization.
- **Videos**: Automatically extracted metadata and generated thumbnails. Added to Video Podcast RSS (`<?= $videoRssPath ?>`) if no password is set.
- **Audios**: Automatically extracted duration and bitrate metadata. Added to Audio Podcast RSS (`<?= $audioRssPath ?>`) if no password is set.
- **Security**: Store each upload response `manage_token` as an asset-scoped secret. Use it for that asset's Seal actions. Keep DMS `pulse_token` private because it can reset the timer; `burn_asset_seal` is destructive.
- **Error Handling**: Check the `result` field in JSON responses. `error` indicates a failure.
