# report-system Specification

## Purpose
TBD - created by archiving change unified-asset-management. Update Purpose after archive.
## Requirements
### Requirement: 舉報追蹤與發信
系統必須提供「舉報此資源」功能。點擊後：
1. 在資料庫中將該資源的 `report_count` 加 1。
2. 觸發 Python 腳本發送電子郵件給預設的管理員清單。

#### Scenario: 提交檢舉
- **WHEN** 使用者在資源頁面點擊「舉報」並確認
- **THEN** 系統將該資源的 `report_count` 數值加 1，並調用 Python SMTP 橋接器發信

### Requirement: 後台檢舉管理 (Admin Report Dashboard)
各個託管中心的後台介面（圖片、影片、文件）必須顯示該資源的累計檢舉次數。若檢舉次數大於 0，系統應以醒目方式（如紅色標籤）標示。

#### Scenario: 管理員查看檢舉清單
- **WHEN** 管理員進入後台管理頁面
- **THEN** 系統展示所有資源及其檢舉次數，並允許管理員篩選出「被檢舉次數最多」的資源進行處理

