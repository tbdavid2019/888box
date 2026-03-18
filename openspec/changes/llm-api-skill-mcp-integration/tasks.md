## 1. API Extensions

- [x] 1.1 Update `api.php` to support `action` parameter for `list` and `search`.
- [x] 1.2 Implement paginated image listing in `api.php`.
- [x] 1.3 Implement basic substring search for images in `api.php`.
- [x] 1.4 Improve error handling in `api.php` with structured JSON error codes.

## 2. AI Skill Documentation

- [x] 2.1 Create `/SKILL.md` with system overview and tool documentation.
- [x] 2.2 Add usage examples and payload schemas to `/SKILL.md`.

## 3. MCP Integration

- [x] 3.1 Create `/mcp.php` as a standalone MCP server entry point.
- [x] 3.2 Implement JSON-RPC 2.0 protocol handler for `stdio` transport.
- [x] 3.3 Expose `upload_image`, `list_images`, and `get_image_details` as tools.
- [x] 3.4 Ensure the MCP server respects existing token-based authentication.

## 4. Verification

- [ ] 4.1 Perform smoke tests on new API endpoints.
- [ ] 4.2 Validate MCP server functionality using an MCP client.
