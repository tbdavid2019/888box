## ADDED Requirements

### Requirement: 統一 API 進入點
系統 SHALL 提供單一 API 進入點 `api.php`，處理所有資產類型（圖片、影片、文件）的請求。

#### Scenario: 請求分發測試
- **WHEN** 發送 `action=list&type=video` 請求至 `api.php`
- **THEN** 系統 SHALL 僅返回影片類型的資產列表

### Requirement: 標準化 JSON 響應
所有 API 響應 SHALL 遵循統一的 JSON 格式，包含 `result`, `code`, `data` (或 `message`)。

#### Scenario: 成功響應格式
- **WHEN** API 請求執行成功
- **THEN** 返回 `{"result": "success", "code": 200, "data": [...]}`
