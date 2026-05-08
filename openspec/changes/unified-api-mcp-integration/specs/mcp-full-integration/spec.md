## ADDED Requirements

### Requirement: 完整的資產管理工具
MCP Server SHALL 提供涵蓋所有資產類型的工具，包含 `list_assets`, `get_asset_details`, `upload_asset_url` 等。

#### Scenario: 列出影片資產
- **WHEN** LLM 調用 `list_assets` 工具並指定 `type=video`
- **THEN** 系統 SHALL 返回包含影片 Metadata (如時長、解析度) 的清單

### Requirement: Podcast 管理整合
MCP Server SHALL 提供專屬工具來管理影片 RSS/Podcast 列表。

#### Scenario: 獲取 Podcast 狀態
- **WHEN** LLM 調用 `get_podcast_info` 工具
- **THEN** 系統 SHALL 返回 RSS 連結及目前的節目數量
