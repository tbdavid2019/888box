## ADDED Requirements

### Requirement: iOS 風格便當盒導航 (Bento Grid Portal)
系統必須在 `index.php` 實作一個整合入口，採用 iOS Bento Grid 佈局，展示圖片、影片與文件三個入口卡片。

#### Scenario: 訪問首頁
- **WHEN** 使用者訪問網站根目錄 `index.php`
- **THEN** 系統展示包含「圖片」、「影片」、「文件」三個導航卡片的響應式頁面

### Requirement: 手機版自動適配
門戶頁面必須支援響應式佈局，在手機等小螢幕設備上將卡片自動垂直堆疊。

#### Scenario: 手機瀏覽
- **WHEN** 使用者在螢幕寬度小於 768px 的設備上訪問首頁
- **THEN** 系統將導航卡片改為單欄垂直佈局
