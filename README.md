# 888box (原 PixPro)

一款專為個人需求設計的高效媒體託管解決方案，整合強大的圖片與影片處理功能。支援 AWS S3 等多種儲存後端，並具備自動提取影片 MetaData、封面圖生成及 Podcast RSS 自動更新功能。

## 功能亮點

- **圖片託管**：支援多種存儲後端（本地、AWS S3、OSS、又拍雲），具備自動壓縮與格式轉換（WebP）功能。
- **影片上傳與 Podcast**：
    - 支援 `mp4`, `webm`, `mov`, `quicktime` 等影片格式上傳。
    - **自動化 MetaData**：自動提取影片時長、解析度與位元率。
    - **自動封面圖**：自動從影片中截取首幀作為封面圖。
    - **Podcast RSS**：自動生成符合 iTunes 規範的 `podcast.xml` 饋送，隨上傳自動更新。
    - **每日列表**：自動生成每日影片 JSON 列表，方便外部系統整合。

## 🚀 部署教學 (Deployment)

建議使用 Docker 進行部署，以確保環境一致性（特別是 FFmpeg 依賴）。

### 1. 系統需求
- Docker & Docker Compose
- Nginx 或其他反向代理伺服器（可選，建議配置 HTTPS）

### 2. 安裝步驟

```bash
# 1. 複製專案
git clone https://github.com/tbdavid2019/PixPro.git 888box
cd 888box

# 2. 構建並啟動容器 (首次啟動會自動編譯 FFmpeg 與 PHP 擴展)
docker compose up --build -d

# 3. 如果需要重置環境或清理舊代碼快取，請執行：
# docker compose down
# docker volume rm pixpro_pixpro-data
# docker compose up --build -d
```

### 3. 初始化設定
啟動後，請打開瀏覽器訪問安裝頁面進行資料庫與管理員設定：
- **安裝 URL**: `http://<你的網域或IP>:6767/install`

---

## 📖 使用教學 (Usage & URLs)

安裝完成後，你可以透過以下網址與端點使用系統：

### 網頁介面 (人類使用者)
- **首頁上傳 UI**: `https://<你的網域>/`
  - 支援點擊、拖曳、或使用 Ctrl+V 貼上圖片/影片。
  - 影片會自動顯示預覽播放器。
- **管理後台**: `https://<你的網域>/admin/`
  - 登入後可進行檔案的瀑布流預覽、刪除誤傳檔案、以及修改系統設定。

### API 介面 (自動化與機器人)
若需使用程式或機器人上傳，請在 HTTP Header 帶上 `Authorization: Bearer <Your_Token>`（Token 可於後台設定）。

- **影片專用上傳 Endpoint**: `https://<你的網域>/video.php`
  - Method: `POST`
  - Body (form-data): `file=@your_video.mp4`
  - 成功回傳: JSON，包含影片 URL、封面圖 URL、時長等 MetaData。
- **圖片專用上傳 Endpoint**: `https://<你的網域>/api.php`
  - Method: `POST`
  - Body (form-data): `image=@your_image.jpg`

### 公開資源與 RSS 訂閱
系統會自動彙整你上傳的影片，產生下列公開資源供外部讀取：

- **🎧 Podcast RSS 訂閱**: `https://<你的網域>/storage/podcast.xml`
  - 可直接將此網址貼入 Apple Podcasts, Spotify 或其他 Podcast 播放器中訂閱。
- **📋 每日影片 JSON**: `https://<你的網域>/storage/YYYY-MM-DD/videos.json`
  - 提供給自動化系統（如 Telegram Bot, Webhook）抓取當日更新的影片清單。

## 致謝

感謝原作者：<https://github.com/JLinMr/PixPro>
