# Changelog

All notable changes to this project will be documented in this file.

## [2026.5.28] - 2026-05-28

### 🐛 Fixed
- **Masked Storage Redirect Loop**: Fixed a critical production regression where cloud-backed assets could enter infinite redirects (`ERR_TOO_MANY_REDIRECTS`) because the database `images.url` field was incorrectly storing the public masked `/storage/...` URL instead of the true remote origin URL.
- **Origin/Public URL Separation**: Split runtime handling so uploads now persist the real upstream storage URL for `s3`, `oss`, and `upyun`, while public responses and admin copy links still expose the local masked domain URL.
- **Legacy Bad-Data Compatibility**: Hardened `get_file.php` and PDF inline preview flows to detect and recover from previously stored masked URLs, preventing older production rows from continuing to self-redirect after deployment.

### 📝 Docs
- **Production Maintenance Notes**: Documented the current operational differences across the four production hosts in a local-only maintenance note and excluded that note from git tracking.

## [2026.5.26] - 2026-05-26

### ✨ Added
- **Audio Center ("聲音大廳")**: Full-featured audio hosting support for `mp3`, `wav`, `aac`, `ogg`, `m4a`, `flac` as a first-class asset type, complete with drag-and-drop batches, metadata extraction, dynamic revolving CD visualizer player in `view.php`, dedicated administrative dashboard (`admin/audio.php`), and automated `storage/podcast_audio.xml` iTunes podcast feeds.
- **Pantone Theme Engine**: Added unified themes color presets registry (`config/themes.php` and `config/theme_helper.php`) featuring sleek dark Pantone 2026 Middle East Dart gold/cream/blue colors, instantly toggleable via Admin settings.
- **Cloud Storage Local-Domain Masking**: Implemented secure asset routing proxy (`get_file.php` + `storage/.htaccess` rewrite engine) to hide direct cloud S3/OSS/Upyun bucket endpoints. All copied links and download buttons dynamically mask to use the active production domain (e.g. `https://box.david888.com/storage/...`), seamlessly fetching or redirecting behind the scenes.
- **Dynamic Asset Masking Overrides**: Integrated `getMaskedUrl()` across all index, video, file, and audio administrative dashboards and unified search/list APIs (`api.php`) to ensure historical items and direct clicks strictly copy clean local domain URLs.

### 🐛 Fixed
- **Dynamic Podcast Return Links**: Fixed podcast XML feeds (`podcast.xml` and `podcast_audio.xml`) rendering loopbacks like `127.0.0.1:6767` for the "返回主站點" (Return to Main Site) link by prioritizing the browser request's dynamic `$_SERVER['HTTP_HOST']`.
- **PDF & EPUB Viewers Repair**: Upgraded PDF preview embedding from standard `<iframe>` (blocked by clickjacking safety policies) to `<embed>` tags, and declared explicit block dimensions inside the details viewport to resolve collapsing black screen blocks.
- **Document Upload ("伺服器回應異常") Fix**: Restored proper standalone entry wrappers (`realpath(__FILE__)`) and rearranged helper function locations inside `api_file.php` to prevent fatal undefined function errors.

## [2026.5.13] - 2026-05-13

### ✨ Added
- **Queue Session Upload Stats**: Added per-batch success counters to the image, video, and file upload frontends so operators can immediately see how many items in the current queue run have completed successfully.
- **Device-Local Upload Totals**: Added browser `localStorage` counters for daily and cumulative uploads on each public upload frontend, tracked separately for images, videos, and files on the current device/browser.

### 📝 Docs
- **Frontend Upload Stats Proposal**: Added and completed the OpenSpec change artifacts for `frontend-upload-session-device-stats`, documenting the client-side session and device-local stats behavior.

## [2026.5.12] - 2026-05-13

