## Context

888box is a server-rendered PHP application with static CSS and JavaScript. It is served over HTTPS and already has a root-scoped manifest, but it does not register a service worker. Public upload and view pages use dynamic APIs and may expose password-protected or user-specific content, so an indiscriminate offline cache would be unsafe.

## Goals / Non-Goals

**Goals:**

- Make the public portal installable with complete PWA metadata and icons.
- Provide a versioned service worker that makes static UI assets available offline and returns a clear offline fallback for failed navigations.
- Keep network-dependent uploads and dynamic/private content out of the service-worker cache.

**Non-Goals:**

- Offline upload queues, Background Sync, push notifications, or media downloads.
- Caching API responses, admin pages, password-protected pages, or user-uploaded assets.
- Adding a frontend framework, bundler, or third-party PWA dependency.

## Decisions

### Use a native root-scoped service worker

`/sw.js` will use browser-native Cache Storage and `fetch` events. This fits the existing zero-build architecture and gives root scope without server rewrites. Workbox was considered but would add a build/dependency workflow the project does not use.

### Precache only the offline shell and stable public static assets

The worker will precache its offline document, manifest, icons, core public CSS/JS, and bundled fonts with an explicit cache version. Requests below `/static/` will use cache-first runtime caching. Cache names will be cleaned up on activation. This avoids unbounded cache growth from the existing `?v=time()` URLs by caching requests after normalizing static asset query strings.

### Keep dynamic and sensitive paths network-only

Requests to upload or API endpoints, admin pages, generated media, and asset-delivery routes will not be cached or synthesized by the worker. Failed HTML navigations get the offline fallback; failed writes continue to fail normally so the existing UI can show an actionable error. This is safer than caching server-rendered responses that may reflect sessions, settings, or password protection.

### Add PWA metadata to every public entry page

The portal, four upload centres, and unified asset viewer will include the manifest, theme color, Apple touch metadata, and one shared registration module. Admin pages remain out of scope because they are authenticated management surfaces.

### Generate dedicated install icons from the existing brand artwork

The manifest will reference 192px and 512px PNG icons, including a maskable 512px variant. Existing favicon and Apple touch icons remain for browser compatibility.

## Risks / Trade-offs

- [Static assets with time-based query strings can produce duplicate cache entries] → Normalize `/static/` cache keys and only precache explicit files.
- [Offline behavior may suggest uploads will be queued] → Present a purpose-built offline page that states uploads require a connection; do not intercept non-GET requests.
- [Cached UI can become stale after deployment] → Use an explicit service-worker cache version, delete old caches during activation, and claim clients promptly.
- [Cross-browser install prompts vary] → Supply standards-compliant metadata and icons, but do not rely on a custom install prompt or background sync.

## Migration Plan

1. Deploy the added service worker, metadata, offline page, and icons together.
2. Verify the manifest, registration, icon requests, and offline navigation over HTTPS.
3. Roll back by reverting the feature commit; existing clients will update on their next service-worker lifecycle check. A cache version change can also invalidate all PWA Lite caches.

## Open Questions

- None for the initial PWA Lite release.

## Verification Notes

- The self-contained PWA contract script validates manifest entries, icon files, public-page metadata, service-worker scope, offline copy, and protected cache paths.
- Local static HTTP smoke checks confirm the service worker, offline page, manifest, and three install icons return successful responses with expected content types.
- Browser automation is not available in the current workspace. Before production rollout, manually install the app in Chrome or Edge over HTTPS, switch DevTools to offline, and confirm a navigation shows the offline page while upload requests are not queued.
