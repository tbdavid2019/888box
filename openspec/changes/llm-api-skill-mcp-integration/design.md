## Context

PixPro is a lightweight PHP-based image hosting system. While it provides a functional upload API, it lacks structured discovery mechanisms (listing, searching) and modern integration layers for AI agents (Skill documentation, Model Context Protocol). 

## Goals / Non-Goals

**Goals:**
- Provide a standardized way for LLMs to manage images (upload, list, search).
- Implement a Model Context Protocol (MCP) server for deep integration with AI tools.
- Create a `SKILL.md` for discovery by agentic systems.
- Maintain existing security (Token/Session boot) across all new endpoints.
- Ensure PHP 7.2 compatibility.

**Non-Goals:**
- Adding complex image processing features (e.g., AI tagging) in this phase.
- Implementing a full OAuth flow if not already present.
- Moving to a new database engine.

## Decisions

### 1. API Extensions
- **Action-based Routing**: Update `api.php` to handle `GET` or `POST` requests with an `action` parameter.
- **List Action**: `GET /api.php?action=list&page=1` will return a paginated JSON list of images.
- **Search Action**: `GET /api.php?action=search&q=query` will allow basic search on the `path` or `url` fields.

### 2. MCP Server (`mcp.php`)
- **Transport**: JSON-RPC over `stdio`.
- **Tools**:
  - `upload_image`: Wraps current `handleUploadedFile` logic.
  - `list_images`: Wraps the new list logic.
  - `get_image_details`: Returns specific metadata for an image ID.
- **Rationale**: Minimal overhead, high compatibility with Claude Desktop and other MCP clients.

### 3. AI Skill (`SKILL.md`)
- **Content**: Defines the purpose of the tools, structured API documentation, and usage examples for LLMs.
- **Location**: Root directory for easy discovery.

## Risks / Trade-offs

- **Risk**: Performance of `LIKE` queries in SQLite for large image sets.
  - **Mitigation**: Add indexes to `path` if necessary, and strictly limit result sets.
- **Risk**: Security of the MCP server.
  - **Mitigation**: The MCP server will require a valid Token for all operations.
- **Trade-off**: Using PHP for MCP instead of Node/Python.
  - **Rationale**: Keeps the project zero-dependency (other than current Composer deps) and consistent with the existing stack.
