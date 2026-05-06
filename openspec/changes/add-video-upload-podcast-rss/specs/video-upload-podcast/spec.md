## ADDED Requirements

### Requirement: Video upload endpoint accepts valid video files
The system SHALL provide an endpoint to receive video file uploads and process them through the standard upload pipeline.

#### Scenario: Successful video upload with metadata extraction
- **WHEN** a user sends a POST request to /video.php with a valid video file
- **THEN** the system shall:
    1. Store the video file using StorageHelper
    2. Use FFmpeg to extract metadata (duration, resolution, bitrate)
    3. Use FFmpeg to generate a thumbnail image (cover.jpg)
    4. Store the thumbnail using StorageHelper
    5. Return a success response with video and thumbnail URLs

#### Scenario: Rejected invalid video file type
- **WHEN** a user uploads a file with non-video extension (e.g., .txt, .exe) or invalid MIME type
- **THEN** the system shall return an error indicating unsupported file type

#### Scenario: Rejected oversized video file
- **WHEN** a user uploads a video file exceeding the maximum size limit
- **THEN** the system shall return an error indicating file too large

### Requirement: System generates RSS podcast XML for uploaded videos
The system SHALL create and maintain an RSS 2.0 compliant podcast feed containing all uploaded videos, ensuring thread safety.

#### Scenario: RSS feed created/updated with file locking
- **WHEN** a video is uploaded
- **THEN** the system shall acquire an exclusive lock on podcast.xml before updating it
- **AND** append new items with proper metadata (including duration and thumbnail link)
- **AND** release the lock after completion

#### Scenario: RSS feed contains rich podcast fields
- **WHEN** generating RSS XML for a video
- **THEN** each item shall include: title (filename), description, enclosure URL (video), itunes:image (thumbnail), itunes:duration (extracted via FFmpeg), and publication date

### Requirement: System generates daily video list JSON
The system SHALL create a daily JSON file listing all videos uploaded on that day, ensuring thread safety.

#### Scenario: Daily JSON updated with file locking
- **WHEN** a video is uploaded
- **THEN** the system shall acquire an exclusive lock on the daily videos.json before updating it
- **AND** include rich metadata (duration, resolution, size) in the entry
- **AND** release the lock after completion

#### Scenario: Daily JSON contains video metadata
- **WHEN** generating the daily video list
- **THEN** each entry shall include: filename, upload timestamp, file size, storage URL, thumbnail URL, resolution, and duration (extracted via FFmpeg)