# Changelog

All notable changes to this project will be documented in this file.

## [2026.5.8] - 2026-05-08

### ✨ Added
- **Storage Consolidation**: Moved all writable data (SQLite database, local uploads `i/`, RSS feeds, logs) into a dedicated `storage/` directory for unified permission management and better Docker compatibility.
- **Dedicated Video Infrastructure**: Completely separated video upload and management from the original image-centric architecture.
- **Video Upload UI (`upload_video.php`)**: Added a brand new, dedicated user interface specifically for video uploads, featuring a wide-screen drag-and-drop zone and native video preview capabilities.
- **Video Admin Panel (`admin/video.php`)**: Created a dedicated administrative panel to manage, preview, copy links for, and delete uploaded videos.
- **Podcast RSS Generation (`storage/podcast.xml`)**: Implemented automated generation of iTunes-compliant Podcast RSS feeds containing all uploaded videos.
- **Metadata Editing (`api_edit_video.php`)**: Added the ability to edit the `Title` and `Description` of videos directly from the video admin panel, which instantly syncs the changes to the database and rebuilds the Podcast RSS feed.
- **Metadata on Upload**: Added input fields in the video upload UI to allow users to set the Podcast Title and Description at the time of upload.
- **Smart Title Extraction**: The video upload process now automatically extracts and uses the original uploaded filename (e.g., `MyVideo.mp4` -> `MyVideo`) as the default video title if no title is explicitly provided, preserving user-friendly names instead of randomized IDs.
- **Automatic FFmpeg Extraction**: The system now automatically uses FFmpeg (compiled into the Docker image) to extract video duration and resolution, and to generate a thumbnail image at the 1-second mark for the Podcast cover.
- **Configurable Video Limits**: Added a `max_video_size` parameter to the admin settings panel, allowing administrators to configure the maximum allowed video upload size via the UI (defaulting to 500MB).
- **Daily JSON List**: The system now generates a `storage/YYYY-MM-DD/videos.json` file daily to allow external automation bots to easily scrape newly uploaded videos.

### 🐛 Fixed
- **Missing Password Change UI**: Added password update fields to the admin settings panel, allowing administrators to change their password securely from the UI.
- **Database Permission Issue**: Resolved `SQLSTATE[HY000] [14] unable to open database file` error in Docker environments by moving the SQLite database to a writable sub-directory and ensuring correct directory permissions.
- **PHP Upload Limits**: Modified the `Dockerfile` to increase PHP's `upload_max_filesize`, `post_max_size`, and `memory_limit` to 500MB+ to prevent "No file uploaded" (無文件上傳) errors on large video files.
- **Hardcoded Size Constraints**: Removed arbitrary code logic that restricted video uploads to 50MB regardless of server configuration.
- **Zero-Size Database Corruption**: Fixed a critical bug where the local temporary video file was deleted (`unlink`) before its size was captured (`filesize()`), resulting in empty file size database records and NaN upload UI responses.
- **Admin Panel Rendering Crash**: Refactored `admin/video.php` size formatting logic (`floatval()`) to safely tolerate missing or corrupt file size data, ensuring the video grid always renders successfully even if past records are corrupted (fixing the "only one video shows up" bug).
- **Empty S3 Endpoints Validation**: Added robust validation and protocol auto-completion (`https://`) for S3 connection strings in `StorageHelper` to resolve AWS SDK `Invalid URI` initialization errors.
- **Admin Image Filter**: Fixed an issue where video files were causing broken image icons in the original `admin/index.php` by filtering them out of the image queries.
- **Database Schema Migration**: Implemented a robust auto-migration script that triggers when accessing the video admin panel to ensure `title` and `description` columns are added to existing databases, preventing "no such column" SQLite errors.

### 💄 Style (UI/UX)
- **Eradicated Unwanted Backgrounds**: Completely removed the hardcoded anime background image (`bg.webp`) from all views (Upload, Admin, Install) and replaced it with a professional, dark solid-color theme.
- **Terminology Localization**: Audited the entire codebase to replace Simplified Chinese terminologies and comments (e.g., 默认, 视频, 文件) with standard Traditional Chinese (Taiwan) terms (預設, 影片, 檔案).
- **Branding Update**: Changed the main site title and metadata descriptions to "888box" and removed mentions of Alibaba Cloud (OSS) in favor of emphasizing AWS S3 support.
- **Navigation Banners**: Added prominent navigation banners to easily guide users between the image interface and the new dedicated video interface.