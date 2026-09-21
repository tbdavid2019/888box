# 888box 第二階段：加密 Seal 設計

## 文件目的

本文件記錄第二階段的真正加密 Seal 設計。第一階段已完成既有資產的伺服器端存取閘門，支援定時解鎖、Dead Man’s Switch、閱後即焚與 Burn。

第一階段的限制如下：

- 原始資產仍由 888box 儲存後端管理。
- 伺服器管理者或具有儲存後端權限的操作者仍可能在解鎖前讀取原始明文。
- 若既有 S3、OSS、UpYun 物件曾經以公開 URL 對外，外部 URL 可能繞過 888box 的代理閘門。
- 這一階段提供的是存取控制 Seal，不是端到端加密保管庫。

## 目標

第二階段要讓「建立 Seal 的瀏覽器」在檔案離開瀏覽器前先完成加密。伺服器只保存密文與無法單獨解密的 Key B。

目標威脅模型：

1. 資料庫外洩時，攻擊者拿不到可解密的完整金鑰。
2. 儲存物件外洩時，攻擊者只能取得密文。
3. 解鎖時間以前，API 不釋出 Key B。
4. URL fragment 中的 Key A 不會送到 HTTP 伺服器。
5. 內容完整性由 AES-GCM authentication tag 與密文雜湊共同驗證。

URL fragment 仍是 Bearer credential。瀏覽器歷史、書籤、瀏覽器外掛、螢幕截圖與使用者主動分享都可能暴露連結，因此介面必須明確提示使用者保存與傳送風險。

## 加密模型

### 金鑰分工

- Key A：瀏覽器產生，保留在 `/seal/<id>#<keyA>` 的 fragment，只在瀏覽器解密時使用。
- Key B：瀏覽器產生，送往伺服器前以 `SEAL_MASTER_KEY` 包裝。
- Content Key：由 Key A 與 Key B 透過 HKDF 衍生，使用 AES-GCM-256 加密內容。
- IV：每個 Seal 使用新的隨機 96-bit IV，作為公開 metadata 保存。
- `SEAL_MASTER_KEY`：只存在部署環境的 secret，不寫入 SQLite、物件儲存或 API 回應。

伺服器資料表只保存：

- `encrypted_blob` 或密文物件路徑
- `encrypted_key_b`
- `iv`
- `blob_hash`
- 解鎖時間、模式、瀏覽次數與清理時間

伺服器永遠不保存 Key A，也不接收 URL fragment。

## 上傳流程

1. 使用者在瀏覽器選擇文字或小型檔案。
2. 瀏覽器產生 Key A、Key B 與 IV。
3. 瀏覽器以 AES-GCM 加密內容。
4. 瀏覽器將密文、IV、Key B 與 Seal 設定送到 `api.php?action=seal_create_encrypted`。
5. 伺服器以 `SEAL_MASTER_KEY` 包裝 Key B。
6. 伺服器將 Seal metadata 與密文保存至既有儲存抽象層。
7. API 回傳不含 Key A 的公開 Seal URL。
8. 瀏覽器將完整 URL fragment 組合給建立者保存。

伺服器收到的上傳內容從一開始就是密文。第二階段不應把既有明文資產送回伺服器重新加密後，宣稱已完成端到端保護。

## 解鎖流程

1. 瀏覽器開啟 `/seal/<id>#<keyA>`。
2. 瀏覽器呼叫 `api.php?action=seal_status`，只取得鎖定狀態與伺服器時間。
3. 解鎖前，API 回傳 423 與倒數 metadata，不回傳 Key B 或密文。
4. 解鎖後，API 回傳密文、IV、包裝後解出的 Key B 與完整性雜湊。
5. 瀏覽器以 URL fragment 中的 Key A 加上 Key B 衍生 Content Key。
6. 瀏覽器驗證並解密內容。
7. 解密失敗時，介面顯示密文損壞或 Seal 版本不相容，不嘗試伺服器端解密。

## 模式設計

### Timed

- `unlock_at` 由伺服器時間驗證。
- 解鎖前 API 僅回傳狀態。
- 到期後釋出 Key B 與密文。

### Dead Man’s Switch

- 建立時設定 `pulse_interval`。
- Pulse Token 只保存雜湊值。
- 每次 Pulse 以原子更新延長 `unlock_at`。
- 到達 `unlock_at` 後禁止 Pulse，狀態轉為不可逆解鎖。
- Burn 只允許在未解鎖的 DMS Seal 執行。

### Ephemeral

- `max_views` 限制成功釋出密文的次數。
- 以 `UPDATE ... WHERE view_count < max_views` 進行原子計數。
- 最後一次釋出後刪除密文物件與 Seal metadata。
- 影片與音訊需要額外設計 Range 請求的消費語義；第二階段第一個版本應限制為文字與小型檔案。

## API 草案

```text
POST /api.php?action=seal_create_encrypted
GET  /api.php?action=seal_status&token=<seal-id>
GET  /api.php?action=seal_payload&token=<seal-id>
POST /api.php?action=seal_pulse
POST /api.php?action=seal_burn
POST /api.php?action=seal_cleanup
```

`seal_payload` 必須在伺服器時間達到解鎖條件後才回傳 `encrypted_blob`、`iv` 與 Key B。所有 endpoint 都必須維持固定錯誤格式，並禁止把 Key A 寫入 log、query string 或 request body。

## 儲存與大型檔案策略

第一個加密版本建議限制：

- 文字與 Markdown：最多 2 MiB。
- 一般小型檔案：最多 10 MiB，瀏覽器記憶體與 Base64 overhead 需實測。
- 影片、音訊與大型 EPUB：延後處理。

若要支援大型資產，需要：

- chunked encryption
- 每個 chunk 的 nonce 與 authentication tag
- manifest 完整性驗證
- Range 請求與 Ephemeral 次數的明確定義
- 中斷續傳與重試
- 避免把整個密文轉成 Base64 後放入 JSON

## 金鑰輪替

`encrypted_key_b` 必須保存 `master_key_version`。輪替流程：

1. 新部署先同時讀取 current 與 previous master key。
2. 讀取舊 Seal 時使用 `master_key_version` 解包。
3. 成功解包後重新以 current key 包裝 Key B。
4. 所有資料完成 rewrap 後才撤除 previous key。
5. 撤除舊 key 前先做完整 Seal 解鎖與恢復演練。

## 驗收條件

- 資料庫與物件儲存只看得到密文。
- API log、PHP error log 與分析資料不包含 Key A、Key B 或完整 Seal URL fragment。
- 解鎖前所有 API response 均不包含密文與 Key B。
- 伺服器本機時間變更不會繞過解鎖條件；判斷使用受信任的伺服器時間來源。
- Key B 包裝失敗時建立流程完整 rollback，不留下孤立密文物件。
- Ephemeral 並行請求不會超過 `max_views`。
- Burn、清理、金鑰輪替都有可重現的整合測試。
- 瀏覽器重新整理、缺少 Key A、錯誤 IV、密文遭修改、Seal 已過期等狀態都有明確 UI。

## 與 Timeseal 的關係

本設計參考 Timeseal 公開描述的 split-key、AES-GCM、Timed、Dead Man’s Switch 與 Ephemeral 行為，但不直接複製其原始碼、元件或品牌素材。Timeseal 目前採用 Business Source License，商業時間鎖或加密保管服務有額外使用限制；若未來需要引用實作程式碼，必須先完成授權審查。
