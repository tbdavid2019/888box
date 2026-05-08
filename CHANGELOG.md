# Changelog

All notable changes to this project will be documented in this file.

## [2026.5.10] - 2026-05-08

### ✨ Added
- **AI Agent Skill System (`skill.php`)**: Introduced a dynamic skill documentation endpoint that automatically detects the host domain and protocol (handling reverse proxies). It also injects the user's API token when logged in, making it a zero-configuration "one-click" integration for AI agents.
- **Unified Management Footer**: Standardized the footer across all administrative dashboards (`admin/index.php`, `admin/video.php`, `admin/file.php`) to match the front-end portal, enabling seamless switching between management modules and quick access to AI Skill docs.
- **MCP Tooling Support**: Formalized the system as a programmable asset platform, providing specific guidance for Model Context Protocol (MCP) agents to perform automated uploads, listing, and maintenance tasks.

### 🐛 Fixed
- **Admin Settings Loading Issue**: Resolved a syntax error in `admin/settings.php` caused by a missing array key (`output_format`), which previously caused the settings modal to fail during AJAX loading.
- **Protocol Detection**: Implemented robust protocol detection in `skill.php` using `X-Forwarded-Proto` headers to ensure correct HTTPS URLs are generated in proxied environments (e.g., Cloudflare/Nginx).

## [2026.5.9] - 2026-05-08

### ✨ Added
- **Unified Bento Portal**: Completely redesigned the root `index.php` as a modern, iOS-style Bento Grid portal for unified access to Image, Video, and File centers.
- **Document Hosting Center (`upload_file.php`)**: Added support for general documents including ZIP, PDF, Word, Excel, Visio, and EPUB.
- **EPUB Online Reader**: Integrated `epub.js` into the view portal, allowing users to read electronic books directly in the browser.
- **Unified Security Gatekeeper (`view.php`)**: Implemented a universal asset viewing gateway that handles secure access, analytics, and dynamic rendering for all media types.
- **Granular Password Protection**: Added the ability to set individual access passwords for images, videos, and files during upload.
- **Analytics Engine**: Implemented "Real View Count" tracking for all assets, visible in the administrative dashboards.
- **Reporting System (`api_report.php`)**: Added a user-facing "Report" feature for inappropriate content, integrated with an automated SMTP notification system.
- **SMTP Notification Backend**: Developed a Python-based SMTP mailer (`scripts/report_mail.py`) to handle high-reliability email alerts to administrators.
- **Admin Dashboards Consolidation**:
    - **File Management (`admin/file.php`)**: New dashboard for managing document assets.
    - **Reporting Statistics**: Added "Reported" status badges and hit-counts to Image, Video, and File admin panels.
- **Privacy Controls**: Updated the Podcast engine to automatically exclude password-protected videos from the public RSS feed.
- **Batch Video Metadata**: Added "Global Metadata" inputs to the video upload UI, allowing users to apply a single Title or Password to an entire batch of uploads.

### 💄 Style (UI/UX)
- **Glassmorphic Design**: Adopted a high-end, semi-transparent design language across the portal and view pages.
- **Mobile-First Navigation**: Optimized the Bento Grid for touch-screens with "iOS App" inspired layout and responsiveness.

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