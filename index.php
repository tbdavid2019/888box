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
    <title>888box 圖片託管中心</title>
    <meta name="keywords" content="檔案託管,影片上傳,高效圖片壓縮,前後台設計,Podcast,自動生成RSS,AWS S3,本機儲存,多格式支援,瀑布流管理,管理後台,自訂壓縮率,尺寸限制">
    <meta name="description" content="一款專為個人需求設計的高效媒體託管解決方案，整合強大的圖片與影片處理功能。提供自訂壓縮率與尺寸設定，有效降低儲存成本。搭配 AWS S3 儲存（支援相容 S3 的各類雲端空間）及彈性的本機儲存選項。特色包含自動提取影片 MetaData、封面圖生成及 Podcast RSS 自動更新功能。">
    <link rel="shortcut icon" href="static/favicon.svg">
    <link rel="stylesheet" type="text/css" href="static/css/styles.css?v=<?php echo time(); ?>">
</head>
<body>
    <header class="blur" style="width: 100%; max-width: 1200px; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; box-sizing: border-box;">
        <h1 style="margin: 0; font-size: 1.5rem; color: #e5e7eb; padding: 15px 0;">🖼️ 888box 圖片託管中心</h1>
        <a href="/admin/" target="_blank" title="後台" class="header-link">
            <svg class="icon" aria-hidden="true">
                <use xlink:href="#icon-Setting"></use>
            </svg>
        </a>
    </header>

    <!-- 影片專屬入口 Banner -->
    <div style="width: 100%; max-width: 1200px; margin: 0 auto 25px auto; text-align: center; padding: 15px; background: linear-gradient(135deg, #3b82f6, #8b5cf6); border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); box-sizing: border-box;">
        <a href="upload_video.php" style="color: white; font-size: 1.2rem; font-weight: bold; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 10px;">
            <span>🎬</span> 點此進入【888box 影片託管中心】 (提供專屬拖曳預覽、獨立操作面板) <span>👉</span>
        </a>
    </div>

    <main>
        <!-- 左側：上傳區與縮圖 -->
        <div class="left-column" style="flex: 1; width: 100%; max-width: 540px;">
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
                            <input type="file" id="imageInput" name="image[]" accept="image/png, image/jpeg, image/webp, image/svg+xml, image/gif" multiple>
                            <div id="imagePreviewContainer" class="imagePreviewContainer">
                                <button id="prevButton" class="nav-button prev-button">
                                    <svg class="icon" aria-hidden="true">
                                        <use xlink:href="#icon-Left-arrow"></use>
                                    </svg>
                                </button>
                                <img id="imagePreview" class="imagePreview" src="" alt="">
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
                        <input type="text" id="pasteOrUrlInput" class="pasteOrUrlInput" placeholder="輸入圖片網址即可自動上傳，或使用 Ctrl+V 貼上" title="注意：部分網站設有防盜鏈，可能無法直接下載">
                    </div>

                    <div id="progressContainer" class="progressContainer" style="position: relative; border-radius: 5px; margin-top: 15px;">
                        <div id="progressBar" class="progressBar"></div>
                    </div>
                </form>
            </div>
            
            <div class="keyboard-hints blur" style="margin-top: 20px;">
                <div class="hint-item">
                    <div class="kbd-group"><kbd>←</kbd><kbd>→</kbd></div><span>切換檔案</span>
                </div>
                <div class="hint-item">
                    <div class="kbd-group"><kbd>Ctrl</kbd><span class="plus">+</span><kbd>V</kbd></div><span>貼上上傳</span>
                </div>
                <div class="hint-item">
                    <div class="kbd-group"><kbd>Esc</kbd></div><span>清除預覽</span>
                </div>
            </div>
        </div>

        <!-- 右側：設定、資訊與連結區 -->
        <div class="right-column" style="flex: 1; width: 100%; max-width: 540px; display: flex; flex-direction: column; gap: 20px;">
            <div class="upload-container blur" style="margin-bottom: 0;">
                <!-- 压缩比率调整 -->
                <div class="quality-section" id="qualityControlSection">
                    <label for="qualityInput">圖片清晰度 60-100<output id="qualityOutput" class="qualityOutput">60</output></label>
                    <input type="range" id="qualityInput" name="quality" min="60" max="100" value="60" step="1">
                </div>

                <!-- 复制按钮区域 -->
                <div class="copy-section">
                    <div class="copy-tab-buttons">
                        <div class="copy-icons-column">
                            <button class="copy-tab-btn" data-type="url" title="複製連結">
                                <svg class="icon" aria-hidden="true"><use xlink:href="#icon-imageUrl"></use></svg>
                            </button>
                            <button class="copy-tab-btn" data-type="markdown" title="複製 Markdown">
                                <svg class="icon" aria-hidden="true"><use xlink:href="#icon-markdownUrl"></use></svg>
                            </button>
                            <button class="copy-tab-btn" data-type="html" title="複製 HTML">
                                <svg class="icon" aria-hidden="true"><use xlink:href="#icon-htmlUrl"></use></svg>
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
            </div>

            <!-- 图片/影片信息展示 -->
            <div id="imageInfo" class="imageInfo blur" style="margin-bottom: 0;">
                <div class="image-info-block">
                    <div class="info-header">
                        <svg class="icon info-icon" aria-hidden="true"><use xlink:href="#icon-imageUrl"></use></svg>
                        <h3 id="infoTitleBefore">原始圖片</h3>
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
                <div class="image-info-block" id="infoBlockAfter">
                    <div class="info-header">
                        <svg class="icon info-icon" aria-hidden="true"><use xlink:href="#icon-up"></use></svg>
                        <h3 id="infoTitleAfter">壓縮後</h3>
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
                <div class="compression-stats" id="compressionStatsBox">
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
        </div>
    </main>
    <footer>
        <?php if (($_ENV['DEMO_MODE'] ?? 'false') === 'true'): ?>
        <div style="padding: 10px;margin-bottom: 10px;border-radius: 10px;font-size: 15px;font-weight: bold;backdrop-filter: blur(10px);-webkit-backdrop-filter: blur(10px);border: 1px solid rgb(255 255 255 / 20%);background: rgb(255 60 60 / 30%);animation: fadeIn 0.5s ease-in-out forwards;">⚠️ 示範站點 - 所有檔案皆為公開可見且可能被刪除</div>
        <?php endif; ?>
        <div class="icp">
            <span>© <?php echo date('Y'); ?></span>
            <button class="logo-btn" onclick="forceClearCache()">清除快取並重整</button>
            <button class="logo-btn">站點聲明</button>
            <em class="logotitle blur">本站不保證內容、時效與穩定性。請嚴格遵守相關法律法規，尊重版權、著作權等權利；內容均由使用者自行上傳，所有檔案的用途與性質皆與本站無關，本站對所有檔案合法性概不負責，亦不承擔任何法律責任。</em>
        </div>
    </footer>
    <script type="module" src="static/js/main.js?v=<?php echo time(); ?>" data-max-file-size="<?php echo $maxFileSize; ?>">
    </script>
    <script>
        function forceClearCache() {
            if ('caches' in window) {
                caches.keys().then(function(names) {
                    for (let name of names) caches.delete(name);
                });
            }
            window.location.reload(true);
        }
    </script>
    <script src="//at.alicdn.com/t/c/font_4623353_hb4c04qfi4u.js"></script>
</body>
</html>
