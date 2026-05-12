<?php
/** @deprecated Use api.php?action=upload instead */
session_start();
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $config = Database::getConfig($pdo);
    
    // 檢查是否需要登入限制
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
    <title>888box 影片託管中心</title>
    <link rel="shortcut icon" href="static/favicon.svg">
    <link rel="stylesheet" href="static/css/video_ui.css?v=<?php echo time(); ?>">
</head>
<body>
    <header class="video-header">
        <h1>🎬 888box 影片託管中心</h1>
        <div class="nav-links">
            <a href="javascript:void(0);" onclick="forceClearCache()">🔄 清除快取並重整</a>
            <a href="/">🏠 門戶</a>
            <a href="/admin/video.php" target="_blank">⚙️ 管理後台</a>
        </div>
    </header>

    <main class="video-main">
        <div class="upload-panel" id="dropZone">
            <div id="uploadPrompt">
                <div class="icon-upload">📁</div>
                <h2>拖曳多部影片檔案至此</h2>
                <p>或點擊選擇檔案 (支援 mp4, webm, mov, mkv)</p>
                <input type="file" id="videoInput" accept="video/*" multiple style="display:none;">
            </div>
            
            <div id="queueArea" style="display:none; text-align: left;">
                <h3 style="margin-top:0; margin-bottom:20px; border-bottom:1px solid var(--border); padding-bottom:10px;">影片上傳佇列 (依序上傳)</h3>
                
                <div class="metadata-form" style="margin-bottom: 25px; background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; border: 1px solid var(--border);">
                    <div style="margin-bottom:15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">統一影片標題 (留空則使用檔名)</label>
                        <input type="text" id="batchTitle" placeholder="例如：我的精彩影片" style="width:100%; padding:10px; border-radius:6px; border:1px solid #444; background:#222; color:#fff;">
                    </div>
                    <div style="margin-bottom:15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">統一影片描述</label>
                        <textarea id="batchDesc" rows="3" placeholder="請輸入影片描述..." style="width:100%; padding:10px; border-radius:6px; border:1px solid #444; background:#222; color:#fff;"></textarea>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:bold;">存取密碼 (選填)</label>
                        <input type="password" id="batchPass" placeholder="設定密碼後，Podcast RSS 將會排除此影片" style="width:100%; padding:10px; border-radius:6px; border:1px solid #444; background:#222; color:#fff;">
                    </div>
                </div>

                <div class="action-buttons action-buttons-top">
                    <button type="button" class="btn secondary" data-video-action="clear">清空列表</button>
                    <button type="button" class="btn primary" data-video-action="upload">開始依序上傳</button>
                </div>

                <div id="fileList"></div>

                <div class="action-buttons action-buttons-bottom">
                    <button type="button" class="btn secondary" data-video-action="clear">清空列表</button>
                    <button type="button" class="btn primary" data-video-action="upload">開始依序上傳</button>
                </div>
            </div>
        </div>

        <div class="rss-notify" style="margin-top: 20px;">
            <p>🚀 上傳的影片將會自動加入至 Podcast 訂閱中！</p>
            <a href="/storage/podcast.xml" target="_blank" class="rss-link">🎧 點此查看 Podcast RSS 連結 (XML)</a>
        </div>

        <section id="videoHistorySection" class="history-panel" hidden>
            <div class="history-header">
                <h3>最近上傳</h3>
                <button type="button" id="clearVideoHistoryBtn" class="history-clear-btn">清除紀錄</button>
            </div>
            <div id="videoHistoryEmpty" class="history-empty">目前還沒有最近上傳紀錄</div>
            <div id="videoHistoryList" class="history-list"></div>
        </section>
    </main>

    <footer style="margin-top: 40px; padding: 20px; text-align: center; color: #888; font-size: 0.9rem;">
        <?php if (($_ENV['DEMO_MODE'] ?? 'false') === 'true'): ?>
        <div style="padding: 10px; margin: 0 auto 20px auto; max-width: 800px; border-radius: 10px; font-size: 15px; font-weight: bold; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgb(255 255 255 / 20%); background: rgb(255 60 60 / 30%); animation: fadeIn 0.5s ease-in-out forwards;">⚠️ 示範站點 - 所有檔案皆為公開可見且可能被刪除</div>
        <?php endif; ?>
        
        <div style="margin-bottom: 15px; display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
            <a href="upload_image.php" style="color: #bbb; text-decoration: none;">🖼️ 圖片託管</a>
            <a href="upload_video.php" style="color: #bbb; text-decoration: none;">🎬 影片託管</a>
            <a href="upload_file.php" style="color: #bbb; text-decoration: none;">📂 文件託管</a>
        </div>
        <div>
            <span>© <?php echo date('Y'); ?> 888box</span> | 
            <span>Created by <a href="https://david888.com" target="_blank" style="color: #bbb; text-decoration: none; font-weight: bold;">DAVID888</a></span> | 
            <a href="javascript:void(0);" onclick="forceClearCache()" style="color: #888; text-decoration: none;">清除快取並重整</a> | 
            <a href="/skill.php" target="_blank" style="color: #888; text-decoration: none;">AI Agent Skills</a>
        </div>
        <div style="margin-top: 10px; font-size: 0.8rem; color: #666; max-width: 800px; margin-left: auto; margin-right: auto; line-height: 1.5;">
            本站不保證內容、時效與穩定性。請嚴格遵守相關法律法規，尊重版權、著作權等權利；內容均由使用者自行上傳，本站對所有檔案合法性概不負責，亦不承擔任何法律責任。
        </div>
    </footer>

    <script src="static/js/upload_history.js?v=<?php echo time(); ?>"></script>
    <script src="static/js/video_app.js?v=<?php echo time(); ?>"></script>
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
</body>
</html>
