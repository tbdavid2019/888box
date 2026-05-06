<?php
session_start();

require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $config = Database::getConfig($pdo);
    
    // 获取上传限制配置
    $maxFileSize = 0;
    $stmt = $pdo->prepare("SELECT value FROM configs WHERE `key` = 'max_file_size'");
    $stmt->execute();
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $maxFileSize = (int)$row['value'];
    }
    
    // 检查是否需要登录限制
    if ($config && 
        isset($config['login_restriction']) && 
        filter_var($config['login_restriction'], FILTER_VALIDATE_BOOLEAN) && 
        (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin'])) {
        header('Location: /admin');
        exit();
    }
} catch (Exception $e) {
    die($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>888box</title>
    <meta name="keywords" content="圖床程式,高效圖片壓縮,前後台設計,圖片上傳,WEBP轉換,阿里雲OSS,本機儲存,多格式支援,瀑布流管理,圖片管理後台,自訂壓縮率,尺寸限制">
    <meta name="description" content="一款專為個人需求設計的高效圖床與影片上傳解決方案，支援自動生成 Podcast RSS。">
    <link rel="shortcut icon" href="static/favicon.svg">
    <link rel="stylesheet" type="text/css" href="static/css/styles.css">
</head>
<body>
    <header class="blur">
        <a href="/admin/" target="_blank" title="後台" class="header-link">
            <svg class="icon" aria-hidden="true">
                <use xlink:href="#icon-Setting"></use>
            </svg>
        </a>
    </header>
    <main>
        <div class="upload-container blur">
            <form id="uploadForm" enctype="multipart/form-data">
                <!-- 上传框区域 -->
                <div class="upload-section">
                    <button id="deleteImageButton" class="deleteImageButton">
                        <svg class="icon" aria-hidden="true">
                            <use xlink:href="#icon-xmark"></use>
                        </svg>
                    </button>
                    <div id="imageUploadBox" class="imageUploadBox" onclick="document.getElementById('imageInput').click();">
                        <svg class="icon upload-icon" aria-hidden="true">
                            <use xlink:href="#icon-up"></use>
                        </svg>
                        <input type="file" id="imageInput" name="image[]" accept="image/png, image/jpeg, image/webp, image/svg+xml, image/gif, video/mp4, video/webm, video/quicktime" multiple>
                        <div id="imagePreviewContainer" class="imagePreviewContainer">
                            <button id="prevButton" class="nav-button prev-button">
                                <svg class="icon" aria-hidden="true">
                                    <use xlink:href="#icon-Left-arrow"></use>
                                </svg>
                            </button>
                            <img id="imagePreview" class="imagePreview" src="" alt="">
                            <video id="videoPreview" class="imagePreview" src="" controls style="display:none; max-height: 60vh;"></video>
                            <button id="nextButton" class="nav-button next-button">
                                <svg class="icon" aria-hidden="true">
                                    <use xlink:href="#icon-Right-arrow"></use>
                                </svg>
                            </button>
                            <div id="imageCounter" class="image-counter"></div>
                        </div>
                    </div>
                </div>

                <!-- 缩略图区域 -->
                <div id="thumbnailStrip" class="thumbnail-strip">
                    <div id="thumbnailScrollContainer" class="thumbnail-scroll-container"></div>
                </div>

                <!-- 网络图片上传输入框 -->
                <div class="url-input-section">
                    <input type="text" id="pasteOrUrlInput" class="pasteOrUrlInput" placeholder="輸入圖片或影片網址即可自動上傳，或使用 Ctrl+V 貼上" title="注意：部分網站設有防盜鏈，可能無法直接下載">
                </div>

                <!-- 压缩比率调整 -->
                <div class="quality-section">
                    <label for="qualityInput">圖片清晰度 60-100<output id="qualityOutput" class="qualityOutput">60</output></label>
                    <input type="range" id="qualityInput" name="quality" min="60" max="100" value="60" step="1">
                </div>

                <!-- 复制按钮区域 -->
                <div class="copy-section">
                    <div class="copy-tab-buttons">
                        <div class="copy-icons-column">
                            <button class="copy-tab-btn" data-type="url" title="複製圖片連結">
                                <svg class="icon" aria-hidden="true">
                                    <use xlink:href="#icon-imageUrl"></use>
                                </svg>
                            </button>
                            <button class="copy-tab-btn" data-type="markdown" title="複製 Markdown 程式碼">
                                <svg class="icon" aria-hidden="true">
                                    <use xlink:href="#icon-markdownUrl"></use>
                                </svg>
                            </button>
                            <button class="copy-tab-btn" data-type="html" title="複製 HTML 程式碼">
                                <svg class="icon" aria-hidden="true">
                                    <use xlink:href="#icon-htmlUrl"></use>
                                </svg>
                            </button>
                        </div>
                        <div class="copy-links-column">
                            <div class="copy-link-display disabled" data-type="url">
                                <span class="copy-link-text" id="urlLinkText"></span>
                            </div>
                            <div class="copy-link-display disabled" data-type="markdown">
                                <span class="copy-link-text" id="markdownLinkText"></span>
                            </div>
                            <div class="copy-link-display disabled" data-type="html">
                                <span class="copy-link-text" id="htmlLinkText"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="progressContainer" class="progressContainer">
                    <div id="progressBar" class="progressBar"></div>
                </div>
            </form>
            
            <div class="system-links" style="margin-top: 15px; text-align: center; border-top: 1px solid var(--border-white-20); padding-top: 15px;">
                <a href="/storage/podcast.xml" target="_blank" style="color: var(--link-color); font-weight: bold; font-size: 14px;">
                    🎧 Podcast RSS 訂閱連結 (XML)
                </a>
                <p style="font-size: 12px; color: var(--text-gray); margin-top: 5px;">影片上傳完成後，RSS 將自動更新</p>
            </div>
        </div>

        <!-- 图片信息展示 -->
        <div id="imageInfo" class="imageInfo blur">
            <div class="image-info-block">
                <div class="info-header">
                    <svg class="icon info-icon" aria-hidden="true">
                        <use xlink:href="#icon-imageUrl"></use>
                    </svg>
                    <h3>原始圖片</h3>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">尺寸</span>
                        <span class="info-value" id="originalWidth"></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">大小</span>
                        <span class="info-value" id="originalSize"></span>
                    </div>
                </div>
            </div>
            <div class="image-info-block">
                <div class="info-header">
                    <svg class="icon info-icon" aria-hidden="true">
                        <use xlink:href="#icon-up"></use>
                    </svg>
                    <h3>壓縮後</h3>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">尺寸</span>
                        <span class="info-value" id="compressedWidth"></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">大小</span>
                        <span class="info-value" id="compressedSize"></span>
                    </div>
                </div>
            </div>
            <div class="compression-stats">
                <div class="stat-badge">
                    <span class="stat-label">壓縮率</span>
                    <span class="stat-value" id="compressionRatio">-</span>
                </div>
                <div class="stat-badge">
                    <span class="stat-label">節省空間</span>
                    <span class="stat-value" id="savedSpace">-</span>
                </div>
            </div>
        </div>
        <!-- 隐藏的元素用于兼容 -->
        <span id="originalHeight" style="display:none;"></span>
        <span id="compressedHeight" style="display:none;"></span>
        <div class="keyboard-hints blur">
            <div class="hint-item">
                <div class="kbd-group">
                    <kbd>←</kbd><kbd>→</kbd>
                </div>
                <span>切換圖片</span>
            </div>
            <div class="hint-item">
                <div class="kbd-group">
                    <kbd>Ctrl</kbd><span class="plus">+</span><kbd>V</kbd>
                </div>
                <span>貼上上傳</span>
            </div>
            <div class="hint-item">
                <div class="kbd-group">
                    <kbd>Ctrl</kbd><span class="plus">+</span><kbd>點擊</kbd>
                </div>
                <span>批次複製</span>
            </div>
            <div class="hint-item">
                <div class="kbd-group">
                    <kbd>滾輪</kbd>
                </div>
                <span>切換圖片</span>
            </div>
            <div class="hint-item">
                <div class="kbd-group">
                    <kbd>Esc</kbd>
                </div>
                <span>清除圖片</span>
            </div>
        </div>
    </main>
    <footer>
        <?php if (($_ENV['DEMO_MODE'] ?? 'false') === 'true'): ?>
        <div style="padding: 10px;margin-bottom: 10px;border-radius: 10px;font-size: 15px;font-weight: bold;backdrop-filter: blur(10px);-webkit-backdrop-filter: blur(10px);border: 1px solid rgb(255 255 255 / 20%);background: rgb(255 60 60 / 30%);animation: fadeIn 0.5s ease-in-out forwards;">⚠️ 示範站點 - 所有檔案皆為公開可見且可能被刪除</div>
        <?php endif; ?>
        <div class="icp">
            <span>© <?php echo date('Y'); ?></span>
            <button class="logo-btn">站點聲明</button>
            <em class="logotitle blur">本站不保證內容、時效與穩定性。請嚴格遵守相關法律法規，尊重版權、著作權等權利；內容均由使用者自行上傳，所有檔案的用途與性質皆與本站無關，本站對所有檔案合法性概不負責，亦不承擔任何法律責任。</em>
        </div>
    </footer>
    <script type="module" src="static/js/main.js" data-max-file-size="<?php echo $maxFileSize; ?>">
    </script>
    <!-- 引入鼠标指针跟随特效 -->
    <script type="text/javascript" src="static/js/cursor.js" defer data-lazy="true"></script>
    <script src="//at.alicdn.com/t/c/font_4623353_hb4c04qfi4u.js"></script>
</body>
</html>
