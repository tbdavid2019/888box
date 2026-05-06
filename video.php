<?php
/**
 * PixPro Video Upload Endpoint
 */
ob_start();

require_once 'vendor/autoload.php';
require_once 'config/database.php';
require_once 'config/upload.php';
require_once 'config/video_logic.php';

// 初始化
$db = Database::getInstance();
$pdo = $db->getConnection();
$config = Database::getConfig($pdo);

// 设置CORS响应头
setCorsHeaders();

try {
    // 1. 验证请求方法
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respondAndExit(['result' => 'error', 'code' => 405, 'message' => '僅支援 POST 請求']);
    }

    if (empty($_FILES)) {
        respondAndExit(['result' => 'error', 'code' => 204, 'message' => '無文件上傳']);
    }

    // 2. 验证权限
    validateToken();

    // 3. 验证上传次数限制 (重用 api.php 逻辑)
    $maxUploadsPerDay = getConfigValue($pdo, 'max_uploads_per_day');
    $uploadCheck = isUploadAllowed($maxUploadsPerDay);
    if ($uploadCheck !== true) {
        respondAndExit(['result' => 'error', 'code' => 429, 'message' => $uploadCheck]);
    }

    // 4. 处理影片上传 (一次只处理一个文件，或者循环处理)
    $results = [];
    foreach ($_FILES as $file) {
        // 验证文件大小 (针对影片可能需要单独的限制，这里先用全局限制)
        $maxFileSize = getConfigValue($pdo, 'max_file_size'); // 預設可能太小，建議影片可以有更大的限制
        // 如果是影片，我們可以考慮放大 10 倍，或者單獨配置
        $videoMaxFileSize = $maxFileSize * 10; 
        
        if ($file['size'] > $videoMaxFileSize) {
            $limitMB = $videoMaxFileSize / (1024 * 1024);
            respondAndExit(['result' => 'error', 'code' => 413, 'message' => "影片大小超過限制，最大允許 {$limitMB}MB"]);
        }

        $videoData = handleVideoUpload($file, $pdo);
        $results[] = $videoData;
    }

    // 5. 返回响应
    respondAndExit([
        'result' => 'success',
        'code' => 200,
        'message' => '影片上傳成功',
        'data' => count($results) === 1 ? $results[0] : $results
    ]);

} catch (Exception $e) {
    logMessage('影片上傳錯誤: ' . $e->getMessage());
    respondAndExit(['result' => 'error', 'code' => 500, 'message' => '伺服器錯誤: ' . $e->getMessage()]);
}
