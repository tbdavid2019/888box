(function () {
    'use strict';

    const API_URL = '/api.php';

    function button(label, className) {
        const el = document.createElement('button');
        el.type = 'button';
        el.className = className;
        el.textContent = label;
        return el;
    }

    function field(labelText, input) {
        const wrapper = document.createElement('div');
        wrapper.className = 'seal-user-field';
        const label = document.createElement('label');
        label.textContent = labelText;
        wrapper.append(label, input);
        return wrapper;
    }

    function input(type, value) {
        const el = document.createElement('input');
        el.type = type;
        if (value !== undefined) el.value = value;
        return el;
    }

    function localDateTime(minutes) {
        const date = new Date(Date.now() + minutes * 60 * 1000);
        const pad = (value) => String(value).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
    }

    function addCopyableResult(parent, labelText, value) {
        if (!value) return;
        const row = document.createElement('div');
        row.className = 'seal-user-result';
        const label = document.createElement('strong');
        label.textContent = labelText;
        const valueInput = input('text', value);
        valueInput.readOnly = true;
        const copy = button('複製', 'seal-user-secondary seal-user-copy');
        copy.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(value);
                copy.textContent = '已複製';
            } catch (error) {
                valueInput.select();
                copy.textContent = '請手動複製';
            }
        });
        row.append(label, valueInput, copy);
        parent.appendChild(row);
    }

    function open(assetId, manageToken, labelText) {
        if (!assetId || !manageToken) return;
        document.querySelector('.seal-user-backdrop')?.remove();

        const backdrop = document.createElement('div');
        backdrop.className = 'seal-user-backdrop';
        const dialog = document.createElement('section');
        dialog.className = 'seal-user-dialog';
        dialog.setAttribute('role', 'dialog');
        dialog.setAttribute('aria-modal', 'true');

        const title = document.createElement('h2');
        title.textContent = '設定 Seal';
        const description = document.createElement('p');
        description.textContent = `資產：${labelText || `#${assetId}`}。這組管理連結只授權此資產。`;
        const status = document.createElement('div');
        status.className = 'seal-user-status';
        status.setAttribute('role', 'status');
        const mode = document.createElement('select');
        [['timed', '定時解鎖'], ['dms', 'DMS Pulse'], ['ephemeral', '閱後即焚']].forEach(([value, text]) => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = text;
            mode.appendChild(option);
        });
        const unlockAt = input('datetime-local', localDateTime(60));
        unlockAt.min = localDateTime(1);
        const pulseInterval = input('number', '60');
        pulseInterval.min = '5';
        pulseInterval.max = '43200';
        const maxViews = input('number', '1');
        maxViews.min = '1';
        maxViews.max = '100';
        const dynamicFields = document.createElement('div');
        const result = document.createElement('div');
        const create = button('建立 Seal', 'seal-user-primary');
        const close = button('關閉', 'seal-user-secondary');
        const revoke = button('解除 Seal', 'seal-user-danger');
        const actions = document.createElement('div');
        actions.className = 'seal-user-actions';
        actions.append(create, revoke, close);
        dialog.append(title, description, status, field('模式', mode), dynamicFields, result, actions);
        backdrop.appendChild(dialog);
        document.body.appendChild(backdrop);

        let currentSeal = null;
        const setStatus = (message, error = false) => {
            status.textContent = message;
            status.style.color = error ? '#f7768e' : '';
        };
        const renderFields = () => {
            dynamicFields.replaceChildren();
            if (mode.value === 'timed') dynamicFields.appendChild(field('解鎖時間', unlockAt));
            if (mode.value === 'dms') dynamicFields.appendChild(field('Pulse 間隔（分鐘）', pulseInterval));
            if (mode.value === 'ephemeral') dynamicFields.appendChild(field('最大瀏覽次數', maxViews));
        };
        renderFields();
        mode.addEventListener('change', renderFields);
        close.addEventListener('click', () => backdrop.remove());
        backdrop.addEventListener('click', (event) => {
            if (event.target === backdrop) backdrop.remove();
        });

        const request = async (action, options = {}) => {
            const response = await fetch(`${API_URL}?action=${action}`, options);
            const payload = await response.json();
            if (!response.ok || payload.result !== 'success') throw new Error(payload.message || 'Seal 請求失敗');
            return payload.data;
        };

        const load = async () => {
            try {
                const data = await request('seal_capability_status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ asset_id: assetId, manage_token: manageToken })
                });
                currentSeal = data.exists ? data : null;
                setStatus(currentSeal ? `目前狀態：${currentSeal.state}` : '目前沒有使用中的 Seal。');
                create.disabled = Boolean(currentSeal);
                revoke.disabled = !currentSeal;
            } catch (error) {
                setStatus(error.message, true);
            }
        };

        create.addEventListener('click', async () => {
            create.disabled = true;
            result.replaceChildren();
            const params = new URLSearchParams({ asset_id: assetId, manage_token: manageToken, mode: mode.value });
            if (mode.value === 'timed') {
                const epochSeconds = Math.floor(new Date(unlockAt.value).getTime() / 1000);
                params.set('unlock_at', Number.isFinite(epochSeconds) && epochSeconds > 0 ? String(epochSeconds) : '0');
            }
            if (mode.value === 'dms') params.set('pulse_interval', String(Number(pulseInterval.value) * 60));
            if (mode.value === 'ephemeral') params.set('max_views', maxViews.value);
            try {
                const data = await request('seal_create', { method: 'POST', body: params });
                currentSeal = data;
                setStatus('Seal 已建立。請保存下方連結。');
                addCopyableResult(result, 'Seal 連結', data.public_url);
                addCopyableResult(result, 'Pulse 連結（僅 DMS）', data.pulse_url);
                revoke.disabled = false;
            } catch (error) {
                setStatus(error.message, true);
                create.disabled = false;
            }
        });

        revoke.addEventListener('click', async () => {
            if (!currentSeal || !window.confirm('解除後資產會恢復公開存取，確定繼續？')) return;
            revoke.disabled = true;
            try {
                await request('seal_revoke', {
                    method: 'POST',
                    body: new URLSearchParams({
                        seal_id: currentSeal.id,
                        asset_id: assetId,
                        manage_token: manageToken
                    })
                });
                currentSeal = null;
                result.replaceChildren();
                setStatus('Seal 已解除。');
                create.disabled = false;
            } catch (error) {
                setStatus(error.message, true);
                revoke.disabled = false;
            }
        });

        load();
    }

    function addButton(container, data, labelText) {
        if (!container || !data?.id || !data?.manage_token || container.querySelector('.seal-user-trigger')) return;
        const trigger = button('設定 Seal', 'seal-user-trigger');
        trigger.addEventListener('click', () => open(data.id, data.manage_token, labelText));
        container.appendChild(trigger);
    }

    window.SealUserControls = { open, addButton };
})();
