## Why

Users need to upload video files and have the system automatically generate an RSS podcast feed and a daily video list JSON for integration with external automation systems. This feature extends PixPro from image-focused to multimedia content support.

## What Changes

- Add a new `video.php` entrypoint in the repository root for handling video uploads
- Implement RSS podcast XML generation (RSS 2.0) for uploaded videos
- Generate and store daily video list JSON files in the storage directory
- Update `AGENTS.md` with a new "Video and Podcast Logic" section
- Use existing `StorageHelper` for all storage operations (local/OSS/S3/Upyun)

## Capabilities

### New Capabilities

- `video-upload-podcast`: Handles video file upload, validation, storage, RSS podcast generation, and daily JSON list creation

### Modified Capabilities

- None (this change adds new functionality without modifying existing requirements)

## Impact

- New file: `video.php` in repository root
- Storage directory will contain RSS XML files and daily JSON lists
- Uses existing `StorageHelper` and `config/upload.php` for validation and processing
- Updates to `AGENTS.md` for agent guidance on video/podcast logic
- No breaking changes to existing APIs or functionality