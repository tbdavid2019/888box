<?php
require_once __DIR__ . '/schema.php';

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        // 安装页面不需要数据库连接
        if (strpos($_SERVER['REQUEST_URI'], '/install') === 0) return;
        
        $envFile = dirname(__DIR__) . '/.env';
        
        // 未安装则跳转
        if (!file_exists($envFile)) {
            header('Location: /install');
            exit;
        }
        
        $this->loadEnv($envFile);
        $this->connect();
    }

    private function loadEnv($envFile) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] === '#' || strpos($line, '=') === false) continue;
            
            [$name, $value] = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
        }
    }

    private function connect() {
        $dbPath = dirname(__DIR__) . '/storage/database.db';
        $dbDir = dirname($dbPath);
        
        if (!is_dir($dbDir)) mkdir($dbDir, 0755, true);
        
        // 如果数据库文件不存在，检查是否需要迁移
        if (!file_exists($dbPath)) {
            // 检测到 MySQL 配置，引导用户迁移
            if (isset($_ENV['DB_HOST']) && strpos($_SERVER['REQUEST_URI'], 'migrate.php') === false) {
                header('Location: /migrate.php');
                exit;
            }
        }
        
        try {
            $this->connection = new PDO('sqlite:' . $dbPath);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->exec('PRAGMA foreign_keys = ON');
            ensureCoreSchema($this->connection);
            $this->ensureCoreConfigs();
        } catch (PDOException $e) {
            throw new Exception('数据库连接失败: ' . $e->getMessage());
        }
    }

    private function ensureCoreConfigs() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $siteUrl = $protocol . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        seedCoreConfigs($this->connection, $siteUrl);
    }

    public static function getInstance() {
        return self::$instance ?? (self::$instance = new self());
    }

    public function getConnection() {
        return strpos($_SERVER['REQUEST_URI'], '/install') === 0 ? null : $this->connection;
    }

    public static function getConfig($pdo, $key = null) {
        if (!$pdo) return null;
        
        $stmt = $pdo->prepare("SELECT `key`, value FROM configs" . ($key ? " WHERE `key` = ?" : ""));
        $stmt->execute($key ? [$key] : []);
        
        $configs = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'value', 'key');
        
        // 允許透過 .env 覆蓋資料庫設定 (例如 .env 中設定 S3_BUCKET 會覆蓋 s3_bucket)
        if ($key) {
            $envKey = strtoupper($key);
            if (isset($_ENV[$envKey])) {
                return $_ENV[$envKey];
            }
            return $configs[$key] ?? null;
        } else {
            foreach ($configs as $k => $v) {
                $envKey = strtoupper($k);
                if (isset($_ENV[$envKey])) {
                    $configs[$k] = $_ENV[$envKey];
                }
            }
            // 確保原本資料庫沒有，但 .env 有的 s3_ 開頭設定也被帶入
            foreach ($_ENV as $envKey => $envVal) {
                $lowerKey = strtolower($envKey);
                if (strpos($lowerKey, 's3_') === 0 && !isset($configs[$lowerKey])) {
                    $configs[$lowerKey] = $envVal;
                }
            }
            return $configs;
        }
    }

    public static function getStorageConfig($type = null) {
        $configs = json_decode(file_get_contents(__DIR__ . '/configs.json'), true)['storage_types'];
        return $type ? ($configs[$type] ?? null) : $configs;
    }

    public function __clone() {}
    public function __wakeup() {}
}

/**
 * 根據路徑生成統一的本地域名遮罩 URL，以隱藏 S3/OSS 等外部雲端儲存端點
 */
function getMaskedUrl($url, $path) {
    if (empty($path)) {
        return $url;
    }
    $cleanPath = ltrim($path, '/');
    $domain = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    return $domain . '/' . $cleanPath;
}

/**
 * 為新上傳的資源產生隨機 share token（32-char hex）
 */
function generateShareToken() {
    return bin2hex(random_bytes(16));
}

/**
 * 根據 share_token 建立公開分享 URL，避免序號枚舉攻擊。
 * $asset 可以是整行資料陣列（含 share_token），或單純的 token 字串。
 */
function buildAssetShareUrl($asset, $config = null) {
    // 相容舊呼叫：傳入純數字 ID 時，嘗試從 DB 撈 token（最佳做法是傳陣列）
    if (is_numeric($asset)) {
        // 舊版相容層：呼叫方應改傳完整 $asset 陣列，這裡僅作降級保底
        $token = null;
    } elseif (is_array($asset)) {
        $token = $asset['share_token'] ?? null;
    } else {
        $token = (string)$asset;
    }

    if (empty($token)) {
        return '';
    }

    if (!empty($_SERVER['HTTP_HOST'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . $_SERVER['HTTP_HOST'] . '/view.php?token=' . urlencode($token);
    }

    $siteDomain = '';
    if (is_array($config) && !empty($config['site_domain'])) {
        $domains = array_filter(array_map('trim', explode(',', $config['site_domain'])));
        foreach ($domains as $domain) {
            if ($domain !== '*') {
                $siteDomain = $domain;
                break;
            }
        }
    }

    if ($siteDomain === '') {
        $siteDomain = 'http://localhost';
    } elseif (!preg_match('/^https?:\/\//i', $siteDomain)) {
        $siteDomain = 'https://' . $siteDomain;
    }

    return rtrim($siteDomain, '/') . '/view.php?token=' . urlencode($token);
}
