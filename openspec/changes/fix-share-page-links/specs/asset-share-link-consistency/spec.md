## ADDED Requirements

### Requirement: User-facing upload actions use share pages
The system SHALL use an asset's API `share_url` for user-facing open and copy actions after upload. It MUST fall back to the delivery URL only when no share URL is available.

#### Scenario: Portal universal upload succeeds
- **WHEN** a user uploads any asset through the portal's universal uploader
- **THEN** the `複製連結` and `查看` actions use the returned `share_url`

#### Scenario: Dedicated upload history is rendered
- **WHEN** a user views recent image, video, audio, or file uploads with a stored share URL
- **THEN** the displayed link, copy action, and open action use that share URL

### Requirement: Media delivery URLs remain available where needed
The system SHALL retain raw delivery URLs for media previews, thumbnails, players, and explicitly labelled direct-link controls.

#### Scenario: Administrator previews a media asset
- **WHEN** an administrator loads an image, video, or audio preview
- **THEN** the preview can continue to use the raw delivery URL while share controls use the share page URL