### 🐛 Fixed
- **Video Upload Queue UI Overflow**: Fixed the legacy video upload page so the `開始依序上傳` action remains reachable even when the queue grows beyond 5 items or the viewport is narrow.
- **Video Password Admin Controls**: Added admin-side edit/remove password controls for uploaded videos, closing a gap where password-protected assets could not be adjusted after upload.
- **Podcast RSS Rebuild Reliability**: Changed video publish flow to rebuild `storage/podcast.xml` from the current database state instead of incrementally appending only, reducing feed drift on long-running deployments.
- **Podcast RSS Base URL Fallback**: Hardened RSS generation so site links no longer degrade to invalid placeholders like `https://` when rebuilds run without a normal browser host context.
- **Podcast RSS Permission Recovery**: Documented and reinforced the expectation that `storage/database.db`, `storage/podcast.xml`, and `storage/podcast.xml.lock` must remain writable by container user `www-data`, which is critical for live RSS updates.
- **Legacy SQLite Asset Flags**: Added runtime/install/migration self-healing for `images.is_video` and `images.is_file`, with backfill logic for older databases that predate the unified asset model.
- **Install Default Upload Cap**: Updated installation/bootstrap defaults so fresh deployments now seed `max_uploads_per_day=100` instead of `50`.

### 📝 Docs
- **Deployment Permission Notes**: Expanded `README.md` to explain Docker ownership expectations for `storage/`, how to diagnose `podcast.xml` permission regressions, and when to use `docker compose up -d --build` during upgrades.

## [2026.5.11] - 2026-05-08

### ✨ Added
- **Per-Frontend Upload History**: Added browser `localStorage` history for `upload_image.php`, `upload_video.php`, and `upload_file.php`, with per-page recent upload UI, copy/open actions, and clear-history controls.
- **Environment Template**: Added `.env.example` covering local, S3, OSS, UpYun, upload limits, and SMTP-related variables.

### 🐛 Fixed
- **Image Upload Auth Regression**: Fixed `api.php` same-origin image uploads failing with `身分驗證無效` by restoring session-aware validation and allowing same-host referers.
- **Upload Size Limit Fallbacks**: Fixed image and file uploads treating missing `max_file_size` config as `0`, which caused false upload rejections and `0MB` messaging on older databases.
- **Core Config Self-Healing**: Added automatic seeding of missing core config rows (`max_file_size`, `max_video_size`, `max_uploads_per_day`, `output_format`, etc.) during runtime bootstrap, install, and migration flows.
- **Centralized Schema Self-Healing**: Moved legacy SQLite column backfills into `config/database.php`, removed scattered runtime `ALTER TABLE` hacks from video entrypoints, and aligned install/migration table definitions with the current production schema.
- **Unified Schema Bootstrap Source**: Introduced `config/schema.php` as the single source of truth for core table creation, image-column backfills, config normalization, and default config seeding across runtime bootstrap, web install, shell install, and MySQL-to-SQLite migration.
- **Legacy Install Script S3 Keys**: Corrected `install.sh` to write `s3_access_key_id` / `s3_access_key_secret` instead of obsolete `s3_key` / `s3_secret`, and aligned prompts with the actual config model.
- **S3 Bootstrap Script**: Updated `setup_s3.sh` to emit `S3_ACL=public-read` and apply a public-read bucket policy so fresh AWS S3 deployments do not return `AccessDenied` for uploaded assets.
- **Frontend Size Messaging**: Fixed sub-1MB upload limit messages so they display `KB` or accurate `MB` values instead of `0MB`.
- **Image Frontend Config Read**: Fixed `static/js/main.js` so the image frontend reads `data-max-file-size` from the correct module script tag.
- **Video/File Upload Validation**: Hardened `video.php` and `api_file.php` size-limit and auth-related behavior to stay aligned with the unified upload gateway.
- **Rendered Skill Format**: Updated `skill.php` to render a standard `SKILL.md`-style document with YAML frontmatter while still injecting the live Base URL and token hints for the current deployment.
- **Public Skill Auth Guidance**: Corrected `skill.php` and `api.php` so public upload actions match the footer-facing product intent: public upload flows can run without a token when login restriction is off, while admin-style actions remain token-protected.

### 📝 Docs
- **README Refresh**: Updated install and S3 sections to document `.env.example`, `setup_s3.sh`, correct S3 variable names, and public-read requirements for AWS S3 buckets.

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
