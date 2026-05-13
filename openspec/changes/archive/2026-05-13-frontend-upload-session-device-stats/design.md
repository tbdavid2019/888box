## Context

888box has three separate public upload frontends for images, videos, and files, and each frontend already contains a success path where the client knows when one upload has completed successfully. Video and file uploads are queue-based and image uploads already track a simple success count for the active batch, but there is no shared product behavior that surfaces queue-scoped session progress or simple per-device upload totals across the three frontends.

The requested behavior is explicitly lightweight and browser-local. Users want to know how many uploads succeeded in the current queue run, and they want a simple daily and cumulative count for the device and browser they are using. This must not introduce backend persistence, reporting APIs, or account-bound state.

## Goals / Non-Goals

**Goals:**
- Show a queue-scoped session summary for image, video, and file upload frontends.
- Count only confirmed successful uploads toward session, daily, and total metrics.
- Persist per-device upload stats in browser `localStorage` separately for image, video, and file frontends.
- Keep the UI simple and close to the active upload workflow so frequent uploaders can glance at progress.
- Reuse shared helper logic for loading, incrementing, and resetting client-side stats where practical.

**Non-Goals:**
- No server-side or account-level analytics.
- No cross-device synchronization.
- No retroactive backfill from historical uploads already stored in the backend.
- No unified combined counter across all asset types.
- No formal reporting, charting, or admin dashboard changes.

## Decisions

### 1. Treat the active queue run as the definition of a session
- **Decision**: A session begins when the user starts uploading the current queue and ends when that queue has no remaining pending items.
- **Rationale**: The user explicitly defined session as "this batch queue," which matches the existing upload flow and is easiest to understand during large upload jobs.
- **Alternative considered**: Treat the entire page lifetime as one session. Rejected because it becomes ambiguous after clearing and rebuilding queues.

### 2. Keep session counters in runtime state, not `localStorage`
- **Decision**: Session counters remain in page memory and reset with each new queue run.
- **Rationale**: Session data is transient by definition and should not survive reloads or unrelated future queues.
- **Alternative considered**: Persist session state to `sessionStorage` or `localStorage`. Rejected because it creates confusing carry-over after refreshes and is not needed for the requested workflow.

### 3. Persist device stats in per-frontend `localStorage` buckets
- **Decision**: Store separate stats keys for image, video, and file frontends, for example `888box.stats.image`, `888box.stats.video`, and `888box.stats.file`.
- **Rationale**: The user wants each asset type counted independently, and separate keys keep lookup and reset behavior simple.
- **Alternative considered**: One shared stats object with a nested `type` field. Rejected because it adds unnecessary branching for a very small feature.

### 4. Store daily counts as a date-keyed map plus a cumulative total
- **Decision**: Persist a simple structure containing `daily` keyed by local calendar date and `total` as a monotonic success count.
- **Rationale**: This supports today's count cleanly while keeping the door open for later lightweight expansions such as yesterday or recent-days views, without changing the storage model again.
- **Alternative considered**: Store only `{ date, count, total }` for the current day. Rejected because date rollover handling becomes more brittle and future extension would require a data-shape migration.

### 5. Increment all counters only on confirmed successful upload responses
- **Decision**: Session and device stats update only inside each frontend's existing success path.
- **Rationale**: This keeps the counts aligned with user-visible completed uploads and avoids inflating numbers due to queued, failed, canceled, or retried items.
- **Alternative considered**: Increment when files enter the queue or when upload starts. Rejected because those states do not mean the upload actually completed.

### 6. Render stats near the active queue or upload controls
- **Decision**: Each frontend will show the session summary and device stats near the current upload workflow rather than burying them in recent-history or admin areas.
- **Rationale**: These numbers are operational feedback during upload, not archival browsing data.
- **Alternative considered**: Place stats only in the recent uploads section. Rejected because users need the counts while uploading, not after scrolling to a secondary panel.

## Risks / Trade-offs

- **[Risk] Browser-local counts can be cleared by cache cleanup or different browsers** → **Mitigation**: Frame the UI explicitly as device-local convenience stats, not authoritative totals.
- **[Risk] The three upload frontends have slightly different queue and success handling styles** → **Mitigation**: Share only the stat persistence rules and keep session wiring local to each page's existing success flow.
- **[Risk] Daily maps can grow indefinitely over very long periods** → **Mitigation**: Keep the structure simple now and allow optional trimming logic during implementation if needed.
- **[Trade-off] Counts will not include uploads performed through other entrypoints or APIs** → **Mitigation**: Scope the feature clearly to uploads performed through the corresponding public frontend.

## Migration Plan

1. Add a shared browser-local stats helper for per-frontend totals and date-keyed daily counts.
2. Add lightweight session-summary state and rendering hooks to image, video, and file upload frontends.
3. Update the UI markup in each upload page to display session and device-local stats near the active upload flow.
4. Release without backend migrations.
5. If rollback is needed, remove the UI and helper hooks; stale browser-local stats keys can remain harmlessly in user browsers.

## Open Questions

- Whether the session summary should show only success counts or also explicit failure counts alongside the total queue size.
- Whether the UI should provide a user-facing way to clear device-local stats independently from any recent-upload history feature.
