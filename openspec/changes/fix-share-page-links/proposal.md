## Why

Successful uploads expose raw storage URLs in several user-facing actions, bypassing the branded share page and potentially exposing storage-provider origins. Every user-visible open or copy action must use the asset's share URL instead.

## What Changes

- Make the portal's universal upload actions prefer the API `share_url`.
- Store and render share URLs for image, video, audio, and file upload history actions.
- Preserve raw URLs only where they are required for media playback, thumbnails, or explicitly labelled direct-link controls.

## Capabilities

### New Capabilities

- `asset-share-link-consistency`: Ensures user-facing asset navigation and copy actions use the share page URL.

### Modified Capabilities

- None.

## Impact

- Updates the portal inline upload workflow and public upload-centre history scripts.
- Adds a self-contained regression test; no database or API response changes are required.
