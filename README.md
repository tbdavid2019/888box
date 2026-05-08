# 888box
![alt text](image-3.png)
![Banner](static/favicon.svg)

一款專業級、全方位的媒體與文件託管解決方案。採用全新的 **Bento Grid** 門戶設計，將「圖片」、「影片」與「文件」完美整合，並具備強大的安全性、分析與舉報系統。支援 AWS S3 等多種儲存後端，具備自動提取 MetaData、Podcast RSS 同步、EPUB 線上閱讀及點擊次數追蹤等功能。

## ✨ 核心功能亮點

### 🖼️ 圖片託管中心
- **極速上傳**：支援拖曳、剪貼簿貼上上傳，具備強大的圖片壓縮與格式轉換（WebP）功能。
- **智能處理**：自動校正 JPEG 方向，保留/移除 Exif 資訊。

### 🎬 影片與 Podcast 系統
- **自動化 RSS**：上傳影片後自動生成相容 iTunes 的 Podcast RSS (`podcast.xml`)，支援主流播客 App 訂閱。
- **MetaData 提取**：自動擷取影片長度、解析度、碼率，並於第 1 秒處自動生成預覽縮圖。

### 📂 文件託管中心
- **萬用支援**：支援 ZIP, PDF, Word, Excel, Visio 等多種文件格式。
- **EPUB 閱讀器**：內建 `epub.js` 支援，電子書可直接線上閱讀，無需下載。
![alt text](image-4.png)

### 🛡️ 安全、分析與舉報
- **通用 Gatekeeper**：所有資源均可設定存取密碼，透過毛玻璃 UI 進行驗證。
- **點擊分析**：追蹤每一項資源的「真實點擊次數」。
- **舉報系統**：內建舉報功能，方便管理違規內容。

### 🤖 AI 代理人整合 (AI Agent Integration)
- **動態技能指南 (`skill.php`)**：為 AI 代理人（如 Claude, GPT）提供動態生成的指令文檔，自動識別 Base URL 並在登入狀態下注入 Token。
- **MCP 支援**：支援 Model Context Protocol，讓 AI 代理人能自動執行上傳、列表查詢及資產清理等任務。

---

## 🚀 快速開始

### 推薦安裝方式 (一鍵腳本)
只要你的伺服器具備 Docker 與 Git，執行以下指令即可完成安裝：

```bash
git clone https://github.com/tbdavid2019/888box.git
cd 888box
./install.sh
```

**該腳本會自動完成：**
1.  **環境檢查**：確保 Docker 正常運作。
2.  **目錄初始化**：建立 `storage/` 並設定正確的權限。
3.  **配置生成**：產生預設的 `.env` 環境變數檔。
4.  **容器啟動**：自動編譯與啟動 Docker 容器。
5.  **互動式設定**：引導你設定第一個**管理員帳號與密碼**。

---

## 🛠️ 開發與架構

- **Backend**: PHP 8.1+ (Apache)
- **Frontend**: Vanilla JS (ES Modules)
- **Storage**: SQLite 3 (持久化於 `storage/database.db`)
- **Dependencies**: FFmpeg, ImageMagick, Composer
- **Docker**: 支援 x86_64 與 ARM64 (Apple Silicon / AWS Graviton)

### 手動管理指令
- **啟動**：`docker compose up -d`
- **停止**：`docker compose stop`
- **更新代碼**：`git pull && docker compose restart`
- **重構環境**：`docker compose up -d --build` (當 Dockerfile 有變動時)
- **手動建立/重設管理員帳號**：
  若未執行安裝腳本，可透過此指令建立帳號（請替換 `YOUR_USER` 與 `YOUR_PASS`）：
  ```bash
  docker exec -it 888box php -r "$u='YOUR_USER'; $p='YOUR_PASS'; $pdo = new PDO('sqlite:/var/www/html/storage/database.db'); $h = password_hash($p, PASSWORD_DEFAULT); $t = bin2hex(random_bytes(16)); $stmt = $pdo->prepare('INSERT OR REPLACE INTO users (username, password, token) VALUES (?, ?, ?)'); $stmt->execute([$u, $h, $t]); echo \"User $u created.\n\";"
  ```

## 📄 授權協議
本專案採用 AGPL-3.0 授權協議。詳見 [LICENSE](LICENSE) 檔案。
