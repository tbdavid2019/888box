<?php

const SEAL_MODE_TIMED = 'timed';
const SEAL_MODE_DMS = 'dms';
const SEAL_MODE_EPHEMERAL = 'ephemeral';
const SEAL_MIN_PULSE_INTERVAL = 5 * 60;
const SEAL_MAX_PULSE_INTERVAL = 30 * 24 * 60 * 60;
const SEAL_MIN_UNLOCK_DELAY = 60;
const SEAL_DEFAULT_RETENTION_DAYS = 30;

function ensureSealCsrfToken() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['seal_csrf_token'])) {
        $_SESSION['seal_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['seal_csrf_token'];
}

function getSealModes() {
    return [SEAL_MODE_TIMED, SEAL_MODE_DMS, SEAL_MODE_EPHEMERAL];
}

function generateSealToken() {
    return bin2hex(random_bytes(16));
}

function generateSealPulseToken() {
    return bin2hex(random_bytes(32));
}

function generateAssetManageToken() {
    return bin2hex(random_bytes(32));
}

function hashAssetManageToken($token) {
    return hash('sha256', (string)$token);
}

function issueAssetManageToken($pdo, $assetId) {
    $token = generateAssetManageToken();
    $stmt = $pdo->prepare(
        'INSERT INTO asset_capabilities (asset_id, token_hash, created_at) VALUES (?, ?, ?)
         ON CONFLICT(asset_id) DO UPDATE SET token_hash = excluded.token_hash, created_at = excluded.created_at'
    );
    $stmt->execute([(int)$assetId, hashAssetManageToken($token), time()]);
    return $token;
}

function findAssetByManageToken($pdo, $assetId, $token) {
    if ((int)$assetId <= 0 || !preg_match('/^[a-fA-F0-9]{64}$/', (string)$token)) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT images.* FROM images
         INNER JOIN asset_capabilities ON asset_capabilities.asset_id = images.id
         WHERE images.id = ? AND asset_capabilities.token_hash = ? LIMIT 1'
    );
    $stmt->execute([(int)$assetId, hashAssetManageToken($token)]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function hashSealToken($token) {
    return hash('sha256', (string)$token);
}

function getSealConfigInt($config, $key, $default) {
    $value = $config[$key] ?? $default;
    return is_numeric($value) ? (int)$value : $default;
}

function getSealRetentionSeconds($config) {
    $days = max(1, getSealConfigInt($config, 'seal_retention_days', SEAL_DEFAULT_RETENTION_DAYS));
    return $days * 24 * 60 * 60;
}

function parseSealTimestamp($value) {
    if (is_numeric($value)) {
        return (int)$value;
    }

    $timestamp = strtotime((string)$value);
    return $timestamp === false ? 0 : $timestamp;
}

function createSealRecord($pdo, $asset, $mode, $options, $config, $now = null) {
    $now = $now ?? time();
    $mode = strtolower(trim((string)$mode));
    $maxDurationDays = max(1, getSealConfigInt($config, 'seal_max_duration_days', 30));
    if (!validateSealMode($mode)) {
        throw new InvalidArgumentException('Seal 模式無效');
    }

    if (getActiveSealForAsset($pdo, (int)$asset['id'])) {
        throw new RuntimeException('此資產已有使用中的 Seal');
    }

    $pulseInterval = null;
    $maxViews = null;
    if ($mode === SEAL_MODE_TIMED) {
        $unlockAt = parseSealTimestamp($options['unlock_at'] ?? 0);
        $maxUnlockAt = $now + $maxDurationDays * 24 * 60 * 60;
        if ($unlockAt < $now + SEAL_MIN_UNLOCK_DELAY || $unlockAt > $maxUnlockAt) {
            throw new InvalidArgumentException("解鎖時間必須介於 {$maxDurationDays} 天內，且至少提前 1 分鐘");
        }
    } elseif ($mode === SEAL_MODE_DMS) {
        $pulseInterval = (int)($options['pulse_interval'] ?? 0);
        if ($pulseInterval < SEAL_MIN_PULSE_INTERVAL || $pulseInterval > SEAL_MAX_PULSE_INTERVAL) {
            throw new InvalidArgumentException('Pulse 間隔必須介於 5 分鐘與 30 天');
        }
        $unlockAt = $now + $pulseInterval;
    } else {
        $maxViews = (int)($options['max_views'] ?? 1);
        if ($maxViews < 1 || $maxViews > 100) {
            throw new InvalidArgumentException('最大瀏覽次數必須介於 1 與 100');
        }
        $unlockAt = $now;
        if ((int)($asset['is_video'] ?? 0) === 1 || (int)($asset['is_audio'] ?? 0) === 1) {
            throw new InvalidArgumentException('閱後即焚目前只支援圖片與文件，影片及音訊請使用定時或 DMS Seal');
        }
    }

    $sealToken = generateSealToken();
    $pulseToken = $mode === SEAL_MODE_DMS ? generateSealPulseToken() : null;
    $retentionAt = $mode === SEAL_MODE_EPHEMERAL
        ? null
        : $unlockAt + getSealRetentionSeconds($config);

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO seals
             (asset_id, seal_token, mode, unlock_at, pulse_interval, last_pulse_at,
              pulse_token_hash, max_views, view_count, created_at, updated_at, cleanup_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)'
        );
        $stmt->execute([
            (int)$asset['id'],
            $sealToken,
            $mode,
            $unlockAt,
            $pulseInterval,
            $mode === SEAL_MODE_DMS ? $now : null,
            $pulseToken ? hashSealToken($pulseToken) : null,
            $maxViews,
            $now,
            $now,
            $retentionAt
        ]);
    } catch (PDOException $e) {
        throw new RuntimeException('Seal 建立失敗，資產可能已有使用中的 Seal');
    }

    $state = $mode === SEAL_MODE_EPHEMERAL ? 'unlocked' : 'locked';
    $response = [
        'id' => (int)$pdo->lastInsertId(),
        'asset_id' => (int)$asset['id'],
        'mode' => $mode,
        'public_url' => buildSealUrl($sealToken, $config),
        'unlock_at' => $unlockAt,
        'status' => $state,
        'state' => $state
    ];
    if ($pulseToken) {
        $response['pulse_url'] = buildSealPulseUrl($pulseToken, $config);
        $response['pulse_token'] = $pulseToken;
    }
    return $response;
}

