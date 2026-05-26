<?php
/** @deprecated Use api.php?action=upload instead */
session_start();

require_once 'config/database.php';
require_once 'config/theme_helper.php';


try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $config = Database::getConfig($pdo);
    
    // 获取上传限制配置，缺值時回退到 100MB
    $maxFileSize = 100 * 1024 * 1024;
    $stmt = $pdo->prepare("SELECT value FROM configs WHERE `key` = 'max_file_size'");
    $stmt->execute();
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $configuredMaxFileSize = (int)$row['value'];
        if ($configuredMaxFileSize > 0) {
            $maxFileSize = $configuredMaxFileSize;
        }
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
    <?php renderThemeStyles($pdo); ?>

    <style>
        :root {
            --page-muted: #7f88b2;
            --page-text: #c0caf5;
            --page-surface: rgba(36, 40, 59, 0.72);
            --page-surface-strong: rgba(41, 46, 66, 0.82);
            --page-border: rgba(122, 162, 247, 0.18);
            --page-link: #7dcfff;
        }

        .image-history-panel {
            width: 100%;
            max-width: 1200px;
            margin: 24px auto 0;
            padding: 0 20px;
            box-sizing: border-box;
        }

        .image-history-card {
            border-radius: 20px;
            padding: 24px;
        }

        .image-history-card[hidden] {
            display: none;
        }

        .image-history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }

        .image-history-header h3 {
            margin: 0;
            color: var(--page-text);
        }

        .image-history-clear {
            border: 1px solid var(--page-border);
            background: var(--page-surface);
            color: var(--page-text);
            border-radius: 999px;
            padding: 8px 14px;
            cursor: pointer;
            font-weight: bold;
        }

        .image-history-empty {
            color: var(--page-muted);
            text-align: center;
            padding: 20px 12px 8px;
        }

        .image-history-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
        }

        .image-history-item {
            padding: 14px;
            border-radius: 16px;
            background: var(--page-surface);
            border: 1px solid var(--page-border);
        }

        .image-history-thumb {
            width: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
            border-radius: 12px;
            display: block;
            background: rgba(22, 22, 30, 0.48);
            margin-bottom: 12px;
        }

        .image-history-title {
            color: var(--page-text);
            font-weight: bold;
            margin-bottom: 8px;
            word-break: break-word;
        }

        .image-history-link {
            display: block;
            color: var(--page-link);
            text-decoration: none;
            font-size: 0.9rem;
            word-break: break-all;
            margin-bottom: 8px;
        }

        .image-history-meta {
            color: var(--page-muted);
            font-size: 0.82rem;
            margin-bottom: 12px;
        }

        .image-history-actions {
            display: flex;
            gap: 8px;
        }

        .image-history-btn,
        .image-history-open {
            flex: 1;
            border-radius: 999px;
            padding: 8px 12px;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            font-size: 0.9rem;
            cursor: pointer;
        }

        .image-history-btn {
            border: none;
            background: #7aa2f7;
            color: #1a1b26;
        }

        .image-history-open {
            border: 1px solid var(--page-border);
            background: rgba(31, 35, 53, 0.72);
            color: var(--page-text);
        }

        .page-footer {
            margin-top: 40px;
            padding: 20px;
            text-align: center;
            color: var(--page-muted);
            font-size: 0.9rem;
        }

        .page-footer a {
            color: var(--page-link);
            text-decoration: none;
        }

        .page-footer .legal-note {
            margin-top: 10px;
            font-size: 0.8rem;
            color: #565f89;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.5;
        }

        .image-stats-card {
            border-radius: 20px;
            padding: 24px;
        }

        .image-stats-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .image-stats-header h3 {
            margin: 0;
            color: var(--page-text);
        }

        .image-stats-note {
            color: var(--page-muted);
            font-size: 0.82rem;
        }

        .image-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .image-stat-item {
            border-radius: 16px;
            padding: 16px;
            background: var(--page-surface);
            border: 1px solid var(--page-border);
        }

        .image-stat-label {
            display: block;
            color: var(--page-muted);
            font-size: 0.85rem;
            margin-bottom: 6px;
        }

        .image-stat-value {
            display: block;
            color: var(--page-text);
            font-size: 1.2rem;
            font-weight: bold;
        }

        @media (max-width: 840px) {
            .image-stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="blur" style="width: 100%; max-width: 1200px; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; box-sizing: border-box; margin: 0 auto 25px auto;">
        <h1 style="margin: 0; font-size: 1.5rem; color: var(--page-text); padding: 15px 0;">🖼️ 888box 圖片託管中心</h1>
        <div style="display: flex; gap: 15px; align-items: center;">
            <a href="/" style="color: white; text-decoration: none;">🏠 門戶</a>
            <a href="/admin/" target="_blank" style="color: white; text-decoration: none;">⚙️ 管理後台</a>
        </div>
    </header>

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

                <!-- 存取密碼 -->
                <div class="password-section" style="margin-top: 15px; display: flex; flex-direction: column; gap: 8px;">
                    <label for="imagePassword" style="font-weight: bold; color: var(--page-text); font-size: 0.9rem;">設定存取密碼 (選填)</label>
                    <input type="password" id="imagePassword" name="password" placeholder="留空則公開" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--page-border); background: rgba(22,22,30,0.48); color: var(--page-text); box-sizing: border-box;" autocomplete="new-password">
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

            <section class="image-stats-card blur">
                <div class="image-stats-header">
                    <h3>本機上傳統計</h3>
                    <span class="image-stats-note">僅限此裝置瀏覽器</span>
                </div>
                <div class="image-stats-grid">
                    <div class="image-stat-item">
                        <span class="image-stat-label">本批成功</span>
                        <strong id="imageSessionCount" class="image-stat-value">0 / 0</strong>
                    </div>
                    <div class="image-stat-item">
                        <span class="image-stat-label">今日上傳</span>
                        <strong id="imageDailyCount" class="image-stat-value">0</strong>
                    </div>
                    <div class="image-stat-item">
                        <span class="image-stat-label">累計上傳</span>
                        <strong id="imageTotalCount" class="image-stat-value">0</strong>
                    </div>
                </div>
            </section>

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
    <section class="image-history-panel">
        <div id="imageHistorySection" class="image-history-card blur" hidden>
            <div class="image-history-header">
                <h3>最近上傳</h3>
                <button type="button" id="clearImageHistoryBtn" class="image-history-clear">清除紀錄</button>
            </div>
            <div id="imageHistoryEmpty" class="image-history-empty">目前還沒有最近上傳紀錄</div>
            <div id="imageHistoryList" class="image-history-list"></div>
        </div>
    </section>
    <footer class="page-footer">
        <?php if (($_ENV['DEMO_MODE'] ?? 'false') === 'true'): ?>
        <div style="padding: 10px; margin: 0 auto 20px auto; max-width: 800px; border-radius: 10px; font-size: 15px; font-weight: bold; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgb(255 255 255 / 20%); background: rgb(255 60 60 / 30%); animation: fadeIn 0.5s ease-in-out forwards;">⚠️ 示範站點 - 所有檔案皆為公開可見且可能被刪除</div>
        <?php endif; ?>
        
        <div style="margin-bottom: 15px; display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
            <a href="upload_image.php">🖼️ 圖片託管</a>
            <a href="upload_video.php">🎬 影片託管</a>
            <a href="upload_file.php">📂 文件託管</a>
        </div>
        <div>
            <span>© <?php echo date('Y'); ?> 888box</span> | 
            <span>Created by <a href="https://david888.com" target="_blank" style="font-weight: bold;">DAVID888</a></span> | 
            <a href="javascript:void(0);" onclick="forceClearCache()">清除快取並重整</a> | 
            <a href="/skill.php" target="_blank">AI Agent Skills</a>
        </div>
        <div class="legal-note">
            本站不保證內容、時效與穩定性。請嚴格遵守相關法律法規，尊重版權、著作權等權利；內容均由使用者自行上傳，本站對所有檔案合法性概不負責，亦不承擔任何法律責任。
        </div>
    </footer>
    <script src="static/js/upload_history.js?v=<?php echo time(); ?>"></script>
    <script src="static/js/upload_stats.js?v=<?php echo time(); ?>"></script>
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
