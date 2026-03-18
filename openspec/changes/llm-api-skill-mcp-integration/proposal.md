## Why

To enable Large Language Models (LLMs) and agentic AI systems to interact directly with the PixPro image hosting system. This allows automated workflows such as:
- AI-generated image storage and management.
- Intelligent image retrieval and organization.
- Integration into LLM-powered tools (e.g., custom GPTs, Claude Components, Gemini Extensions).

## What Changes

We will introduce a set of tools and documentation designed for machine consumption:
1. **Enhanced API**: New endpoints for discovery and management of images beyond just uploading.
2. **AI Skill**: A `SKILL.md` file providing structured documentation for AI agents.
3. **MCP Server**: A Model Context Protocol server implementation to bridge PixPro with modern AI orchestrators.

## Capabilities

### New Capabilities
- `llm-api-extensions`: Enhanced JSON API for image listing, metadata retrieval, and search.
- `ai-skill-docs`: Structured documentation (SKILL.md) for agentic discovery and usage.
- `mcp-integration`: Model Context Protocol server implementation providing tools and resources to LLMs.

### Modified Capabilities
- `upload-api`: Update existing `api.php` to provide more robust error reporting and metadata for AI agents.

## Impact

- **Affected Files**: `api.php`, `config/upload.php`.
- **New Files**: `SKILL.md`, `mcp.php`, `openspec/specs/llm-api-extensions/spec.md`, etc.
- **Dependencies**: No new external PHP dependencies are strictly required, though some MCP-related helper libraries might be considered if they simplify stdio/json-rpc handling.
