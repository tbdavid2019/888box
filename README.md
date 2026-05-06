# PixPro

本專案後續將在此基礎上持續開發與調整。

## 功能亮點

- **圖片上傳**：支援多種存儲後端（本地、OSS、S3、又拍雲），具備自動壓縮與格式轉換功能。
- **影片上傳與 Podcast**：
    - 支援 `mp4`, `webm`, `mov` 等影片格式上傳。
    - **自動化 MetaData**：自動提取影片時長、解析度與位元率。
    - **自動封面圖**：自動從影片中截取首幀作為封面圖。
    - **Podcast RSS**：自動生成符合 iTunes 規範的 `podcast.xml` 饋送。
    - **每日列表**：自動生成每日影片 JSON 列表，方便外部系統整合。

## 快速開始

### 環境需求
- PHP 7.2+
- Docker & Docker Compose
- **FFmpeg** (若需使用影片處理功能，Docker 鏡像已內置)

### 安裝與啟動
1. 複製本專案。
2. 執行 `docker compose up -d`。
3. 訪問 `http://localhost:6767/install` 進行初始化設定。

## API 使用說明

### 影片上傳
- **Endpoint**: `/video.php`
- **Method**: `POST`
- **Headers**: 
    - `Authorization: Bearer <Your_Token>`
- **Body** (form-data):
    - `file`: 影片檔案
- **Response**: 返回影片 URL、封面圖 URL 及元數據。

### 公開資源
- **Podcast RSS**: `<Storage_URL>/storage/podcast.xml`
- **每日 JSON**: `<Storage_URL>/storage/YYYY-MM-DD/videos.json`

## 致謝

感謝原作者：<https://github.com/JLinMr/PixPro>
