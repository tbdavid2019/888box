## Why

888box 目前僅具備獨立的圖片與影片託管介面，缺乏統一的資源入口與常見辦公文件（如 ZIP, PDF, Word, EPUB）的託管能力。隨著資產類型增加，需要一個整合式的門戶頁面 (Hub) 來提升使用者體驗，並強化安全性（存取密碼）與管理能力（檢舉系統、瀏覽統計）。

## What Changes

- **統一門戶 (Hub)**: 新增 iOS Bento 風格的首頁，作為各類託管中心的統一入口。
- **文件託管中心**: 新增支援 ZIP, PDF, Word, Excel, Visio, EPUB 等文件上傳、管理與線上預覽 (EPUB 閱讀器)。
- **安全增強**: 為單個檔案提供存取密碼保護功能（支援圖片、影片、文件）。
- **管理與舉報**: 
    - 新增舉報功能，按鈕觸發後透過 Python SMTP 腳本發信給管理員。
    - 後台新增 SMTP 與管理員信箱設定（支援多組信箱）。
- **統計分析**: 新增資產瀏覽次數 (View Count) 統計。
- **路徑重構**: 統一資源存儲路徑為 `storage/image/`, `storage/video/`, `storage/file/`。

## Capabilities

### New Capabilities
- `unified-portal`: 提供 iOS 風格的便當盒佈局門戶，整合各中心入口。
- `file-hosting`: 支援文件上傳、管理、下載與 EPUB 線上閱讀。
- `asset-security`: 為個別資源提供密碼保護與權限驗證。
- `report-system`: 檢舉機制與 Python SMTP 發信系統。
- `asset-analytics`: 記錄與展示資源的真實點擊次數。

### Modified Capabilities
- `image-hosting`: 增加密碼保護、瀏覽統計與舉報功能。
- `video-hosting`: 增加密碼保護、瀏覽統計與舉報功能。

## Impact

- **Database**: `images` 表結構異動（或遷移至 `assets`），新增 `password`, `view_count` 等欄位。
- **UI/UX**: `index.php` 重構為門戶頁，新增多個後台管理介面。
- **Docker**: `Dockerfile` 需安裝 `python3`。
- **API**: 新增瀏覽統計與驗證密碼的端點。
