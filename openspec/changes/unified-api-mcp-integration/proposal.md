## Why

888box 目前的 API 與 MCP 實作分散且不完整。API 邏輯分佈在多個 `api_*.php` 檔案中，且主要偏向圖片處理，忽略了新增加的影片與文件中心。MCP Server 也僅支援基本的圖片查詢，無法讓 LLM 發揮 888box 的全功能優勢（如 Podcast 管理與文件線上閱讀）。

## What Changes

- **統一 API Gateway**: 將分散的 API 邏輯整合進 `api.php`，支援統一的 `action` 參數與資產類型過濾。
- **新增 URL 遠端上傳**: API 與 MCP 將支援直接從 URL 下載並上傳資源，無需中轉。
- **完善 MCP 工具集**: 補齊影片、文件的查詢與管理工具，並修正現有的圖片上傳 placeholder。
- **建立 Skill 文件**: 撰寫 `SKILL.md` 指南，讓 LLM 能精確理解並調用 888box 的全功能。

## Capabilities

### New Capabilities
- `unified-api-gateway`: 提供統一的 RESTful 風格 API 入口，支援多種類型資產的操作。
- `mcp-full-integration`: 實作完整的 Model Context Protocol，讓 LLM 具備管理所有資產類型的能力。
- `remote-url-ingestion`: 支援伺服器端直接從 URL 抓取資源並自動分類儲存。

### Modified Capabilities
- `unified-portal`: 更新門戶系統，可能需要配合 API 調整部分動態內容載入。

## Impact

- **Affected Code**: `api.php`, `mcp.php`, `api_file.php`, `config/upload.php`.
- **APIs**: 舊有的 `api_*.php` 將被標記為過時 (Deprecated) 或移除。
- **LLM Experience**: 顯著提升 LLM 幫用戶管理資產的效率與成功率。
