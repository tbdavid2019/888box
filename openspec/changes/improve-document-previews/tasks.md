## 1. Safe document delivery

- [x] 1.1 Add authorized, type-restricted inline delivery for text and EPUB assets in the share route.
- [x] 1.2 Add a regression contract test covering the preview routes and safe text rendering requirements.

## 2. Share-page reader experience

- [x] 2.1 Render supported small text-like files in a readable plain-text viewer with an over-limit fallback.
- [x] 2.2 Replace the broken EPUB source and load books through the same-origin inline URL with visible error fallback.
- [x] 2.3 Make share-page URLs the default link format while retaining direct and embed formats.

## 3. Verification and rollout

- [x] 3.1 Run PHP syntax, contract, HTTP, and available browser-tooling checks.
- [x] 3.2 Commit, push, and update all four production hosts.
