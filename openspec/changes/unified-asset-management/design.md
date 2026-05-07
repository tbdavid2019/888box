## Context

目前 888box 採用分散式的 PHP 腳本處理圖片與影片，各模組間邏輯重複且缺乏統一入口。SQLite 資料庫雖然方便，但當前表結構過於簡化。

## Goals / Non-Goals

**Goals:**
- 實作統一的 Bento Grid 門戶頁面。
- 建立通用的資產管理架構，支援多種類型。
- 整合 Python SMTP 橋接發信系統。
- 支援 EPUB 線上閱讀與個別資源密碼保護。

**Non-Goals:**
- 不涉及使用者權限系統的重構（維持現有 admin 登入機制）。
- 不實作文件內容搜尋（僅限元數據搜尋）。

## Decisions

### 1. 資料庫重構 (Unified Assets Table)
- **決定**: 將 `images` 與 `videos` 的概念統一，或在現有表上擴充 `password`, `view_count`, `report_count`, `mime_type` 欄位。
- **理由**: 統一管理有利於點擊統計與密碼 gate 的邏輯複用。
- **RSS 過濾**: 在 `video_logic.php` 的 RSS 生成函式中，加入 `WHERE password IS NULL OR password = ''` 的查詢條件。
- **替代方案**: 建立多張表，但這會增加 `view.php` 路由判斷的複雜度。

### 2. Python SMTP 橋接
- **決定**: 在 `Dockerfile` 安裝 `python3`，並使用 `smtplib` 撰寫發信腳本。
- **理由**: 避免引入 `PHPMailer` 等 PHP 依賴，保持容器精簡。
- **替代方案**: 使用 PHP `mail()`，但在無郵件伺服器的環境下極不穩定。

### 3. 首頁門戶技術 (Bento Grid)
- **決定**: 使用 CSS Grid 與毛玻璃 (Glassmorphism) 風格。
- **理由**: 符合 iOS Aesthetes，且能自動適配手機版面。

### 4. 線上閱讀器
- **決定**: 引入 `epub.js` 作為前台渲染引擎。
- **理由**: 成熟且輕量，無需後端處理電子書內容。

## Risks / Trade-offs

- **[Risk] Python 環境增加鏡像大小** → **Mitigation**: 使用 `apt-get clean` 並僅安裝 `python3-minimal`。
- **[Risk] 密碼保護安全性** → **Mitigation**: 密碼僅用於前端 Gate，後端存儲路徑仍需配合 `.htaccess` 或 PHP 權限檢查以防直接訪問。
- **[Trade-off] 資料庫遷移複雜度** → **Decision**: 優先採累加欄位方式 (ALTER TABLE)，確保舊資料不丟失。
