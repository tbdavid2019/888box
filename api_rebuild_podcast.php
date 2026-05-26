<?php
ob_start();
session_start();
error_reporting(0);
ini_set('display_errors', '0');

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['result' => 'error', 'message' => '未登入，無權限'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once 'vendor/autoload.php';
require_once 'config/database.php';
require_once 'config/video_logic.php';

$db = Database::getInstance();
$pdo = $db->getConnection();
$config = Database::getConfig($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['result' => 'error', 'message' => '只支援 POST 請求'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $type = $_POST['type'] ?? 'video';
    if ($type === 'audio') {
        require_once 'config/audio_logic.php';
        rebuildAudioRSS($pdo, $config);
    } else {
        rebuildVideoRSS($pdo, $config);
    }

    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['result' => 'success', 'message' => 'Podcast RSS 已重建完成'], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['result' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
