## Why

888box has installable PWA metadata, but Android Chrome decides independently whether to show its own install prompt. Users need a visible, on-page entry point whenever Chrome has made the site eligible for installation.

## What Changes

- Add a small shared install card that appears after a compatible browser emits its PWA installation event.
- Let an explicit user tap on the card open the browser's native install confirmation.
- Remove the card after installation or when the user dismisses it, while keeping non-PWA browsers unchanged.

## Capabilities

### New Capabilities

- `pwa-install-cta`: Provides a user-controlled in-page installation entry point for eligible PWA browsers.

### Modified Capabilities

- None.

## Impact

- Updates the shared `static/js/pwa.js` module and its self-contained Node contract test.
- Adds no dependencies, API changes, storage changes, or service-worker cache scope changes.
