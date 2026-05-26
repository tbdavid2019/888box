## MODIFIED Requirements

### Requirement: 文件上傳與管理
系統必須支援 ZIP, PDF, Word, Excel, Visio, EPUB 等文件上傳，並存儲於 `storage/file/` 目錄。音訊格式檔案（如 MP3、WAV 等）不屬於文件託管範圍，必須由聲音中心處理。

#### Scenario: 上傳 PDF 文件
- **WHEN** 使用者在文件中心選擇一個 `.pdf` 檔案並提交上傳
- **THEN** 系統將檔案保存至 `storage/file/` 並在資料庫記錄該文件