function validateSealMode($mode) {
    return in_array($mode, getSealModes(), true);
}

function getSealState($seal, $now = null) {
    $now = $now ?? time();

    if (!empty($seal['burned_at'])) {
        return 'burned';
    }

    if ($seal['mode'] === SEAL_MODE_EPHEMERAL
        && $seal['max_views'] !== null
        && (int)$seal['view_count'] >= (int)$seal['max_views']) {
        return 'exhausted';
    }

    return $now < (int)$seal['unlock_at'] ? 'locked' : 'unlocked';
}

function getActiveSealForAsset($pdo, $assetId) {
    $stmt = $pdo->prepare(
        'SELECT * FROM seals WHERE asset_id = ? AND burned_at IS NULL ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute([(int)$assetId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function getActiveSealMap($pdo) {
    $rows = $pdo->query('SELECT * FROM seals WHERE burned_at IS NULL ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
    $map = [];
    foreach ($rows as $row) {
        $assetId = (int)$row['asset_id'];
        if (!isset($map[$assetId])) {
            $map[$assetId] = $row;
        }
    }
    return $map;
}

function findSealByToken($pdo, $token) {
    if (!preg_match('/^[a-fA-F0-9]{32}$/', (string)$token)) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT * FROM seals WHERE seal_token = ? LIMIT 1');
    $stmt->execute([strtolower($token)]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function findSealByPulseToken($pdo, $pulseToken) {
    if (!preg_match('/^[a-fA-F0-9]{64}$/', (string)$pulseToken)) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT * FROM seals WHERE pulse_token_hash = ? AND burned_at IS NULL LIMIT 1'
    );
    $stmt->execute([hashSealToken($pulseToken)]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function buildSealUrl($token, $config = []) {
    $base = '';
    if (!empty($_SERVER['HTTP_HOST'])) {
        $forwardedProto = strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
        $scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https') ? 'https' : 'http';
        $base = $scheme . '://' . $_SERVER['HTTP_HOST'];
    } else {
        $base = trim((string)($config['site_domain'] ?? 'http://localhost'));
        if (!preg_match('/^https?:\/\//i', $base)) {
            $base = 'https://' . $base;
        }
    }

    return rtrim($base, '/') . '/seal/' . rawurlencode((string)$token);
}

function buildSealPulseUrl($pulseToken, $config = []) {
    $base = buildSealUrl('pulse', $config);
    return preg_replace('/\/seal\/pulse$/', '/seal.php', $base) . '?pulse=' . rawurlencode((string)$pulseToken);
}

function buildSealedAssetDeliveryUrl($asset) {
    return '/get_file.php?path=' . rawurlencode(ltrim((string)($asset['path'] ?? ''), '/'));
}

function recordSealView($pdo, $sealId, $now = null) {
    $now = $now ?? time();
    $stmt = $pdo->prepare(
        'UPDATE seals
         SET view_count = view_count + 1, updated_at = ?
         WHERE id = ?
           AND burned_at IS NULL
           AND (max_views IS NULL OR view_count < max_views)'
    );
    $stmt->execute([$now, (int)$sealId]);

    return $stmt->rowCount() === 1;
}

function getSealAccessDecision($pdo, $asset) {
    $seal = getActiveSealForAsset($pdo, (int)$asset['id']);
    if (!$seal) {
        return ['allowed' => true, 'seal' => null, 'state' => null];
    }

    $state = getSealState($seal);
    if ($state === 'locked') {
        return ['allowed' => false, 'seal' => $seal, 'state' => $state, 'code' => 423];
    }
    if ($state === 'exhausted') {
        return ['allowed' => false, 'seal' => $seal, 'state' => $state, 'code' => 410];
    }

    if ($seal['mode'] === SEAL_MODE_EPHEMERAL) {
        if (!recordSealView($pdo, $seal['id'])) {
            return ['allowed' => false, 'seal' => $seal, 'state' => 'exhausted', 'code' => 410];
        }
        $seal['view_count'] = (int)$seal['view_count'] + 1;
        $seal['should_delete'] = $seal['max_views'] !== null
            && $seal['view_count'] >= (int)$seal['max_views'];
    }

    return ['allowed' => true, 'seal' => $seal, 'state' => 'unlocked'];
}

function scheduleEphemeralAssetDeletion($pdo, $asset, $decision) {
    if (empty($decision['should_delete']) && empty($decision['seal']['should_delete'])) {
        return;
    }

    register_shutdown_function(static function () use ($pdo, $asset) {
        require_once __DIR__ . '/delete.php';
        deleteAsset($pdo, (int)$asset['id']);
    });
}

function cleanupExpiredSeals($pdo, $now = null) {
    $now = $now ?? time();
    $stmt = $pdo->prepare(
        "DELETE FROM seals
         WHERE cleanup_at IS NOT NULL
           AND cleanup_at <= ?
           AND mode != 'ephemeral'"
    );
    $stmt->execute([$now]);
    return $stmt->rowCount();
}

function getSealStatusPayload($seal, $now = null) {
    $now = $now ?? time();
    $state = getSealState($seal, $now);
    $payload = [
        'id' => (int)$seal['id'],
        'mode' => $seal['mode'],
        'state' => $state,
        'unlock_at' => (int)$seal['unlock_at'],
        'time_remaining' => max(0, (int)$seal['unlock_at'] - $now),
        'view_count' => (int)$seal['view_count'],
        'max_views' => $seal['max_views'] === null ? null : (int)$seal['max_views'],
    ];

    if ($seal['mode'] === SEAL_MODE_DMS) {
        $payload['pulse_interval'] = (int)$seal['pulse_interval'];
        $payload['last_pulse_at'] = (int)$seal['last_pulse_at'];
    }

    return $payload;
}
