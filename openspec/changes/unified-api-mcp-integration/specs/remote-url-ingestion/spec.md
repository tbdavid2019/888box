## ADDED Requirements

### Requirement: 遠端 URL 抓取上傳
系統 SHALL 支援透過 `URL` 參數上傳資產，由伺服器端直接下載檔案。

#### Scenario: 成功抓取圖片
- **WHEN** 發送 `action=upload_url&url=https://example.com/image.jpg`
- **THEN** 伺服器 SHALL 下載該圖片，將其轉為 WebP 格式，存入資料庫並返回存取連結

### Requirement: 下載安全性檢查
在執行抓取前，系統 SHALL 檢查 URL 內容的 MIME 類型與檔案大小，避免伺服器遭攻擊或資源耗盡。

#### Scenario: 拒絕過大檔案
- **WHEN** 發送一個指向 2GB 檔案的 `upload_url` 請求
- **THEN** 系統 SHALL 立即拒絕下載並返回 `413 Payload Too Large`
