<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (empty($_SESSION['loggedin'])) {
    require 'login.php';
    exit;
}

require_once '../config/database.php';
require_once '../config/seal.php';

$db = Database::getInstance();
$pdo = $db->getConnection();
$config = Database::getConfig($pdo);
cleanupExpiredSeals($pdo);

$assets = $pdo->query(
    'SELECT id, title, path, mime_type FROM images ORDER BY id DESC LIMIT 200'
)->fetchAll(PDO::FETCH_ASSOC);
$seals = $pdo->query(
    'SELECT seals.*, images.title, images.path
     FROM seals JOIN images ON images.id = seals.asset_id
     WHERE seals.burned_at IS NULL ORDER BY seals.id DESC'
)->fetchAll(PDO::FETCH_ASSOC);
?><!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Seal 管理 - 888 BOX</title>
    <link rel="stylesheet" href="/static/css/admin.css?v=<?= time() ?>">
    <style>
        .seal-page { max-width: 1120px; margin: 0 auto; padding: 32px 20px 80px; }
        .seal-card { margin: 18px 0; padding: 22px; border: 1px solid rgba(255,255,255,.12); border-radius: 16px; background: rgba(255,255,255,.04); }
        .seal-form { display: grid; gap: 14px; max-width: 680px; }
        .seal-form label { display: grid; gap: 6px; }
        .seal-form input, .seal-form select, .seal-form button { padding: 11px 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,.16); font: inherit; }
        .seal-form button { cursor: pointer; color: #08121d; background: #8bd5ff; border: 0; font-weight: 700; }
        .seal-row { display: grid; gap: 8px; padding: 14px 0; border-top: 1px solid rgba(255,255,255,.1); }
        .seal-row:first-child { border-top: 0; }
        .seal-link { color: #8bd5ff; word-break: break-all; }
        .muted { color: #8995b4; }
        .hidden { display: none; }
    </style>
</head>
<body>
<main class="seal-page">
    <p><a href="/admin/index.php">← 返回圖片管理</a></p>
    <h1>Seal 管理</h1>
    <p class="muted">第一階段 Seal 控制既有資產的對外存取時間與瀏覽次數。資料仍由 888 BOX 伺服器管理。</p>

    <section class="seal-card">
        <h2>建立 Seal</h2>
        <form id="seal-form" class="seal-form">
            <label>資產
                <select name="asset_id" required>
                    <option value="">請選擇資產</option>
                    <?php foreach ($assets as $asset): ?>
                        <option value="<?= (int)$asset['id'] ?>">
                            #<?= (int)$asset['id'] ?> — <?= htmlspecialchars($asset['title'] ?: basename($asset['path'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>模式
                <select name="mode" id="seal-mode" required>
                    <option value="timed">定時解鎖</option>
                    <option value="dms">Dead Man’s Switch</option>
                    <option value="ephemeral">閱後即焚</option>
                </select>
            </label>
            <label id="unlock-field">解鎖時間
                <input type="datetime-local" name="unlock_at">
            </label>
            <label id="pulse-field" class="hidden">Pulse 間隔（分鐘）
                <input type="number" name="pulse_interval_minutes" min="5" max="43200" value="10080">
            </label>
            <label id="views-field" class="hidden">最大瀏覽次數
                <input type="number" name="max_views" min="1" max="100" value="1">
            </label>
            <button type="submit">建立 Seal</button>
        </form>
        <p id="form-result" class="muted"></p>
    </section>

    <section class="seal-card">
        <h2>使用中的 Seal</h2>
        <div id="seal-list">
            <?php if (!$seals): ?>
                <p class="muted">目前沒有使用中的 Seal。</p>
            <?php else: ?>
                <?php foreach ($seals as $seal): ?>
                    <div class="seal-row">
                        <strong>#<?= (int)$seal['asset_id'] ?> — <?= htmlspecialchars($seal['title'] ?: basename($seal['path'])) ?></strong>
                        <span class="muted">模式：<?= htmlspecialchars($seal['mode']) ?>／狀態：<?= htmlspecialchars(getSealState($seal)) ?></span>
                        <span class="muted">解鎖時間：<?= htmlspecialchars(date('Y-m-d H:i:s', (int)$seal['unlock_at'])) ?></span>
                        <a class="seal-link" href="<?= htmlspecialchars(buildSealUrl($seal['seal_token'], $config)) ?>" target="_blank" rel="noopener">開啟公開 Seal 頁面</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>
<script>
    const mode = document.getElementById('seal-mode');
    const unlockField = document.getElementById('unlock-field');
    const pulseField = document.getElementById('pulse-field');
    const viewsField = document.getElementById('views-field');
    const updateFields = () => {
        unlockField.classList.toggle('hidden', mode.value !== 'timed');
        pulseField.classList.toggle('hidden', mode.value !== 'dms');
        viewsField.classList.toggle('hidden', mode.value !== 'ephemeral');
    };
    mode.addEventListener('change', updateFields);
    updateFields();

    document.getElementById('seal-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        const payload = new URLSearchParams();
        payload.set('asset_id', form.get('asset_id'));
        payload.set('mode', form.get('mode'));
        if (form.get('mode') === 'timed') {
            payload.set('unlock_at', String(Math.floor(new Date(form.get('unlock_at')).getTime() / 1000)));
        } else if (form.get('mode') === 'dms') {
            payload.set('pulse_interval', String(Number(form.get('pulse_interval_minutes')) * 60));
        } else {
            payload.set('max_views', form.get('max_views'));
        }

        const result = document.getElementById('form-result');
        try {
            const response = await fetch('/api.php?action=seal_create', { method: 'POST', body: payload });
            const body = await response.json();
            if (!response.ok || body.result !== 'success') throw new Error(body.message || '建立失敗');
            const data = body.data;
            result.innerHTML = `公開連結：<a class="seal-link" href="${data.public_url}" target="_blank" rel="noopener">${data.public_url}</a>`
                + (data.pulse_url ? `<br>私有 Pulse 連結：<code>${data.pulse_url}</code>` : '')
                + '<br>請先保存私有連結，再重新整理列表。';
            event.currentTarget.reset();
            updateFields();
        } catch (error) {
            result.textContent = error.message;
        }
    });

    // Pulse and Burn are intentionally handled by /seal.php?pulse=... so the private token stays out of the list page.
    const phaseOneActions = ['seal_pulse', 'seal_burn'];
    void phaseOneActions;
</script>
</body>
</html>
