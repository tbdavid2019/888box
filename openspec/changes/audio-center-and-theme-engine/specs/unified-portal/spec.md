## MODIFIED Requirements

### Requirement: iOS 風格便當盒導航 (Bento Grid Portal)
系統必須在 `index.php` 實作一個整合入口，採用 iOS Bento Grid 佈局，展示圖片、影片、文件以及聲音共四個入口卡片，形成對稱的 2x2 網格。

#### Scenario: 訪問首頁
- **WHEN** 使用者訪問網站根目錄 `index.php`
- **THEN** 系統展示包含「圖片」、「影片」、「文件」、「聲音」共四個導航卡片的響應式頁面
