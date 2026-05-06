## Context

PixPro is a PHP application focused on image uploads with support for multiple storage backends (local, OSS, S3, Upyun). The application uses a StorageHelper abstraction for storage operations and has existing upload handling in api.php and config/upload.php. Users now need to upload video files and generate podcast RSS feeds and daily video lists for automation integration. To support rich metadata and thumbnails, FFmpeg will be integrated into the containerized environment.

## Goals / Non-Goals

**Goals:**
- Enable video file uploads through a dedicated endpoint
- Automatically generate RSS 2.0 compliant podcast XML for uploaded videos
- Create daily video list JSON files for automation consumption
- Leverage FFmpeg for metadata extraction (duration, resolution) and thumbnail generation
- Ensure thread-safe/process-safe updates to shared files (RSS, JSON) using file locking
- Leverage existing storage and validation infrastructure
- Maintain compatibility with all existing storage backends

**Non-Goals:**
- Video transcoding or format conversion (preserving original quality)
- User interface for video management in admin panel
- Advanced podcast features (iTunes categories, explicit tags, etc.)
- Complex video playback or streaming functionality

## Decisions

### Dedicated video.php endpoint vs extending api.php
**Chosen:** Create a new video.php file in the repository root
**Why:** Keeps video-specific logic separate from general upload API, follows existing pattern of dedicated entrypoints (migrate.php, mcp.php), avoids overloading api.php with media-type-specific logic

### Using StorageHelper for all storage operations
**Chosen:** Route all video storage operations through StorageHelper
**Why:** Ensures consistent behavior across storage backends, leverages existing abstraction, reduces code duplication, maintains consistency with existing upload handling

### FFmpeg for Metadata and Thumbnails
**Chosen:** Install FFmpeg in the Docker image and use it via shell execution (exec/passthru)
**Why:** Provides reliable extraction of duration, bitrate, and resolution. Allows generating a cover image (thumbnail) for the video, which is essential for a high-quality podcast feed.

### File Locking (flock) for Shared Resource Updates
**Chosen:** Use PHP's `flock()` when updating `podcast.xml` and daily `videos.json`
**Why:** Prevents data corruption during concurrent uploads. Since multiple users might upload at once, we must ensure sequential access to these shared index files.

### RSS 2.0 format with core podcast fields
**Chosen:** Generate RSS 2.0 XML with title, description, enclosure URL, duration, and publication date
**Why:** Provides broad podcast client compatibility, matches user requirements, keeps implementation focused.

### Daily JSON list stored as file in storage directory
**Chosen:** Write daily video list JSON to a file in storage/{date}/videos.json format
**Why:** Simple to implement, works with all storage backends via StorageHelper, allows external systems to fetch via standard storage URLs.

## Risks / Trade-offs

[Storage backend compatibility] → All storage backends must support file creation and reading in subdirectories; test with each backend type
[RSS XML escaping] → Improper escaping could break XML parsing; use PHP's htmlspecialchars or XMLWriter for safe generation
[File naming collisions] → Using original filenames could cause overwrites; implement unique filename generation like existing uploads
[FFmpeg Dependency] → Increases Docker image size; essential for metadata/thumbnails. Ensure binary availability check in code.
[Concurrency Bottleneck] → Heavy concurrent uploads might wait on file locks; acceptable for this scale of application.
[Storage costs] → Video files and thumbnails are larger than images; monitor storage usage.