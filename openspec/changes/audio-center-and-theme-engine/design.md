## Context

The current state of the application supports images, videos, and generic files. Audio files (such as MP3 and WAV) are rejected during the validation phase in [api_file.php](file:///Users/david/Documents/git/tbdavid2019/888box/api_file.php). The user interface is locked to the original Tokyo Night color theme and a 3-card Bento portal. This design outlines how we introduce the "聲音大廳" (Audio Center) and a dynamic theme engine supporting custom color presets.

## Goals / Non-Goals

**Goals:**
- Provide complete audio file uploading, validation, and storage capability.
- Extract audio duration and bitrate using ffprobe.
- Symmetrically implement the audio architecture modeled after the Video Center (dedicated front-end gateway, back-end logical handler, podcast RSS builder, daily upload JSON, and admin dashboard).
- Introduce a modular theme engine allowing full-site theme switching, including the new Pantone 2026 Middle East Dart styling.

**Non-Goals:**
- Audio editing or trimming features.
- Generating client-side waveform visualizations.
- Changing database backends or creating complex table relationships.

## Decisions

### Decision 1: Modular Theme Presets via CSS Custom Properties Injection
- **Choice**: Store themes as configurations in [config/themes.php](file:///Users/david/Documents/git/tbdavid2019/888box/config/themes.php) and inject them dynamically in a `<style>` block in the head of HTML templates using a helper [config/theme_helper.php](file:///Users/david/Documents/git/tbdavid2019/888box/config/theme_helper.php).
- **Rationale**: Keeps style sheets clean and allows all pages (index, views, upload apps, and admins) to align instantly to the active theme. Changing the active theme in settings updates the whole site.
- **Alternatives Considered**: Creating independent stylesheet overrides (e.g. `portal-dark.css`, `portal-desert.css`). This would lead to css code replication and high maintenance.

### Decision 2: Reuse of VideoHelper for Audio Metadata Extraction
- **Choice**: Call the existing `VideoHelper::getVideoMetadata` method to read audio metadata.
- **Rationale**: Since `VideoHelper` executes `ffprobe -show_format -show_streams`, it extracts duration and bitrate from the format header for both audio and video files. Using it for audio avoids duplicating command-line wrapping code.
- **Alternatives Considered**: Creating a new `AudioHelper` class with similar ffprobe wrappers. This introduces redundant code.

### Decision 3: Symmetrical Upload and Admin File Routing
- **Choice**: Implement `upload_audio.php`, `audio.php`, and `static/js/audio_app.js` symmetrically to the video center logic.
- **Rationale**: Symmetrical alignment makes it extremely clear how each media type operates, matching the current structure of the codebase.
- **Alternatives Considered**: Overloading the generic file upload system (`upload_file.php`) to handle audio. This would complicate document listing and upload queues, making the UX cluttered.

## Risks / Trade-offs

- **Risk**: SQLite schema migration on every PDO connection.
  - *Mitigation*: The codebase currently executes `ensureCoreSchema()` on every connection in `Database::connect()`. We follow this pattern by adding the `is_audio` column checks there, which SQLite executes in milliseconds.
- **Risk**: Missing `ffmpeg`/`ffprobe` in non-Docker execution environment.
  - *Mitigation*: If `ffprobe` is not in the system PATH, the metadata extraction falls back to 0 without breaking the file upload flow.
