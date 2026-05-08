# 888box Asset Management Skill

This skill allows an AI agent to manage images, videos, and files on the 888box platform using a unified API and MCP server.

## Environment Setup
- **Base URL**: The domain where this `SKILL.md` file is hosted (e.g., `https://your-domain.com`).
- **Auth**: Pass the `token` in the POST body or as a Bearer token in the `Authorization` header.

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
- **Security**: Always use the user's `token` for authenticated requests.
- **Error Handling**: Check the `result` field in JSON responses. `error` indicates a failure.
