## Context

888box now has three separate public upload frontends:

- `upload_image.php` backed by `static/js/main.js` and `static/js/upload/handler.js`
- `upload_video.php` backed by `static/js/video_app.js`
- `upload_file.php` backed by `static/js/file_app.js`

Each frontend already renders immediate upload results, but none persist recent uploads across page reloads. The requested behavior is explicitly frontend-local: each upload page must remember its own uploaded URLs and show them below the upload interface without adding server-side persistence or admin dependencies.

This change crosses multiple frontend entrypoints, but the data source is already available because each page receives successful upload metadata from existing responses.

## Goals / Non-Goals

**Goals:**
- Persist recent upload history in browser `localStorage` separately for image, video, and file frontends.
- Render a per-frontend "recent uploads" UI section below each upload interface.
- Store richer display metadata so history is useful at a glance, not just a raw URL list.
- Reuse one shared client-side history utility where practical to keep behavior consistent.
- Keep the feature fully client-side and backward-compatible with existing upload flows.

**Non-Goals:**
- No database schema, API contract, or admin backend changes.
- No cross-device sync or account-bound upload history.
- No unified shared history across all three frontends.
- No replacement of the existing single-upload success/result UI; history is additive.

## Decisions

### 1. Use per-frontend `localStorage` keys rather than a shared history bucket
- **Decision**: Store history in separate keys such as `888box.history.image`, `888box.history.video`, and `888box.history.file`.
- **Rationale**: The user explicitly wants each frontend to remember only its own uploads. Separate keys keep scoping simple and avoid filtering mistakes.
- **Alternative considered**: A single shared history array with a `type` field. Rejected because it adds filtering complexity while providing no immediate product benefit.

### 2. Introduce a small shared history helper, but keep rendering page-specific
- **Decision**: Centralize common operations such as load, save, dedupe, truncate, clear, and timestamp normalization in a reusable frontend helper. Each page will still define its own item shape and rendering markup.
- **Rationale**: All three pages need the same persistence rules, but image/video/file cards differ enough that forcing one renderer would create brittle abstractions.
- **Alternative considered**: Three completely separate implementations. Rejected because it would duplicate state handling and increase drift.

### 3. Persist display-ready metadata per asset type
- **Decision**: Store enough metadata to render immediately useful cards:
  - image: `url`, preview/thumbnail URL, filename, `createdAt`
  - video: `url`, `thumbnailUrl`, title fallback, `createdAt`
  - file: `url` or `shareUrl`, title/filename fallback, mime or extension hint, `createdAt`
- **Rationale**: The history UI should help users identify past uploads visually or by title without opening the admin area.
- **Alternative considered**: Store URL only. Rejected because it makes the feature much less usable, especially for videos and files.

### 4. Write to history only after confirmed successful upload responses
- **Decision**: Append or refresh a history entry only inside each page's upload success path.
- **Rationale**: This avoids false history entries from failed uploads, aborted requests, or pre-upload queue items.
- **Alternative considered**: Save on queue add or upload start. Rejected because it would persist items that never actually uploaded.

### 5. Use deduplicated, bounded history lists
- **Decision**: Deduplicate by canonical primary URL and move repeated uploads to the top. Keep a fixed-length list per frontend.
- **Rationale**: Users want "recent useful history", not an infinitely growing log. Deduping keeps repeated uploads readable.
- **Alternative considered**: Unbounded append-only storage. Rejected due to storage growth and noisy UX.

### 6. Place history sections below the upload interface, not in admin or portal
- **Decision**: Render the history block directly below each frontend's upload UI.
- **Rationale**: This matches the user's requested placement and keeps the feature in the same workflow context as upload, copy, and open actions.
- **Alternative considered**: Portal-level or admin-only history. Rejected because it adds navigation friction.

## Risks / Trade-offs

- **[Risk] localStorage quota or malformed stored data** → **Mitigation**: Guard JSON parsing, fall back to empty arrays, and keep bounded history sizes.
- **[Risk] Existing frontend scripts have different styles and structure** → **Mitigation**: Share only persistence helpers and keep DOM rendering localized to each page.
- **[Risk] Thumbnail or title data may be missing in some responses** → **Mitigation**: Define deterministic fallbacks such as filename, generic label, or plain link rows.
- **[Trade-off] Client-only history is browser-specific** → **Mitigation**: Accept this as intended behavior; do not frame it as a global account history feature.

## Migration Plan

1. Add the shared browser history helper and integrate success hooks in image, video, and file upload flows.
2. Add the "recent uploads" section markup and rendering logic to each upload frontend.
3. Release without backend migration requirements.
4. If rollback is needed, remove the UI hooks; stale `localStorage` keys can remain harmlessly in browsers.

## Open Questions

- Final per-frontend history length: `10`, `20`, or another value.
- Whether image history should show the final uploaded asset URL as the thumbnail source or preserve a separate preview field when available.
