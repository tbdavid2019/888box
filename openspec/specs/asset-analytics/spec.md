# asset-analytics Specification

## Purpose
TBD - created by archiving change unified-asset-management. Update Purpose after archive.
## Requirements
### Requirement: 真實點擊次數統計
系統必須記錄資源的真實訪問次數，並在後台管理介面展示。

#### Scenario: 訪問資源增加計數
- **WHEN** 使用者成功通過 `view.php` 訪問資源
- **THEN** 系統將該資源在資料庫中的 `view_count` 欄位數值加 1

