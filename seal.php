<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/seal.php';
require_once __DIR__ . '/config/upload.php';

$db = Database::getInstance();
$pdo = $db->getConnection();
$config = Database::getConfig($pdo);
cleanupExpiredSeals($pdo);

$pulseToken = trim((string)($_GET['pulse'] ?? ''));
$publicToken = trim((string)($_GET['token'] ?? ''));
$pulseSeal = $pulseToken !== '' ? findSealByPulseToken($pdo, $pulseToken) : null;
$publicSeal = $publicToken !== '' ? findSealByToken($pdo, $publicToken) : null;
$seal = $pulseSeal ?: $publicSeal;

if (!$seal) {
    http_response_code(404);
    exit('Seal 不存在或已清理');
}

$state = getSealState($seal);
$assetStmt = $pdo->prepare('SELECT id, title, share_token FROM images WHERE id = ? LIMIT 1');
$assetStmt->execute([(int)$seal['asset_id']]);
$asset = $assetStmt->fetch(PDO::FETCH_ASSOC);

if (!$asset) {
    http_response_code(404);
    exit('Seal 對應的資產不存在');
}

if ($state === 'burned') {
    http_response_code(410);
    exit('此 Seal 已銷毀');
}

if ($publicSeal && $state === 'unlocked') {
    header('Location: ' . buildAssetShareUrl($asset, $config), true, 302);
    exit;
}

if ($pulseSeal) {
    $pageTitle = '管理 Seal';
} else {
    $pageTitle = $state === 'locked' ? 'Seal 尚未解鎖' : 'Seal 已達使用上限';
}

$unlockAt = (int)$seal['unlock_at'];
$modeLabels = [
    SEAL_MODE_TIMED => '定時解鎖',
    SEAL_MODE_DMS => 'Dead Man’s Switch',
    SEAL_MODE_EPHEMERAL => '閱後即焚'
];
?><!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> - 888 BOX</title>
    <style>
        :root { color-scheme: dark; font-family: system-ui, sans-serif; background: #0d111b; color: #f4f7ff; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; box-sizing: border-box; }
        main { width: min(100%, 560px); padding: 32px; border: 1px solid rgba(255,255,255,.14); border-radius: 20px; background: rgba(255,255,255,.06); box-shadow: 0 24px 80px rgba(0,0,0,.3); }
        h1 { margin: 0 0 12px; font-size: clamp(1.6rem, 4vw, 2.3rem); }
        p { color: #aeb9d6; line-height: 1.6; }
        code { color: #8bd5ff; word-break: break-all; }
        button, input { width: 100%; box-sizing: border-box; border: 0; border-radius: 10px; padding: 12px 14px; font: inherit; }
        button { cursor: pointer; color: #07111d; background: #8bd5ff; font-weight: 700; }
        button.danger { color: #fff; background: #be4b68; }
        input { margin: 8px 0 12px; color: #fff; background: rgba(0,0,0,.25); border: 1px solid rgba(255,255,255,.16); }
        .status { margin: 24px 0; padding: 18px; border-radius: 14px; background: rgba(0,0,0,.2); }
        .muted { color: #8490ae; font-size: .92rem; }
        .actions { display: grid; gap: 12px; margin-top: 18px; }
        a { color: #8bd5ff; }
    </style>
</head>
<body>
<main>
    <p class="muted">888 BOX / Seal</p>
    <h1><?= htmlspecialchars($pageTitle) ?></h1>
    <p>資產：<?= htmlspecialchars($asset['title'] ?: ('#' . $asset['id'])) ?></p>
    <div class="status">
        <p>模式：<?= htmlspecialchars($modeLabels[$seal['mode']] ?? $seal['mode']) ?></p>
        <?php if ($state === 'locked'): ?>
            <p>解鎖時間：<?= htmlspecialchars(date('Y-m-d H:i:s', $unlockAt)) ?></p>
            <p id="countdown" data-unlock-at="<?= $unlockAt ?>">正在計算倒數…</p>
        <?php elseif ($state === 'exhausted'): ?>
            <p>此 Seal 已達瀏覽上限，內容停止提供。</p>
        <?php elseif ($pulseSeal): ?>
            <p>目前狀態：可管理</p>
            <p>下次解鎖時間：<?= htmlspecialchars(date('Y-m-d H:i:s', $unlockAt)) ?></p>
        <?php endif; ?>
    </div>

    <?php if ($pulseSeal && $seal['mode'] === SEAL_MODE_DMS && $state === 'locked'): ?>
        <label for="new-interval">新的 Pulse 間隔（分鐘）</label>
        <input id="new-interval" type="number" min="5" max="43200" value="<?= max(5, (int)$seal['pulse_interval'] / 60) ?>">
        <div class="actions">
            <button type="button" id="pulse-button">Pulse，延長鎖定</button>
            <button type="button" class="danger" id="burn-button">Burn，銷毀 Seal</button>
        </div>
        <p id="action-result" class="muted"></p>
    <?php endif; ?>

    <p class="muted"><a href="/">返回 888 BOX</a></p>
</main>
<script>
    const countdown = document.getElementById('countdown');
    if (countdown) {
        const unlockAt = Number(countdown.dataset.unlockAt) * 1000;
        const update = () => {
            const seconds = Math.max(0, Math.ceil((unlockAt - Date.now()) / 1000));
            const days = Math.floor(seconds / 86400);
            const hours = Math.floor((seconds % 86400) / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const remainingSeconds = seconds % 60;
            countdown.textContent = `${days} 天 ${hours} 小時 ${minutes} 分 ${remainingSeconds} 秒`;
            if (seconds === 0) window.location.reload();
        };
        update();
        window.setInterval(update, 1000);
    }

    const pulseToken = <?= json_encode($pulseToken) ?>;
    const result = document.getElementById('action-result');
    async function callSealAction(action, fields) {
        const body = new URLSearchParams({ pulse_token: pulseToken, ...fields });
        const response = await fetch(`/api.php?action=${action}`, { method: 'POST', body });
        const payload = await response.json();
        if (!response.ok || payload.result !== 'success') {
            throw new Error(payload.message || '操作失敗');
        }
        return payload;
    }

    document.getElementById('pulse-button')?.addEventListener('click', async () => {
        try {
            const interval = Math.max(5, Number(document.getElementById('new-interval').value || 5));
            await callSealAction('seal_pulse', { new_interval: String(interval * 60) });
            result.textContent = 'Pulse 已更新，頁面即將重新整理。';
            window.setTimeout(() => window.location.reload(), 700);
        } catch (error) {
            result.textContent = error.message;
        }
    });

    document.getElementById('burn-button')?.addEventListener('click', async () => {
        if (!window.confirm('確定要永久銷毀此 Seal？')) return;
        try {
            await callSealAction('seal_burn', {});
            result.textContent = 'Seal 已銷毀。';
            window.setTimeout(() => window.location.reload(), 700);
        } catch (error) {
            result.textContent = error.message;
        }
    });
</script>
</body>
</html>
