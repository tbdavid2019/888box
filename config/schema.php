<?php

function getCoreTableSql() {
    return [
        'images' => "CREATE TABLE IF NOT EXISTS images (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NULL,
            url VARCHAR(255) NOT NULL,
            path VARCHAR(255) NOT NULL,
            storage VARCHAR(50) NOT NULL,
            size INTEGER NOT NULL,
            upload_ip VARCHAR(45) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            title VARCHAR(255) DEFAULT '',
            description TEXT DEFAULT '',
            password VARCHAR(255) DEFAULT NULL,
            view_count INTEGER DEFAULT 0,
            report_count INTEGER DEFAULT 0,
            mime_type VARCHAR(100) NULL
        )",
        'users' => "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            token VARCHAR(32) NOT NULL UNIQUE
        )",
        'configs' => "CREATE TABLE IF NOT EXISTS configs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            `key` VARCHAR(50) NOT NULL UNIQUE,
            value TEXT,
            description VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )"
    ];
}

function getCoreImageColumns() {
    return [
        'title' => "VARCHAR(255) DEFAULT ''",
        'description' => "TEXT DEFAULT ''",
        'password' => 'VARCHAR(255) DEFAULT NULL',
        'view_count' => 'INTEGER DEFAULT 0',
        'report_count' => 'INTEGER DEFAULT 0',
        'mime_type' => 'VARCHAR(100) NULL'
    ];
}

function getCoreConfigDefaults($siteUrl) {
    return [
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
}

function createCoreTables($pdo) {
    foreach (getCoreTableSql() as $sql) {
        $pdo->exec($sql);
    }
}

function ensureColumns($pdo, $table, $columns) {
    $existingColumns = [];

    try {
        $stmt = $pdo->query("PRAGMA table_info($table)");
        $existingColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
    } catch (Exception $e) {
        return;
    }

    foreach ($columns as $name => $definition) {
        if (!in_array($name, $existingColumns, true)) {
            $pdo->exec("ALTER TABLE $table ADD COLUMN $name $definition");
        }
    }
}

function normalizeConfigsTable($pdo) {
    $existingColumns = [];

    try {
        $stmt = $pdo->query('PRAGMA table_info(configs)');
        $existingColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
    } catch (Exception $e) {
        return;
    }

    if (!in_array('updated_at', $existingColumns, true)) {
        return;
    }

    $pdo->beginTransaction();

    try {
        $pdo->exec("
            CREATE TABLE configs__new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                `key` VARCHAR(50) NOT NULL UNIQUE,
                value TEXT,
                description VARCHAR(255),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $pdo->exec("
            INSERT INTO configs__new (id, `key`, value, description, created_at)
            SELECT id, `key`, value, description, created_at
            FROM configs
        ");
        $pdo->exec('DROP TABLE configs');
        $pdo->exec('ALTER TABLE configs__new RENAME TO configs');
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function ensureCoreSchema($pdo) {
    normalizeConfigsTable($pdo);
    ensureColumns($pdo, 'images', getCoreImageColumns());
}

function seedCoreConfigs($pdo, $siteUrl) {
    $defaults = getCoreConfigDefaults($siteUrl);
    $existing = [];

    try {
        $existing = array_column(
            $pdo->query("SELECT `key` FROM configs")->fetchAll(PDO::FETCH_ASSOC),
            'key'
        );
    } catch (Exception $e) {
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO configs (`key`, value, description) VALUES (?, ?, ?)");

    foreach ($defaults as $key => $config) {
        if (!in_array($key, $existing, true)) {
            $stmt->execute([$key, $config[0], $config[1]]);
        }
    }
}
