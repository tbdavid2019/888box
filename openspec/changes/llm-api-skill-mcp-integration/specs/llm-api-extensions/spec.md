## ADDED Requirements

### Requirement: image-listing
The API must provide a way to list uploaded images with pagination support.

#### Scenario: basic-list
- **WHEN** a GET request is made to `/api.php?action=list&page=1` with a valid Token
- **THEN** it returns a JSON object containing a list of image metadata (url, path, size, created_at) and pagination info (current_page, total_pages).

### Requirement: image-search
The API must allow searching for images based on their path or URL.

#### Scenario: search-by-query
- **WHEN** a GET request is made to `/api.php?action=search&q=2024` with a valid Token
- **THEN** it returns a JSON list of images whose path or URL contains "2024".

### Requirement: descriptive-errors
API error responses must include specific codes and developer-friendly messages suitable for LLM parsing.

#### Scenario: invalid-token-error
- **WHEN** a request is made with an invalid or missing Token
- **THEN** it returns 403 Forbidden with a JSON body explaining the authentication failure.
