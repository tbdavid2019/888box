## Context

The upload API already returns a raw delivery URL in `url` and a token-backed share page in `share_url`. The portal upload queue and several browser-local upload-history renderers incorrectly use the raw value for user navigation and copying.

## Goals / Non-Goals

**Goals:**

- Use `share_url` whenever the user opens or copies an asset link.
- Preserve raw delivery URLs for previews, media players, thumbnails, and explicit direct-link controls.
- Keep history entries compatible with existing browser-local data by falling back to `url` when no `shareUrl` exists.

**Non-Goals:**

- Changing the storage URL saved in the database or changing API response fields.
- Rewriting existing direct-link controls or media preview sources.
- Migrating browser-local upload history.

## Decisions

### Treat `share_url` as the user-facing navigation field

Each upload UI will choose `share_url` first for copy/open actions. This uses the existing API contract and avoids additional endpoint work.

### Preserve `url` as the delivery field

Raw URLs remain necessary for image thumbnails, audio/video playback, and intentionally labelled direct links. Replacing those sources with a share page would break rendering and embedding behavior.

### Support historical browser-local entries

History renderers will use `entry.shareUrl || entry.url`, so old entries continue to work while newly created entries use the share page.

## Risks / Trade-offs

- [Older assets may have no share URL] → Fall back to their stored URL; database backfill already generates tokens where possible.
- [Changing raw media sources would break previews] → Limit the change to open/copy action URLs only.

## Migration Plan

1. Deploy the updated client scripts and portal template.
2. Upload one asset of each type and verify every open/copy action uses `/v/<token>`.
3. Roll back by reverting the commit; no data migration is involved.

## Open Questions

- None.
