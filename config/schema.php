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
            is_audio INTEGER DEFAULT 0,
            share_token VARCHAR(32) NULL
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
        )",
        'seals' => "CREATE TABLE IF NOT EXISTS seals (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            asset_id INTEGER NOT NULL,
            seal_token VARCHAR(64) NOT NULL UNIQUE,
            mode VARCHAR(20) NOT NULL,
            unlock_at INTEGER NOT NULL,
            pulse_interval INTEGER DEFAULT NULL,
            last_pulse_at INTEGER DEFAULT NULL,
            pulse_token_hash VARCHAR(64) DEFAULT NULL,
            max_views INTEGER DEFAULT NULL,
            view_count INTEGER NOT NULL DEFAULT 0,
            created_at INTEGER NOT NULL,
            updated_at INTEGER NOT NULL,
            burned_at INTEGER DEFAULT NULL,
            cleanup_at INTEGER DEFAULT NULL,
            FOREIGN KEY (asset_id) REFERENCES images(id) ON DELETE CASCADE
        )",
        'asset_capabilities' => "CREATE TABLE IF NOT EXISTS asset_capabilities (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            asset_id INTEGER NOT NULL UNIQUE,
            token_hash VARCHAR(64) NOT NULL UNIQUE,
            created_at INTEGER NOT NULL,
            FOREIGN KEY (asset_id) REFERENCES images(id) ON DELETE CASCADE
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
        'is_audio' => 'INTEGER DEFAULT 0',
        'share_token' => 'VARCHAR(32) NULL'
    ];
}

function ensureSealIndexes($pdo) {
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_seals_asset_id ON seals(asset_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_seals_unlock_at ON seals(unlock_at)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_seals_cleanup_at ON seals(cleanup_at)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_seals_pulse_token_hash ON seals(pulse_token_hash)');
    $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_seals_one_active_per_asset ON seals(asset_id) WHERE burned_at IS NULL');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_asset_capabilities_token_hash ON asset_capabilities(token_hash)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_asset_capabilities_asset_id ON asset_capabilities(asset_id)');
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
        'active_theme' => ['default', '當前配色主題'],
        'rss_token_enabled' => ['false', 'RSS Token 保護'],
        'rss_token' => ['', 'RSS Token'],
        'custom_tracking_code' => ['', '全站自訂追蹤碼（例如 Google Analytics / GTM 追蹤程式碼）'],
        'turnstile_enabled' => ['false', 'Cloudflare Turnstile 總開關'],
        'turnstile_site_key' => ['', 'Turnstile Site Key (公鑰)'],
        'turnstile_secret_key' => ['', 'Turnstile Secret Key (密鑰)'],
        'turnstile_protect_login' => ['true', 'Turnstile 保護後台登入/重設'],
        'turnstile_protect_upload' => ['false', 'Turnstile 保護公開網頁上傳'],
        'seal_max_duration_days' => ['30', 'Seal 最長等待天數'],
        'seal_retention_days' => ['30', 'Seal 解鎖後保留天數']
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

/**
 * 為沒有 share_token 的既有資料補上隨機 token
 */
function backfillShareTokens($pdo) {
    $rows = $pdo->query("SELECT id FROM images WHERE share_token IS NULL OR share_token = ''")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        return;
    }
    $stmt = $pdo->prepare("UPDATE images SET share_token = ? WHERE id = ?");
    foreach ($rows as $row) {
        $token = bin2hex(random_bytes(16)); // 32-char hex token
        $stmt->execute([$token, $row['id']]);
    }
}

/**
 * 為既有資產建立 capability hash。原始 token 不回溯公開，既有資產仍由 admin 管理。
 */
function backfillAssetCapabilities($pdo) {
    $marker = $pdo->prepare("SELECT value FROM configs WHERE `key` = 'asset_capabilities_backfilled'");
    $marker->execute();
    if ($marker->fetchColumn() === '1') {
        return;
    }

    $rows = $pdo->query(
        'SELECT images.id FROM images
         LEFT JOIN asset_capabilities ON asset_capabilities.asset_id = images.id
         WHERE asset_capabilities.asset_id IS NULL'
    )->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($rows)) {
        $stmt = $pdo->prepare(
            'INSERT OR IGNORE INTO asset_capabilities (asset_id, token_hash, created_at) VALUES (?, ?, ?)'
        );
        foreach ($rows as $row) {
            $stmt->execute([(int)$row['id'], hash('sha256', bin2hex(random_bytes(32))), time()]);
        }
    }

    $markerStmt = $pdo->prepare(
        "INSERT INTO configs (`key`, value, description) VALUES ('asset_capabilities_backfilled', '1', 'Asset capability migration marker')
         ON CONFLICT(`key`) DO UPDATE SET value = '1'"
    );
    $markerStmt->execute();
}

function ensureCoreSchema($pdo) {
    createCoreTables($pdo);
    ensureSealIndexes($pdo);
    normalizeConfigsTable($pdo);
    ensureColumns($pdo, 'images', getCoreImageColumns());
    backfillAssetFlags($pdo);
    backfillShareTokens($pdo);
    backfillAssetCapabilities($pdo);
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
