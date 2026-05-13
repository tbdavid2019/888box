## 1. Shared Stats Foundation

- [x] 1.1 Add a shared browser-local stats helper for image, video, and file upload frontends that loads, saves, and increments per-type device stats.
- [x] 1.2 Define per-frontend stats keys and a normalized stats shape containing date-keyed daily counts plus a cumulative total.

## 2. Session Counter Integration

- [x] 2.1 Update the image upload frontend to reset queue-session state on a new batch run and increment the session success count only on confirmed successful uploads.
- [x] 2.2 Update the video upload frontend to track the active queue session and increment the session success count only on confirmed successful uploads.
- [x] 2.3 Update the file upload frontend to track the active queue session and increment the session success count only on confirmed successful uploads.

## 3. Device Stats Integration

- [x] 3.1 Update the image upload success flow to increment only image device-local daily and total stats.
- [x] 3.2 Update the video upload success flow to increment only video device-local daily and total stats.
- [x] 3.3 Update the file upload success flow to increment only file device-local daily and total stats.

## 4. Frontend Stats UI

- [x] 4.1 Add a session and device-stats display to the image upload frontend near the active upload workflow.
- [x] 4.2 Add a session and device-stats display to the video upload frontend near the active upload workflow.
- [x] 4.3 Add a session and device-stats display to the file upload frontend near the active upload workflow.
- [x] 4.4 Ensure each frontend reloads and renders its stored device-local stats without affecting the other asset types.

## 5. Verification

- [ ] 5.1 Verify that successful uploads update session, daily, and total counts for image, video, and file frontends.
- [ ] 5.2 Verify that failed, canceled, or aborted uploads do not increment session or device-local stats.
- [ ] 5.3 Verify that starting a new queue run resets only the current session summary while keeping device-local totals intact.
