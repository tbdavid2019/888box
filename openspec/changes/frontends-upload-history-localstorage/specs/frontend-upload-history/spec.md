## ADDED Requirements

### Requirement: Image upload frontend SHALL persist recent image uploads locally
The image upload frontend SHALL store successful image uploads in browser-local history that is scoped to the image upload page only. Each stored entry MUST include the uploaded image URL, a displayable preview source, a filename or equivalent label, and a timestamp. The frontend MUST deduplicate entries by uploaded URL and MUST keep only the most recent bounded set of entries.

#### Scenario: Successful image upload is persisted
- **WHEN** the image upload frontend receives a successful upload response containing an uploaded image URL
- **THEN** the frontend stores or refreshes a history entry in image-specific browser storage
- **AND** the stored entry includes a preview-capable source, a human-readable label, and the upload timestamp

#### Scenario: Re-upload moves an existing image entry to the top
- **WHEN** the user successfully uploads an image whose uploaded URL already exists in the image history
- **THEN** the frontend MUST update the existing entry instead of creating a duplicate
- **AND** the refreshed entry MUST appear as the most recent item

### Requirement: Video upload frontend SHALL persist recent video uploads locally
The video upload frontend SHALL store successful video uploads in browser-local history that is scoped to the video upload page only. Each stored entry MUST include the uploaded video URL, a title or filename fallback, a thumbnail/poster when available, and a timestamp. The frontend MUST deduplicate entries by uploaded URL and MUST keep only the most recent bounded set of entries.

#### Scenario: Successful video upload is persisted
- **WHEN** the video upload frontend receives a successful upload response for a video
- **THEN** the frontend stores or refreshes a history entry in video-specific browser storage
- **AND** the stored entry includes the video URL, a display title or filename fallback, and a timestamp

#### Scenario: Video upload without thumbnail still appears in history
- **WHEN** a successful video upload response does not contain a thumbnail or poster URL
- **THEN** the frontend MUST still create a usable history entry
- **AND** the history UI MUST render the entry using title-first or filename-first fallback content

### Requirement: File upload frontend SHALL persist recent file uploads locally
The file upload frontend SHALL store successful file uploads in browser-local history that is scoped to the file upload page only. Each stored entry MUST prefer the share URL when available, and MUST also include a title or filename fallback plus a timestamp. The frontend MUST deduplicate entries by the primary stored link and MUST keep only the most recent bounded set of entries.

#### Scenario: Successful file upload prefers share URL
- **WHEN** the file upload frontend receives a successful upload response containing both `share_url` and `url`
- **THEN** the frontend MUST store the share URL as the primary history link
- **AND** the history UI MUST expose that primary link for copy and open actions

#### Scenario: File upload falls back when share URL is absent
- **WHEN** the file upload frontend receives a successful upload response that lacks `share_url` but includes `url`
- **THEN** the frontend MUST still create a file history entry using the available URL

### Requirement: Each upload frontend SHALL display a recent uploads section below the upload interface
Each of the image, video, and file upload frontends SHALL render a recent uploads section beneath its upload interface. The section MUST show only entries for that frontend's asset type and MUST present enough metadata for users to identify previously uploaded assets without opening the admin area.

#### Scenario: Page load renders existing local history
- **WHEN** a user opens one of the upload frontends and browser history exists for that frontend
- **THEN** the page MUST render the recent uploads section below the upload interface
- **AND** the section MUST show only that frontend's stored history entries

#### Scenario: Empty history hides or empties the section cleanly
- **WHEN** a user opens one of the upload frontends and no valid browser history exists for that frontend
- **THEN** the page MUST present an empty-state or hidden recent uploads section without errors

### Requirement: Recent upload entries SHALL support direct reuse actions
Each recent upload entry shown in the frontend history UI SHALL provide direct actions to reuse the stored asset link. At minimum, the UI MUST provide copy and open actions, and each frontend MUST provide a way to clear its own stored history.

#### Scenario: User copies a recent upload link
- **WHEN** the user clicks the copy action for a recent upload entry
- **THEN** the frontend MUST copy that entry's primary link to the clipboard

#### Scenario: User clears one frontend history
- **WHEN** the user triggers clear-history on one upload frontend
- **THEN** the frontend MUST remove only that frontend's stored history entries
- **AND** the histories for the other two upload frontends MUST remain unchanged
