## Why

888box is already served over HTTPS and exposes a web app manifest, but it cannot be installed reliably or opened when the network is temporarily unavailable because it has no service worker or offline experience.

## What Changes

- Add a PWA Lite experience that makes the public portal installable and provides an offline fallback for its cached application shell.
- Add versioned service-worker caching for public static UI assets only.
- Add consistent PWA metadata to public portal, upload, and asset-view pages.
- Expand the manifest with install-ready and maskable application icons.
- Keep uploads, dynamic API requests, admin pages, password-protected assets, and user-uploaded media out of service-worker caches.

## Capabilities

### New Capabilities

- `pwa-lite`: Provides installable public pages, offline shell recovery, and privacy-safe cache behavior.

### Modified Capabilities

- None.

## Impact

- Adds a root service worker, a small client registration module, an offline page, and PWA icon assets.
- Updates the manifest and public PHP page heads; no API response shape, database schema, or upload flow changes.
- Requires manual browser verification on HTTPS deployments; no new runtime dependency or build tool is introduced.
