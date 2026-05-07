## 1. Environment and Infrastructure

- [x] 1.1 Update `Dockerfile` to include `ffmpeg` in system dependencies
- [x] 1.2 Update `config/upload.php` to include video MIME types and size limits if needed
- [x] 1.3 Review `StorageHelper` to ensure it supports reading/writing files with locking or handle locking externally

## 2. Video Helper Implementation (FFmpeg Integration)

- [x] 2.1 Create `config/video_helper.php` (or similar) to wrap FFmpeg calls
- [x] 2.2 Implement `getVideoMetadata($filePath)` to extract duration, resolution, and bitrate
- [x] 2.3 Implement `generateThumbnail($videoPath, $thumbPath)` to extract a frame at 1 second
- [x] 2.4 Add error handling and binary existence check for FFmpeg

## 3. Video Upload Endpoint Implementation

- [x] 3.1 Create `video.php` file in repository root
- [x] 3.2 Implement upload handling and validation (reusing logic where possible)
- [x] 3.3 Integrate `VideoHelper` to get metadata and thumbnail after storage
- [x] 3.4 Store thumbnail using `StorageHelper`
- [x] 3.5 Return JSON response with video URL, thumbnail URL, and metadata

## 4. Thread-Safe RSS Podcast Generation

- [x] 4.1 Implement `updatePodcastRSS($videoData)` with `flock()` protection
- [x] 4.2 Use `XMLWriter` or `DOMDocument` for safe XML generation/manipulation
- [x] 4.3 Include `<itunes:duration>` and `<itunes:image>` tags in the RSS feed
- [x] 4.4 Store/Update `podcast.xml` in the storage root via `StorageHelper`

## 5. Thread-Safe Daily Video List JSON

- [x] 5.1 Implement `updateDailyList($videoData)` with `flock()` protection
- [x] 5.2 Handle date-based directory creation (e.g., `storage/2026-05-06/`)
- [x] 5.3 Append video metadata (including resolution/duration) to `videos.json`
- [x] 5.4 Ensure proper JSON encoding and file writing

## 6. Integration and Testing

- [x] 6.1 Rebuild Docker image and verify FFmpeg installation
- [x] 6.2 Test end-to-end upload with metadata extraction and thumbnail generation
- [x] 6.3 Verify `podcast.xml` and `videos.json` are updated correctly with rich data
- [x] 6.4 Test concurrent uploads to verify file locking prevents corruption
- [x] 6.5 Validate generated RSS feed with a podcast validator (e.g., Castos or Podbase)