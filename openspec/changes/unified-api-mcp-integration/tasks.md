## 1. 統一 API Gateway 重構

- [x] 1.1 在 `api.php` 中實作統一的路由分發邏輯
- [x] 1.2 整合 `api_file.php` 的文件處理邏輯至 `api.php`
- [x] 1.3 整合影片管理邏輯（原本在根目錄或分散的 api 文件中）
- [x] 1.4 實作 `action=stats` 介面，返回各中心資源統計數據


## 2. 實作遠端 URL 抓取功能

- [x] 2.1 在 `api.php` 中新增 `action=upload_url` 指令
- [x] 2.2 實作伺服器端 cURL 下載與暫存機制
- [x] 2.3 實作下載前的安全檢查（MIME 類型與檔案大小驗證）
- [x] 2.4 串接現有的 `handleUploadedFile` 流程完成壓縮與儲存


## 3. MCP Server 全面升級

- [x] 3.1 擴展 `mcp.php` 中的工具定義，包含 `list_videos`, `list_files`
- [x] 3.2 實作 `upload_asset_by_url` MCP 工具，調用 2.1 的 API
- [x] 3.3 補齊 Podcast 專用管理工具 `get_podcast_info` 等
- [x] 3.4 最佳化 JSON-RPC 回傳格式，確保與最新 MCP 標準相符


## 4. 指南與清理

- [x] 4.1 撰寫並更新 `SKILL.md`，詳述統一後的 API 調用方式
- [x] 4.2 標記舊有的分散式 `api_*.php` 檔案為過時，並更新內部引用
- [x] 4.3 在 `index.php` 實作動態統計數據展示

