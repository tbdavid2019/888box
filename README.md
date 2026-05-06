# PixPro

本專案後續將在此基礎上持續開發與調整。

## 功能亮點

- **圖片上傳**：支援多種存儲後端（本地、OSS、S3、又拍雲），具備自動壓縮與格式轉換功能。
- **影片上傳與 Podcast**：
    - 支援 `mp4`, `webm`, `mov`, `quicktime` 等影片格式上傳。
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
2. 執行 `docker compose up --build -d`。
3. 訪問 `<Your_Domain>/install` 進行初始化設定。

## 使用說明

### 1. 網頁介面 (UI) 上傳
- 開啟首頁，你可以直接**點擊上傳框**或**將圖片/影片拖曳**至視窗內。
- 也可以在下方的文字方塊內**貼上圖片或影片的網址**，系統會自動下載並上傳。
- 選取影片後，介面會自動切換為影片預覽模式，上傳完成後即可一鍵複製影片與封面的 Markdown/HTML 連結。

### 2. API 上傳 (自動化與機器人)
- **Endpoint**: `/video.php` (影片專用) / `/api.php` (圖片專用)
- **Method**: `POST`
- **Headers**: 
    - `Authorization: Bearer <Your_Token>`
- **Body** (form-data):
    - `file` (針對 video.php) 或 `image` (針對 api.php): 你的媒體檔案
- **Response**: 返回檔案 URL、封面圖 URL 及元數據。

### 3. 公開資源與訂閱
系統會自動彙整你上傳的影片，產生下列資源：
- **Podcast RSS**: `<Storage_URL>/storage/podcast.xml` (支援導入 Apple Podcasts 等播放器)
- **每日 JSON**: `<Storage_URL>/storage/YYYY-MM-DD/videos.json`

## 致謝

感謝原作者：<https://github.com/JLinMr/PixPro>
