## Context

The shared PWA module currently registers the service worker but does not surface an installation action. Chrome controls automatic Android install prompts and requires a user gesture before a site can open its native installation confirmation.

## Goals / Non-Goals

**Goals:**

- Make an eligible browser's installation opportunity visible from every existing public PWA entry page.
- Open the native browser installation confirmation only after the user taps the in-page action.
- Keep the UI dependency-free and remove it once no longer useful.

**Non-Goals:**

- Bypassing browser user-gesture or installability rules.
- Showing a permanent banner when the browser did not expose an installation event.
- Changing manifest metadata, service-worker cache rules, uploads, or admin pages.

## Decisions

### Listen for `beforeinstallprompt` in the shared PWA module

The browser event is stored with `preventDefault()` and triggers an injected install card. This works on every public page that already loads `pwa.js`, while unsupported browsers receive no new interface. A persistent always-visible banner was rejected because it could promise an installation action that the browser cannot honor.

### Use a user-clicked button to call `prompt()`

The button invokes the saved event from its click handler, satisfying Chrome's user-activation requirement. Automatically calling `prompt()` on page load was rejected because browsers block or ignore it.

### Keep styles and markup in the shared module

The module creates a compact, accessible dialog and controls its lifecycle without adding a new CSS file or changing six PHP templates. The content is fixed project-owned markup, not user input.

## Risks / Trade-offs

- [Chrome does not emit an install event for every visit] → Show the card only when the browser confirms eligibility; users retain the Chrome menu fallback.
- [A prompt appears above page-specific UI] → Use a fixed high-z-index compact card and allow immediate dismissal.
- [Install prompt invocation fails] → Catch and log the error, then clean up the temporary UI.

## Migration Plan

1. Deploy the updated shared PWA module with its regression test.
2. Reload a public page over HTTPS in Android Chrome; when the card appears, tap its install action and verify Chrome's confirmation.
3. Roll back by reverting this feature commit; no data or cache migration is required.

## Open Questions

- None.
