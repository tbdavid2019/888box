# asset-security Specification

## Purpose
TBD - created by archiving change unified-asset-management. Update Purpose after archive.
## Requirements
### Requirement: 單個資源密碼保護
系統必須允許為單個資源設置存取密碼。若設置了密碼，使用者必須輸入正確密碼後才能查看或下載資源。

#### Scenario: 訪問受密碼保護的資源
- **WHEN** 使用者訪問一個設有密碼的 `view.php?id=...` 連結
- **THEN** 系統顯示密碼輸入框，隱藏資源內容，直到驗證成功

### Requirement: 加密影片排除於 RSS (RSS Exclusion)
系統在生成 Podcast RSS (podcast.xml) 時，必須自動排除所有設有存取密碼的影片，以確保 RSS 的公開相容性與隱私。

#### Scenario: 生成 RSS 時過濾加密影片
- **WHEN** 系統觸發更新 `storage/podcast.xml`
- **THEN** 系統在查詢資料庫時必須過濾掉 `password` 欄位不為空的影片資源

