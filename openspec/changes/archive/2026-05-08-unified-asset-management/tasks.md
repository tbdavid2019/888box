## 1. 基礎架構與資料庫遷移

- [x] 1.1 修改 `Dockerfile` 安裝 `python3`
- [x] 1.2 執行資料庫遷移：為現有資源表增加 `password`, `view_count`, `mime_type` 欄位
- [x] 1.3 建立新目錄結構：`storage/file/`

## 2. 統一門戶 (Bento Grid Portal)

- [x] 2.1 將原 `index.php` 重新命名或備份，建立全新的 Bento Grid 首頁
- [x] 2.2 實作 iOS 風格卡片組件與響應式 CSS 佈局
- [x] 2.3 串接圖片、影片、文件的導航連結

## 3. 文件託管中心 (File Center)

- [x] 3.1 建立 `upload_file.php` (前端上傳 UI)
- [x] 3.2 實作文件上傳後端邏輯，支援指定後綴與 MIME 辨識
- [x] 3.3 建立 `admin/file.php` (後台管理介面)

## 4. 資源檢視頁與安全 Gate (View Page)

- [x] 4.1 實作 `view.php` 通用資源檢視頁面
- [x] 4.2 整合密碼輸入驗證邏輯與毛玻璃 UI
- [x] 4.3 在 `view.php` 實作點擊次數累加邏輯
- [x] 4.4 整合 `epub.js` 提供電子書線上閱讀功能

## 5. 檢舉系統與 SMTP 發信

- [x] 5.1 撰寫 Python 發信指令碼 `scripts/report_mail.py`
- [x] 5.2 在後台 `admin/settings.php` 增加 SMTP 配置項與多組管理員信箱欄位
- [x] 5.3 在 `view.php` 實作檢舉按鈕與 AJAX 發信/計數累加邏輯
- [x] 5.4 在圖片、影片、文件後台清單中，增加「檢舉次數」顯示欄位並實作醒目提示
