# 888box

一款專業級、全方位的媒體與文件託管解決方案。採用全新的 **Bento Grid** 門戶設計，將「圖片」、「影片」與「文件」完美整合，並具備強大的安全性、分析與舉報系統。支援 AWS S3 等多種儲存後端，具備自動提取 MetaData、Podcast RSS 同步、EPUB 線上閱讀及點擊次數追蹤等功能。

## ✨ 核心功能亮點

### 🏛️ 統一資產門戶 (Bento Grid Portal)
- **iOS 風格設計**：全新的入口首頁，採用現代化 Bento Layout，適配手機與桌機。
- **一站式管理**：快速切換圖片、影片與文件託管中心。

### 🖼️ 圖片託管系統
- **智慧壓縮**：自動轉換為 WebP 格式，大幅節省儲存與頻寬。
- **安全存取**：支援為單張圖片設定存取密碼。
- **瀑布流管理**：獨立的圖片管理後台，支援批次操作與 Token 驗證。

### 🎬 影片與 Podcast 系統
- **MetaData 提取**：自動透過 FFmpeg 提取時長、解析度，並生成封面圖。
- **Podcast RSS 同步**：自動生成符合 iTunes 規範的 `podcast.xml`；修改或刪除影片時即時更新。
- **隱私控制**：設有密碼保護的影片會自動從公開 RSS 饋送中移除。

### 📂 文件託管中心 (全新)
- **萬用支援**：支援 ZIP, PDF, Word, Excel, Visio 等多種文件格式。
- **EPUB 閱讀器**：內建 `epub.js` 支援，電子書可直接線上閱讀，無需下載。

### 🛡️ 安全、分析與舉報
- **通用 Gatekeeper**：所有資源均可設定存取密碼，透過毛玻璃 UI 進行驗證。
- **點擊分析**：追蹤每一項資源的「真實點擊次數」。
- **檢舉系統**：使用者可一鍵舉報異常資源，系統會即時透過 SMTP 發送電子郵件通知管理員。
- **SMTP 通知**：整合 Python 發信後端，支援多組管理員信箱。

## 🚀 部署教學 (Deployment)

強烈建議使用 Docker 進行部署，以確保環境一致性（特別是需要底層編譯 FFmpeg 以支援影片解析）。

### 1. 系統需求
- Docker & Docker Compose
- Nginx 或其他反向代理伺服器（可選，建議配置 HTTPS）

### 2. 安裝步驟

```bash
git clone https://github.com/tbdavid2019/888box.git
cd 888box

# 2. 構建並啟動容器 (首次啟動會自動編譯 FFmpeg 與 PHP 擴展，並解除 PHP 檔案大小限制至 500MB)
docker compose up --build -d

# 3. 初始化權限 (重要)
# 由於容器內以 www-data 執行，需確保宿主機上的 storage 目錄可寫
mkdir -p storage/i
chmod -R 777 storage

# 4. 如果未來需要重置環境或清理舊代碼快取，請執行：
# docker compose down
# docker volume rm 888box_888box-data
# docker compose up --build -d
```

```
git clone https://github.com/tbdavid2019/888box.git
cd 888box
docker compose up --build -d
mkdir -p storage/i && chmod -R 777 storage

```

### 3. 初始化設定
啟動後，請打開瀏覽器訪問安裝頁面進行資料庫與管理員設定：
- **安裝 URL**: `http://<你的網域或IP>:6767/install`

---

## 📖 使用教學與路由 (Usage & URLs)

安裝完成後，系統將分為兩大獨立區塊：

### 網頁介面 (人類使用者)
- **🏛️ 統一資產門戶 (Portal)**: `https://<你的網域>/`
- **🖼️ 圖片託管中心**: `https://<你的網域>/upload_image.php`
- **🎬 影片託管中心**: `https://<你的網域>/upload_video.php`
- **📂 文件託管中心**: `https://<你的網域>/upload_file.php`
- **⚙️ 圖片管理後台**: `https://<你的網域>/admin/`
- **⚙️ 影片管理後台**: `https://<你的網域>/admin/video.php`
- **⚙️ 文件管理後台**: `https://<你的網域>/admin/file.php`

### API 介面 (自動化與機器人)
若需使用程式或機器人上傳，請在 HTTP Header 帶上 `Authorization: Bearer <Your_Token>`（Token 可於圖片後台設定）。

- **影片專用上傳 Endpoint**: `https://<你的網域>/video.php`
  - Method: `POST`
  - Body (form-data): `file=@your_video.mp4` (可選填欄位: `title`, `description`)
  - 成功回傳: JSON，包含影片 URL、封面圖 URL、時長等 MetaData。
- **圖片專用上傳 Endpoint**: `https://<你的網域>/api.php`
  - Method: `POST`
  - Body (form-data): `image=@your_image.jpg`

### 🎧 公開資源與 RSS 訂閱
系統會自動彙整你上傳的影片，產生下列公開資源供外部讀取：

- **Podcast RSS 訂閱**: `https://<你的網域>/storage/podcast.xml`
  - 可直接將此網址貼入 Apple Podcasts, Spotify 或其他 Podcast 播放器中訂閱。任何刪除或標題修改都會即時反應在此檔案中。
- **每日影片 JSON**: `https://<你的網域>/storage/YYYY-MM-DD/videos.json`
  - 提供給自動化系統（如 Telegram Bot, Webhook）抓取當日更新的影片清單。

## 致謝

感謝[原作者JLinMr](https://github.com/JLinMr/PixPro) 的啟發。
