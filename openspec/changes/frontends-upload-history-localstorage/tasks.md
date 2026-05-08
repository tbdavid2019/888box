## 1. Shared History Foundation

- [x] 1.1 Add a shared frontend history helper for loading, saving, deduplicating, truncating, and clearing browser-local upload history.
- [x] 1.2 Define separate storage keys and item normalization rules for image, video, and file histories.

## 2. Upload Success Integration

- [x] 2.1 Integrate image upload success handling so each successful image response writes a history entry with preview, URL, label, and timestamp.
- [x] 2.2 Integrate video upload success handling so each successful video response writes a history entry with URL, title/filename fallback, thumbnail fallback, and timestamp.
- [x] 2.3 Integrate file upload success handling so each successful file response writes a history entry that prefers `share_url`, falls back to `url`, and stores title/filename metadata with timestamp.

## 3. Frontend History UI

- [x] 3.1 Add a recent uploads section below the image upload interface and render image-only history entries with preview, link, and actions.
- [x] 3.2 Add a recent uploads section below the video upload interface and render video-only history entries with thumbnail/title, link, and actions.
- [x] 3.3 Add a recent uploads section below the file upload interface and render file-only history entries with title/filename, link, and actions.
- [x] 3.4 Add per-frontend clear-history actions and empty-state behavior without affecting other frontends' histories.

## 4. Verification

- [ ] 4.1 Verify that each frontend reloads and renders only its own stored history after successful uploads.
- [ ] 4.2 Verify copy, open, deduplication, bounded history length, and clear-history behaviors for image, video, and file histories.
