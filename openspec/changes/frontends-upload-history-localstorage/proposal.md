## Why

888box currently lets users upload images, videos, and files from three separate frontend upload centers, but each page forgets what the user just uploaded after the current interaction ends. Adding browser-local upload history makes the upload flows more usable by letting users quickly revisit, copy, and verify their recent asset URLs without needing the admin backend.

## What Changes

- Add browser `localStorage` history for each public upload frontend: image, video, and file.
- Show a "recent uploads" section below each upload interface, scoped to that frontend only.
- Store and render richer per-item metadata instead of raw URLs only:
  - image: preview thumbnail, URL, filename, timestamp
  - video: thumbnail/poster, title fallback, URL, timestamp
  - file: title or filename, share URL fallback, timestamp
- Add basic history management behaviors per frontend, including deduplication, bounded history length, and clear-history actions.
- Keep this feature fully client-side with no database or API contract changes.

## Capabilities

### New Capabilities
- `frontend-upload-history`: Persist and display per-frontend recent upload history in browser localStorage for image, video, and file upload pages.

### Modified Capabilities

## Impact

- **Frontend JS**: `static/js/main.js`, `static/js/upload/handler.js`, `static/js/video_app.js`, and `static/js/file_app.js`.
- **Frontend UI**: `upload_image.php`, `upload_video.php`, and `upload_file.php` will gain a new history section below the upload area.
- **Storage Model**: New browser-only `localStorage` keys for image, video, and file histories.
- **Backend/API**: No required server-side persistence or schema changes; existing upload responses are reused as the source of history metadata.
