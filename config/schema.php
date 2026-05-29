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
            mime_type VARCHAR(100) NULL,
            is_video INTEGER DEFAULT 0,
            is_file INTEGER DEFAULT 0,
            is_audio INTEGER DEFAULT 0
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
        'mime_type' => 'VARCHAR(100) NULL',
        'is_video' => 'INTEGER DEFAULT 0',
        'is_file' => 'INTEGER DEFAULT 0',
        'is_audio' => 'INTEGER DEFAULT 0'
    ];
}

function getVideoAssetConditionSql() {
    return "("
        . "LOWER(COALESCE(mime_type, '')) LIKE 'video/%'"
        . " OR LOWER(COALESCE(url, '')) LIKE '%.mp4'"
        . " OR LOWER(COALESCE(url, '')) LIKE '%.webm'"
        . " OR LOWER(COALESCE(url, '')) LIKE '%.mov'"
        . " OR LOWER(COALESCE(url, '')) LIKE '%.mkv'"
        . " OR LOWER(COALESCE(path, '')) LIKE '%.mp4'"
        . " OR LOWER(COALESCE(path, '')) LIKE '%.webm'"
        . " OR LOWER(COALESCE(path, '')) LIKE '%.mov'"
        . " OR LOWER(COALESCE(path, '')) LIKE '%.mkv'"
        . ")";
}

function getImageAssetConditionSql() {
    return "("
        . "LOWER(COALESCE(mime_type, '')) LIKE 'image/%'"
        . " OR LOWER(COALESCE(url, '')) LIKE '%.jpg'"
        . " OR LOWER(COALESCE(url, '')) LIKE '%.jpeg'"
        . " OR LOWER(COALESCE(url, '')) LIKE '%.png'"
        . " OR LOWER(COALESCE(url, '')) LIKE '%.gif'"
        . " OR LOWER(COALESCE(url, '')) LIKE '%.webp'"
        . " OR LOWER(COALESCE(url, '')) LIKE '%.svg'"
        . " OR LOWER(COALESCE(path, '')) LIKE '%.jpg'"
        . " OR LOWER(COALESCE(path, '')) LIKE '%.jpeg'"
        . " OR LOWER(COALESCE(path, '')) LIKE '%.png'"
        . " OR LOWER(COALESCE(path, '')) LIKE '%.gif'"
        . " OR LOWER(COALESCE(path, '')) LIKE '%.webp'"
        . " OR LOWER(COALESCE(path, '')) LIKE '%.svg'"
        . ")";
}

function getAudioAssetConditionSql() {
    return "("
        . "LOWER(COALESCE(mime_type, '')) LIKE 'audio/%'"
        . " OR LOWER(COALESCE(url, '')) LIKE '%.mp3'"
        . " OR LOWER(COALESCE(url, '')) LIKE '%.wav'"
        . " OR LOWER(COALESCE(url, '')) LIKE '%.aac'"
        . " OR LOWER(COALESCE(url, '')) LIKE '%.ogg'"
        . " OR LOWER(COALESCE(url, '')) LIKE '%.m4a'"
        . " OR LOWER(COALESCE(url, '')) LIKE '%.flac'"
        . " OR LOWER(COALESCE(path, '')) LIKE '%.mp3'"
        . " OR LOWER(COALESCE(path, '')) LIKE '%.wav'"
        . " OR LOWER(COALESCE(path, '')) LIKE '%.aac'"
        . " OR LOWER(COALESCE(path, '')) LIKE '%.ogg'"
        . " OR LOWER(COALESCE(path, '')) LIKE '%.m4a'"
        . " OR LOWER(COALESCE(path, '')) LIKE '%.flac'"
        . ")";
}

function getCoreConfigDefaults($siteUrl) {
    return [
        'storage' => ['local', '儲存方式'],
        'url_prefix' => ['', '圖片代理'],
        'local_cdn_domain' => ['', '本地CDN域名'],
        'per_page' => ['20', '每頁顯示數量'],
        'login_restriction' => ['false', '登入保護'],
        'max_uploads_per_day' => ['100', '每日上傳限制'],
        'max_file_size' => [(string) (100 * 1024 * 1024), '單一圖片大小限制（Bytes）'],
        'max_video_size' => ['500', '單一影片大小限制（MB）'],
        'max_audio_size' => ['100', '單一音訊大小限制（MB）'],
        'output_format' => ['webp', '輸出圖片格式'],
        'site_domain' => [$siteUrl, '網站網域'],
        'active_theme' => ['middle_east_dart', '當前配色主題'],
        'rss_token_enabled' => ['false', 'RSS Token 保護'],
        'rss_token' => ['', 'RSS Token']
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
    createCoreTables($pdo);
    normalizeConfigsTable($pdo);
    ensureColumns($pdo, 'images', getCoreImageColumns());
    backfillAssetFlags($pdo);
}

function backfillAssetFlags($pdo) {
    $videoCondition = getVideoAssetConditionSql();
    $imageCondition = getImageAssetConditionSql();
    $audioCondition = getAudioAssetConditionSql();

    $pdo->exec("
        UPDATE images
        SET
            is_video = CASE
                WHEN $videoCondition THEN 1
                ELSE 0
            END,
            is_audio = CASE
                WHEN $audioCondition THEN 1
                ELSE 0
            END,
            is_file = CASE
                WHEN $videoCondition THEN 0
                WHEN $imageCondition THEN 0
                WHEN $audioCondition THEN 0
                ELSE 1
            END
    ");
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
