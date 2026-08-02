## Why

The public share page accepts text and EPUB documents but gives text files a generic unsupported state and fails to load EPUB files from S3-backed storage. Shared links should provide a useful, safe reading experience without exposing storage origins or relying on broken third-party URLs.

## What Changes

- Add safe inline delivery for eligible text and EPUB assets through the existing authorized share route.
- Render text-like documents in a readable, copyable plain-text preview with a clear size limit and download fallback.
- Restore EPUB rendering with a working reader script and a same-origin source that avoids storage CORS failures.
- Make the share-page URL the first copy option, while retaining direct and embed links as explicit secondary options.

## Capabilities

### New Capabilities

- `document-share-preview`: Provides secure in-browser previews for supported text and EPUB documents on public share pages.

### Modified Capabilities

- None.

## Impact

- Updates `view.php` and adds a focused regression contract test under `tests/`.
- Uses an external, version-pinned EPUB reader script; no database schema, upload API, or storage configuration changes are required.
