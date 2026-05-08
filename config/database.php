<?php
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
            $this->ensureCoreConfigs();
        } catch (PDOException $e) {
            throw new Exception('数据库连接失败: ' . $e->getMessage());
        }
    }

    private function ensureCoreConfigs() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $siteUrl = $protocol . ($_SERVER['HTTP_HOST'] ?? 'localhost');

        $defaults = [
            'storage' => ['local', '儲存方式'],
            'url_prefix' => ['', '圖片代理'],
            'local_cdn_domain' => ['', '本地CDN域名'],
            'per_page' => ['20', '每頁顯示數量'],
            'login_restriction' => ['false', '登入保護'],
            'max_uploads_per_day' => ['50', '每日上傳限制'],
            'max_file_size' => [(string) (100 * 1024 * 1024), '單一圖片大小限制（Bytes）'],
            'max_video_size' => ['500', '單一影片大小限制（MB）'],
            'output_format' => ['webp', '輸出圖片格式'],
            'site_domain' => [$siteUrl, '網站網域']
        ];

        $existing = [];

        try {
            $existing = array_column(
                $this->connection->query("SELECT `key` FROM configs")->fetchAll(PDO::FETCH_ASSOC),
                'key'
            );
        } catch (Exception $e) {
            return;
        }

        $stmt = $this->connection->prepare(
            "INSERT INTO configs (`key`, value, description) VALUES (?, ?, ?)"
        );

        foreach ($defaults as $key => $config) {
            if (!in_array($key, $existing, true)) {
                $stmt->execute([$key, $config[0], $config[1]]);
            }
        }
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
