## ADDED Requirements

### Requirement: agent-discoverability
The system must provide a standard markdown file that AI agents can read to understand how to interact with the PixPro API.

#### Scenario: read-skill-file
- **WHEN** an AI agent reads the file `/SKILL.md`
- **THEN** it finds structured descriptions of the API endpoints, required headers, and example payloads.
