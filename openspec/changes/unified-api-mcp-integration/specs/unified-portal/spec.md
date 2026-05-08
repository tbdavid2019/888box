## ADDED Requirements

### Requirement: 資產統計展示
門戶系統 SHALL 透過 API 獲取各個資產中心（圖片、影片、文件）的統計數據，並在卡片上展示。

#### Scenario: 統計數據載入
- **WHEN** 使用者訪問 `index.php`
- **THEN** 系統透過 `api.php?action=stats` 獲取數據並顯示在對應卡片的角落
