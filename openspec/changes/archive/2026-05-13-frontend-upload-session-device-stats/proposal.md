## Why

888box's public upload frontends already let users upload images, videos, and files in batches, but they do not clearly show how many uploads succeeded within the current queue run, and they do not give frequent uploaders a simple device-local sense of daily or cumulative upload volume. Adding lightweight per-frontend session counters and browser-local device stats improves operator confidence during heavy upload workflows without introducing backend reporting or account-bound state.

## What Changes

- Add a queue-scoped session summary to each public upload frontend for image, video, and file uploads.
- Show per-batch success counts based on the current queue run, with counts updating only after confirmed successful uploads.
- Persist simple browser-local device stats for each asset type separately: daily upload count and total upload count.
- Render those device-local stats directly in each upload frontend near the active upload workflow.
- Keep the feature fully client-side with no database, API, or admin reporting changes.

## Capabilities

### New Capabilities
- `frontend-upload-session-stats`: Track and display per-frontend queue session upload counts and device-local upload totals for image, video, and file upload pages.

### Modified Capabilities

## Impact

- **Frontend JS**: `static/js/upload/handler.js`, `static/js/video_app.js`, `static/js/file_app.js`, and shared browser-local helper logic under `static/js/`.
- **Frontend UI**: `upload_image.php`, `upload_video.php`, and `upload_file.php` will gain a lightweight session/stats display near the upload queue or upload controls.
- **Storage Model**: New browser-only `localStorage` keys for per-device upload stats by asset type.
- **Backend/API**: No required server-side persistence, schema, or response-contract changes.
