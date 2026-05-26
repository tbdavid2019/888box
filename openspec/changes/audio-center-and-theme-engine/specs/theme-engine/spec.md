## ADDED Requirements

### Requirement: 系統主題配置與設定
系統必須提供全站配色主題的配置機制，支援設定與儲存目前使用的主題名稱（例如 `default` 或是 `middle_east_dart`）。

#### Scenario: 儲存主題設定
- **WHEN** 管理員在系統設定表單中將主題切換為 `middle_east_dart` 並提交
- **THEN** 系統將 `active_theme` 設定寫入 `configs` 資料庫，並回傳儲存成功訊息

### Requirement: 潘通 2026 色系主題 (Middle East Dart)
系統必須實作一組名為 `middle_east_dart` 的 Pantone 2026 配色規格：標題使用 `#D4AF37`，內文使用 `#F5E5C8`，按鈕使用 `#4A90E2`，強調/提示使用 `#FFD700`。

#### Scenario: 套用 Middle East Dart 主題
- **WHEN** 當前主題設為 `middle_east_dart`
- **THEN** 系統輸出的 CSS 變數覆蓋為：標題為金黃色、內文為奶白色、按鈕為天藍色、強調提示為亮黃色，且背景渲染為沙漠暗金漸層

### Requirement: 動態樣式注入 (Dynamic Style Injection)
系統必須自動在所有前台及後台管理頁面的 HTML 頁首注入動態樣式塊，依據當前選定的主題覆蓋 CSS 自訂屬性與特定標籤樣式。

#### Scenario: 前台加載樣式
- **WHEN** 使用者載入首頁 `index.php`
- **THEN** 頁首含有對應 active_theme 配色的 `<style>` 覆蓋區塊，使所有標題、內文、按鈕與區塊邊框顏色變更為主題配色
