## Why

The current platform lacks support for uploading, hosting, and previewing audio files (such as MP3 and WAV). Users attempting to upload audio files face format errors, and there is no media player or admin dashboard to manage these assets. Additionally, the platform needs a dynamic and configurable styling engine to apply professional color themes, specifically the Pantone 2026 Middle East Dart theme, with extensibility for future presets.

## What Changes

- **Audio File Uploading**: Support uploading `mp3`, `wav`, `aac`, `ogg`, `m4a`, and `flac` files through a dedicated Audio Uploader gateway and the unified API.
- **Audio Metadata Extraction**: Automatically extract audio metadata (such as duration and bitrate) using ffprobe.
- **Audio Podcast RSS Feed**: Generate and automatically rebuild an audio-specific podcast RSS XML feed at `/storage/podcast_audio.xml`.
- **Responsive 2x2 Bento Portal Layout**: Add a fourth card ("聲音大廳") on the homepage bento grid to form a balanced 2x2 layout.
- **Micro-Animated Audio Disc Player**: Embed a premium audio player page with a revolving disc micro-animation that animates dynamically during playback.
- **Audio Admin Dashboard**: Create a dedicated audio administration screen to view, play, edit, and delete audio assets.
- **Dynamic Styling & Theme Engine**: Save styling configuration in the database/config registry, supporting presets like "Tokyo Night" (default) and "Middle East Dart" (Pantone 2026), and apply overrides dynamically to all pages.

## Capabilities

### New Capabilities
- `audio-center`: Handles audio file upload processing, ffprobe duration extraction, podcast RSS compilation, custom music player visualizer, and audio admin dashboard management.
- `theme-engine`: Defines preset style registries and provides dynamic override rendering to all UI screens.

### Modified Capabilities
- `unified-portal`: Modifies the home portal structure by expanding the Bento grid to a 2x2 layout with the addition of the "聲音大廳" card.
- `file-hosting`: Excludes audio files from document uploads and document listings, ensuring they are routed to the audio center.

## Impact

- **Database**: Add `is_audio` column to `images` table and seed new system config parameters (`active_theme`, `max_audio_size`).
- **Unified Upload API**: Route audio MIME types to the audio upload processor.
- **Unified List API**: Add `type=audio` option and exclude audio assets from `type=file`.
- **View/Preview Page**: Support `<audio>` media player and custom vinyl animation.
- **Admin Dashboard**: Update Image, Document, and Video dashboards to align with theme colors, and add the Audio Management dashboard.
