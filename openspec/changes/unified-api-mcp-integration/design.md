## Context

888box 目前擁有三個資產中心（圖片、影片、文件），但對外的 API 與 MCP 介面仍停留在早期僅支援圖片的狀態。為了支援 LLM 自動化操作，需要建立一個能感知類型、支援遠端抓取且統一的 API 體系。

## Goals / Non-Goals

**Goals:**
- 將 `api.php` 重構為統一的進入點，透過 `action` 與 `type` 分發邏輯。
- 實作伺服器端 URL 下載功能，整合進上傳流程。
- 擴展 `mcp.php`，補齊對影片與文件的 Tool 定義與實作。
- 撰寫 `SKILL.md` 做為 LLM 的系統提示詞參考。

**Non-Goals:**
- 不涉及前端 UI 的大規模改版（僅配合 API 調節）。
- 不處理多用戶權限管理（維持現有的單一 Token 驗證模式）。

## Decisions

### 1. 統一 API 路由 (Unified Routing)
**Rationale**: 目前 `api_file.php` 等檔案重複了大量資料庫連接、Token 驗證與錯誤處理代碼。
**Decision**: 所有的 API 請求統一由 `api.php` 接收，驗證通過後，根據 `action` 調用對應的處理函數。處理函數將盡量重用 `config/upload.php` 中的現有邏輯。

### 2. URL 抓取機制 (Remote Ingestion)
**Rationale**: LLM 無法直接將大檔案二進制流傳輸給 Web API（通常受限於 Context 或 Payload 大小）。
**Decision**: 實作 `action=upload_url`。伺服器端使用 `curl` 抓取內容，暫存至臨時目錄，再調用現有的 `handleUploadedFile` 流程進行後續處理（壓縮、儲存、入庫）。

### 3. MCP 工具擴展
**Rationale**: 目前 MCP 工具過於單一，無法讓 AI 知道如何處理 Podcast。
**Decision**: 新增 `list_recent_videos` (返回 metadata), `add_video_to_podcast`, `upload_asset_by_url` 等工具。

## Risks / Trade-offs

- **[Risk] 伺服器帶寬壓力** → **Mitigation**: 限制 URL 上傳的單個檔案大小，並在下載時檢查 MIME 類型。
- **[Risk] API 破壞性變更** → **Mitigation**: 保留舊有的 `api_file.php` 作為轉發器 (Proxy) 一段時間，或者確保前端調用已全部遷移。
