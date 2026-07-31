## ADDED Requirements

### Requirement: Safe text document preview
The public share page SHALL render supported text-like documents as escaped plain text when the asset is authorized and within the configured preview size limit.

#### Scenario: Small text asset is opened
- **WHEN** an authorized visitor opens a shared `.txt`, `.md`, `.json`, `.csv`, `.log`, `.yaml`, or `.yml` asset within the preview limit
- **THEN** the page displays its contents as readable plain text without interpreting the contents as HTML

#### Scenario: Text asset exceeds preview limit
- **WHEN** an authorized visitor opens a supported text-like asset larger than the preview limit
- **THEN** the page shows a clear size message and retains the download action

### Requirement: EPUB preview through authorized delivery
The public share page SHALL provide EPUB.js a same-origin, authorized inline asset URL rather than a storage-origin URL.

#### Scenario: S3-backed EPUB is opened
- **WHEN** an authorized visitor opens a shared EPUB stored in S3-compatible storage
- **THEN** the reader loads the book through the share endpoint without requiring storage-bucket CORS headers

#### Scenario: EPUB reader cannot load
- **WHEN** the EPUB reader script or book loading fails
- **THEN** the page presents a visible failure message and retains the download action

### Requirement: Share-page URL is the primary copy format
The public share page SHALL present its share-page URL as the first and selected link format.

#### Scenario: Visitor opens link formats
- **WHEN** an authorized visitor views the link-format panel
- **THEN** the selected value is the share-page URL and direct/embed formats remain explicitly selectable
