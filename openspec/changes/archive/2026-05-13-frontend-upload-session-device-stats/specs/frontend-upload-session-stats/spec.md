## ADDED Requirements

### Requirement: Each upload frontend SHALL display queue-scoped session upload counts
The image, video, and file upload frontends SHALL display a session summary for the active upload queue. A session MUST be defined as the current batch queue run started by the user on that frontend. The session summary MUST update as uploads succeed and MUST reset when a new queue session begins.

#### Scenario: Session count updates during a queue run
- **WHEN** a user starts uploading a queue on one of the public upload frontends
- **THEN** that frontend MUST track the active queue as a distinct session
- **AND** the session summary MUST reflect successful uploads within that queue run only

#### Scenario: New queue run starts a new session
- **WHEN** a prior queue run has finished or been cleared and the user starts uploading a new queue
- **THEN** the frontend MUST reset the prior session summary
- **AND** the new queue run MUST begin with a fresh session count

### Requirement: Session and device stats SHALL count only confirmed successful uploads
All session counts, daily counts, and total counts MUST increment only after the frontend receives a confirmed successful upload response for the current asset type.

#### Scenario: Failed upload does not affect counts
- **WHEN** an upload attempt fails, is aborted, or returns a non-success response
- **THEN** the frontend MUST NOT increment the session summary
- **AND** the frontend MUST NOT increment the stored daily or total device stats

#### Scenario: Successful upload increments counts
- **WHEN** an upload attempt completes successfully on the frontend
- **THEN** the frontend MUST increment the active session success count
- **AND** the frontend MUST update the stored device-local daily and total counts for that asset type

### Requirement: Each upload frontend SHALL persist device-local stats separately by asset type
The image, video, and file upload frontends SHALL persist browser-local upload stats independently for their own asset types. Each asset type MUST maintain its own daily upload counts and cumulative total without affecting the other asset types.

#### Scenario: Video upload does not affect image or file stats
- **WHEN** a successful video upload is recorded on the video upload frontend
- **THEN** the frontend MUST update only the device-local video stats
- **AND** the stored image and file stats MUST remain unchanged

#### Scenario: Frontend reload preserves device-local stats
- **WHEN** a user reloads one of the upload frontends after previous successful uploads on the same browser and device
- **THEN** that frontend MUST load and display its previously stored device-local daily and total counts

### Requirement: Device-local stats SHALL include daily and cumulative totals
Each asset-type stats store MUST include a daily count keyed by date and a cumulative total count. The frontend MUST use that stored data to display at least the current day's count and the cumulative total for the current browser and device.

#### Scenario: Current day count is shown from stored daily stats
- **WHEN** device-local stats exist for the current date on a frontend
- **THEN** the frontend MUST display that date's count as the current daily upload total for that asset type

#### Scenario: Missing current day entry falls back cleanly
- **WHEN** no stored daily entry exists yet for the current date on a frontend
- **THEN** the frontend MUST display a daily count of zero without errors
- **AND** the cumulative total MUST still be displayed from stored data when available

### Requirement: Session and device-local stats SHALL be presented near the active upload workflow
Each public upload frontend SHALL render its session summary and device-local upload stats near the upload controls or active queue so users can inspect them during upload operations.

#### Scenario: User can inspect stats during upload
- **WHEN** a user is viewing or operating one of the public upload frontends
- **THEN** the session summary and device-local stats MUST be visible in the same workflow area as the upload interaction
- **AND** the user MUST NOT need to navigate to admin pages to view them
