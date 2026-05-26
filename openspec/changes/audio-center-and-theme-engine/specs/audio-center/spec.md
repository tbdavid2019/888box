## ADDED Requirements

### Requirement: 音訊上傳處理與驗證
系統必須支援音訊檔案（包括 `mp3`、`wav`、`aac`、`ogg`、`m4a`、`flac`）的上傳、驗證與儲存。

#### Scenario: 上傳音訊檔案
- **WHEN** 使用者在聲音大廳選擇一個 `.mp3` 音訊檔案並點選上傳
- **THEN** 系統驗證其檔案大小小於單一音訊限制，儲存至儲存裝置，並在資料庫寫入一筆 `is_audio = 1` 的紀錄

### Requirement: 音訊 Metadata 與時長提取
系統在接收到音訊檔案時，必須自動透過 `ffprobe` 提取音訊檔案的時長（duration）與位元率（bitrate）資訊，並記錄於資料庫中。

#### Scenario: 提取 MP3 屬性
- **WHEN** 使用者成功上傳音訊檔案後
- **THEN** 系統背景或流程自動調用 `ffprobe` 提取時長，並將時長寫入對應的 meta 屬性

### Requirement: 音訊 Podcast XML 訂閱源與重建
系統必須能自動彙整未設定密碼的音訊資產，產生音訊專屬的 Podcast RSS 訂閱源並儲存在 `/storage/podcast_audio.xml`；並在後台提供手動重建功能。

#### Scenario: 重建音訊 RSS
- **WHEN** 管理員在聲音管理後台點選「重建 RSS」
- **THEN** 系統遍歷資料庫中所有公開的音訊檔案，重新產出符合 Podcast XML 規範的 `podcast_audio.xml` 訂閱源

### Requirement: 音訊網頁播放器與旋轉動畫
系統必須在資源檢視頁面提供美觀的音訊播放介面，播放時畫面中部的唱片圖示必須旋轉，暫停或播放結束時停止旋轉。

#### Scenario: 播放音訊
- **WHEN** 使用者點選檢視音訊資源並按下「播放」按鈕
- **THEN** 播放器開始播放音樂，且畫面中的唱片圖示開始以 360 度旋轉

### Requirement: 聲音專屬管理後台
系統必須提供獨立的聲音管理後台頁面，展示所有的音訊檔案，並支援直接播放預覽、編輯標題與描述、以及刪除音訊資產。

#### Scenario: 刪除音訊
- **WHEN** 管理員在聲音管理後台對某個音訊點擊「刪除」並確認
- **THEN** 系統將檔案從實體儲存及資料庫中刪除，並自動更新 `podcast_audio.xml` 訂閱源
