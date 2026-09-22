(function () {
    'use strict';

    const csrfToken = () => document.querySelector('meta[name="seal-csrf-token"]')?.content || '';

    function setMessage(root, message, isError = false) {
        const element = root.querySelector('[data-seal-message]');
        if (!element) return;
        element.textContent = message || '';
        element.setAttribute('role', 'status');
        element.setAttribute('aria-live', 'polite');
        element.style.color = isError ? '#f7768e' : '#7f88b2';
    }

    function showPulseMessage(root, pulseUrl) {
        const element = root.querySelector('[data-seal-message]');
        if (!element) return;
        element.replaceChildren();
        element.setAttribute('role', 'status');
        element.setAttribute('aria-live', 'polite');
        const label = document.createElement('span');
        label.textContent = '私有 Pulse 連結（請立即複製保存）：';
        const row = document.createElement('div');
        row.className = 'seal-pulse-copy';
        const input = document.createElement('input');
        input.type = 'text';
        input.readOnly = true;
        input.value = pulseUrl;
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = '複製';
        button.addEventListener('click', async () => {
            await navigator.clipboard.writeText(pulseUrl);
            button.textContent = '已複製';
        });
        row.append(input, button);
        element.append(label, row);
    }

    function setModeVisibility(root) {
        const mode = root.querySelector('[data-seal-mode]')?.value;
        root.querySelector('[data-seal-timed]')?.toggleAttribute('hidden', mode !== 'timed');
        root.querySelector('[data-seal-dms]')?.toggleAttribute('hidden', mode !== 'dms');
        root.querySelector('[data-seal-ephemeral]')?.toggleAttribute('hidden', mode !== 'ephemeral');
    }

    function renderExisting(root, data) {
        const create = root.querySelector('[data-seal-create]');
        const existing = root.querySelector('[data-seal-existing]');
        const state = root.querySelector('[data-seal-state]');
        const link = root.querySelector('[data-seal-public-link]');
        if (!create || !existing || !state) return;

        create.hidden = Boolean(data.exists);
        existing.hidden = !data.exists;
        state.textContent = data.exists ? `Seal：${data.state}` : '未設定';
        if (data.exists && data.id) root.dataset.sealId = data.id;

        if (data.exists && link) {
            link.href = data.public_url;
            link.textContent = data.public_url;
        }
        if (data.exists) {
            const details = root.querySelector('[data-seal-details]');
            if (details) {
                const unlock = data.unlock_at ? new Date(data.unlock_at * 1000).toLocaleString() : '';
                details.textContent = data.mode === 'dms'
                    ? `${data.mode} · 下一次解鎖：${unlock}`
                    : `${data.mode} · 解鎖：${unlock}`;
            }
        }
    }

    async function loadStatus(root, assetId) {
        root.classList.add('is-loading');
        try {
            const response = await fetch(`/api.php?action=seal_admin_status&asset_id=${encodeURIComponent(assetId)}`);
            const body = await response.json();
            if (!response.ok || body.result !== 'success') throw new Error(body.message || 'Seal 狀態載入失敗');
            renderExisting(root, body.data);
        } catch (error) {
            setMessage(root, error.message, true);
        } finally {
            root.classList.remove('is-loading');
        }
    }

    async function createSeal(root, assetId) {
        const mode = root.querySelector('[data-seal-mode]')?.value;
        const payload = new URLSearchParams({ asset_id: assetId, mode, csrf_token: csrfToken() });
        if (mode === 'timed') {
            const value = root.querySelector('[data-seal-unlock-at]')?.value;
            payload.set('unlock_at', String(Math.floor(new Date(value).getTime() / 1000)));
        } else if (mode === 'dms') {
            payload.set('pulse_interval', String(Number(root.querySelector('[data-seal-pulse]')?.value || 0) * 60));
        } else {
            payload.set('max_views', root.querySelector('[data-seal-max-views]')?.value || '1');
        }

        const button = root.querySelector('[data-seal-create-button]');
        if (button) {
            button.disabled = true;
            button.textContent = '建立中…';
        }
        try {
            const response = await fetch('/api.php?action=seal_create', { method: 'POST', body: payload });
            const body = await response.json();
            if (!response.ok || body.result !== 'success') throw new Error(body.message || 'Seal 建立失敗');
            const data = body.data;
            if (data.pulse_url) showPulseMessage(root, data.pulse_url);
            else setMessage(root, '已建立 Seal。');
            renderExisting(root, { ...data, exists: true, state: data.status, public_url: data.public_url });
            root.querySelector('[data-seal-existing]')?.setAttribute('data-pulse-url', data.pulse_url || '');
        } catch (error) {
            setMessage(root, error.message, true);
        } finally {
            if (button) {
                button.disabled = false;
                button.textContent = '建立 Seal';
            }
        }
    }

    async function revokeSeal(root, assetId) {
        const sealId = root.dataset.sealId;
        if (!sealId || !window.confirm('解除 Seal 後，資產會恢復公開。確定繼續？')) return;
        const payload = new URLSearchParams({ seal_id: sealId, csrf_token: csrfToken() });
        try {
            const response = await fetch('/api.php?action=seal_revoke', { method: 'POST', body: payload });
            const body = await response.json();
            if (!response.ok || body.result !== 'success') throw new Error(body.message || '解除失敗');
            setMessage(root, 'Seal 已解除，資產保留。');
            delete root.dataset.sealId;
            renderExisting(root, { exists: false });
        } catch (error) {
            setMessage(root, error.message, true);
        }
    }

    function attach(root, assetId) {
        if (!root || !assetId) return;
        root.dataset.assetId = assetId;
        if (root.dataset.sealAttached !== '1') {
            root.querySelector('[data-seal-mode]')?.addEventListener('change', () => setModeVisibility(root));
            root.querySelector('[data-seal-create-button]')?.addEventListener('click', () => createSeal(root, root.dataset.assetId));
            root.querySelector('[data-seal-revoke-button]')?.addEventListener('click', () => revokeSeal(root, root.dataset.assetId));
            root.dataset.sealAttached = '1';
        }
        const unlockInput = root.querySelector('[data-seal-unlock-at]');
        if (unlockInput && !unlockInput.value) {
            const defaultUnlock = new Date(Date.now() + 24 * 60 * 60 * 1000);
            const localValue = new Date(defaultUnlock.getTime() - defaultUnlock.getTimezoneOffset() * 60000)
                .toISOString().slice(0, 16);
            unlockInput.min = new Date(Date.now() + 60 * 1000 - new Date().getTimezoneOffset() * 60000)
                .toISOString().slice(0, 16);
            unlockInput.value = localValue;
        }
        setModeVisibility(root);
        loadStatus(root, assetId);
    }

    function openStandalone(assetId, label) {
        const modal = document.createElement('div');
        modal.className = 'seal-standalone-modal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-label', `資產 Seal · ${label || `#${assetId}`}`);
        modal.innerHTML = `<div class="seal-standalone-dialog"><div class="seal-standalone-title"></div>${window.SealControls.template()}<button type="button" data-seal-close>關閉</button></div>`;
        document.body.appendChild(modal);
        modal.querySelector('.seal-standalone-title').textContent = `資產 Seal · ${label || `#${assetId}`}`;
        attach(modal.querySelector('[data-seal-control]'), assetId);
        modal.querySelector('[data-seal-close]').addEventListener('click', () => modal.remove());
        modal.addEventListener('click', event => { if (event.target === modal) modal.remove(); });
    }

    function template() {
        return `<section class="seal-control" data-seal-control>
            <div class="seal-control-head"><span class="seal-control-title">Seal 存取控制</span><span class="seal-control-state" data-seal-state>載入中…</span></div>
            <p class="seal-control-help">Seal 與密碼同屬資產存取控制：密碼保護內容，Seal 控制何時或如何釋出。Seal 建立或解除會立即生效。</p>
            <div data-seal-create>
                <div class="seal-control-grid">
                    <label>模式<select data-seal-mode><option value="timed">定時解鎖</option><option value="dms">Dead Man’s Switch</option><option value="ephemeral">閱後即焚</option></select></label>
                    <label data-seal-timed>解鎖時間<input type="datetime-local" data-seal-unlock-at></label>
                    <label data-seal-dms hidden>Pulse 間隔（分鐘）<input type="number" min="5" max="43200" value="10080" data-seal-pulse></label>
                    <label data-seal-ephemeral hidden>最大瀏覽次數<input type="number" min="1" max="100" value="1" data-seal-max-views></label>
                </div>
                <div class="seal-control-actions"><button type="button" class="seal-primary" data-seal-create-button>建立 Seal</button></div>
            </div>
            <div class="seal-control-existing" data-seal-existing hidden>
                <p class="seal-control-details" data-seal-details></p>
                <div class="seal-control-actions"><a data-seal-public-link target="_blank" rel="noopener">開啟 Seal 頁面</a><button type="button" class="seal-danger" data-seal-revoke-button>解除 Seal</button></div>
            </div>
            <p class="seal-control-message" data-seal-message role="status" aria-live="polite"></p>
        </section>`;
    }

    window.SealControls = { attach, openStandalone, template };
})();
