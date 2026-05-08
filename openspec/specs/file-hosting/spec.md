# file-hosting Specification

## Purpose
TBD - created by archiving change unified-asset-management. Update Purpose after archive.
## Requirements
### Requirement: 文件上傳與管理
系統必須支援 ZIP, PDF, Word, Excel, Visio, EPUB 等文件上傳，並存儲於 `storage/file/` 目錄。

#### Scenario: 上傳 PDF 文件
- **WHEN** 使用者在文件中心選擇一個 `.pdf` 檔案並提交上傳
- **THEN** 系統將檔案保存至 `storage/file/` 並在資料庫記錄該文件

### Requirement: EPUB 線上閱讀
系統必須為 `.epub` 檔案提供線上閱讀功能。

#### Scenario: 點擊查看 EPUB
- **WHEN** 使用者在預覽頁面打開一個 `.epub` 資源
- **THEN** 系統載入線上閱讀器介面並展示電子書內容

