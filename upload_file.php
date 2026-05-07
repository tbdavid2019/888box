<?php
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
    <title>888box 文件託管中心</title>
    <link rel="shortcut icon" href="static/favicon.svg">
    <link rel="stylesheet" href="static/css/video_ui.css?v=<?php echo time(); ?>"> <!-- 重用影片中心的 CSS 以保持風格一致 -->
    <style>
        :root {
            --accent: #34c759; /* 文件中心使用綠色調 */
        }
        .file-icon { font-size: 3rem; margin-bottom: 20px; color: var(--accent); }
    </style>
</head>
<body>
    <header class="video-header">
        <h1>📂 888box 文件託管中心</h1>
        <div class="nav-links">
            <a href="/">🏠 回到入口頁</a>
            <a href="/admin/file.php">⚙️ 文件管理</a>
        </div>
    </header>

    <main class="video-main">
        <div class="upload-panel" id="dropZone">
            <div id="uploadPrompt">
                <div class="file-icon">📄</div>
                <h2>拖曳檔案至此</h2>
                <p>支援 ZIP, PDF, Word, Excel, Visio, EPUB 等格式</p>
                <input type="file" id="fileInput" multiple style="display:none;">
            </div>
            
            <div id="queueArea" style="display:none; text-align: left;">
                <h3 style="margin-top:0; margin-bottom:20px; border-bottom:1px solid var(--border); padding-bottom:10px;">檔案上傳佇列</h3>
                <div id="fileList"></div>
                
                <div class="action-buttons" style="margin-top: 30px;">
                    <button id="cancelBtn" class="btn secondary">清空列表</button>
                    <button id="uploadBtn" class="btn primary">開始上傳</button>
                </div>
            </div>
        </div>

        <div class="info-notify" style="margin-top: 20px;">
            <p>💡 您可以為上傳的每個檔案單獨設置存取密碼。</p>
        </div>
    </main>

    <!-- 重用或修改影片上傳的 JS 邏輯 -->
    <script src="static/js/file_app.js?v=<?php echo time(); ?>"></script>
</body>
</html>
