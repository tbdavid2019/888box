document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('settings-modal');
    const settingsLinks = document.querySelectorAll('.settings-link');

    const generateRandomToken = (length) => 
        Array.from({length}, () => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'[Math.floor(Math.random() * 62)]).join('');

    const closeModal = (modalElement) => {
        if (!modalElement) return;
        modalElement.classList.replace('show', 'hide');
        setTimeout(() => {
            modalElement.style.display = 'none';
            modalElement.classList.remove('hide');
        }, 200);
    };

    const setupModalCloseHandlers = (modalElement, closeHandler) => {
        modalElement.onclick = (e) => {
            if (e.target.closest('.close-modal')) {
                e.preventDefault();
                closeHandler();
                return;
            }
            const dialog = modalElement.querySelector('.modal-content');
            if (dialog && !dialog.contains(e.target)) {
                closeHandler();
            }
        };
    };

    // 全域 Esc 鍵關閉模態框
    document.addEventListener('keydown', (e) => {
        if ((e.key === 'Escape' || e.key === 'Esc') && modal && modal.classList.contains('show')) {
            closeModal(modal);
        }
    });

    const handleAction = async (btn, action, onSuccess) => {
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = action === 'optimize_db' ? '最佳化中...' : '檢查中...';
        
        try {
            const formData = new FormData();
            formData.append('action', action);
            
            const response = await fetch('settings.php', {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                body: formData
            });
            
            const data = await response.json();
            onSuccess(data);
        } catch (error) {
            console.error('操作失敗:', error);
            UI.showNotification('操作失敗', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    };

    const initializeSettingsForm = () => {
        const settingsForm = document.getElementById('settings-form');
        const tokenInput = document.getElementById('token-input');
        const rssTokenInput = document.getElementById('rss-token-input');
        const rssVideoPreview = document.getElementById('rss-video-preview');
        const rssAudioPreview = document.getElementById('rss-audio-preview');

        const updateStorageSettings = () => {
            const selectedStorage = document.querySelector('input[name="storage"]:checked');
            if (!selectedStorage) return;
            
            document.querySelectorAll('[id$="-settings"]').forEach(panel => panel.style.display = 'none');
            document.getElementById(`${selectedStorage.value}-settings`)?.style.setProperty('display', 'block');
        };

        const updateRssPreviewUrls = () => {
            const rssEnabled = document.querySelector('input[name="rss_token_enabled"]:checked')?.value === 'true';
            const rssToken = rssTokenInput?.value || '';

            [
                {input: rssVideoPreview, publicUrl: rssVideoPreview?.dataset.publicUrl || ''},
                {input: rssAudioPreview, publicUrl: rssAudioPreview?.dataset.publicUrl || ''}
            ].forEach(({input, publicUrl}) => {
                if (!input) return;
                input.value = rssEnabled && rssToken
                    ? `${publicUrl}?rss_token=${encodeURIComponent(rssToken)}`
                    : publicUrl;
            });
        };

        document.querySelector('.copy-token')?.addEventListener('click', () => {
            if (tokenInput.value) {
                navigator.clipboard.writeText(tokenInput.value).then(() => UI.showNotification('Token 已複製', 'success'));
            }
        });

        document.querySelector('.refresh-token')?.addEventListener('click', () => {
            tokenInput.value = generateRandomToken(32);
            UI.showNotification('Token 已重新產生', 'success');
        });

        document.querySelector('.copy-rss-token')?.addEventListener('click', () => {
            if (rssTokenInput?.value) {
                navigator.clipboard.writeText(rssTokenInput.value).then(() => UI.showNotification('RSS Token 已複製', 'success'));
            }
        });

        document.querySelector('.refresh-rss-token')?.addEventListener('click', () => {
            if (!rssTokenInput) return;
            rssTokenInput.value = generateRandomToken(48);
            updateRssPreviewUrls();
            UI.showNotification('RSS Token 已重新產生', 'success');
        });

        // 密碼顯示/隱藏
        document.querySelectorAll('.toggle-password').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = btn.previousElementSibling;
                if (input.type === 'password') {
                    input.type = 'text';
                    btn.innerHTML = '<i data-lucide="eye-off" class="icon"></i>';
                } else {
                    input.type = 'password';
                    btn.innerHTML = '<i data-lucide="eye" class="icon"></i>';
                }
                if (window.lucide) lucide.createIcons();
            });
        });

        updateStorageSettings();
        updateRssPreviewUrls();
        document.querySelectorAll('input[name="storage"]').forEach(input => 
            input.addEventListener('change', updateStorageSettings)
        );
        document.querySelectorAll('input[name="rss_token_enabled"]').forEach(input =>
            input.addEventListener('change', updateRssPreviewUrls)
        );

        // Sheet Tabs 切換
        const tabBtns = modal.querySelectorAll('.settings-tab-btn');
        const tabPanels = modal.querySelectorAll('.settings-tab-panel');
        tabBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const targetTab = btn.dataset.tab;
                tabBtns.forEach(b => {
                    const active = (b === btn);
                    b.classList.toggle('is-active', active);
                    b.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                tabPanels.forEach(panel => {
                    const active = (panel.id === `panel-${targetTab}`);
                    panel.classList.toggle('is-active', active);
                });
                if (window.lucide) lucide.createIcons();
            });
        });

        // 支援 Cmd+S / Ctrl+S 快速儲存
        const handleSettingsShortcut = (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 's') {
                e.preventDefault();
                settingsForm?.requestSubmit();
            }
        };
        modal.removeEventListener('keydown', handleSettingsShortcut);
        modal.addEventListener('keydown', handleSettingsShortcut);

        settingsForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            const maxFileSize = formData.get('max_file_size');
            if (maxFileSize) formData.set('max_file_size', Math.floor(maxFileSize * 1024 * 1024));
            
            const saveBtns = modal.querySelectorAll('.settings-save-btn');
            saveBtns.forEach(b => {
                b.disabled = true;
                b.dataset.origHtml = b.innerHTML;
                b.innerHTML = '<i data-lucide="loader-2" class="icon spin"></i> <span>儲存中...</span>';
            });
            if (window.lucide) lucide.createIcons();

            try {
                const response = await fetch('settings.php', {
                    method: 'POST',
                    headers: {'X-Requested-With': 'XMLHttpRequest'},
                    body: formData
                });
                
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                
                const {message, success} = await response.json();
                UI.showNotification(message, success ? 'success' : 'error');
                if (success) updateRssPreviewUrls();
            } catch (error) {
                console.error('Error saving settings:', error);
                UI.showNotification('儲存設定失敗', 'error');
            } finally {
                saveBtns.forEach(b => {
                    b.disabled = false;
                    if (b.dataset.origHtml) b.innerHTML = b.dataset.origHtml;
                });
                if (window.lucide) lucide.createIcons();
            }
        });

        document.getElementById('optimize-db-btn')?.addEventListener('click', (e) => {
            e.preventDefault();
            handleAction(e.target, 'optimize_db', (data) => {
                if (data.success) {
                    const msg = data.saved > 0 ? `最佳化完成！節省 ${data.saved} MB 空間` : '資料庫已是最佳狀態';
                    UI.showNotification(msg, 'success');
                } else {
                    UI.showNotification(data.message, 'error');
                }
            });
        });
        
        document.getElementById('check-update-btn')?.addEventListener('click', (e) => {
            e.preventDefault();
            handleAction(e.target, 'check_update', (data) => {
                if (data.success) {
                    if (data.isDev) {
                        // 测试版本提示
                        const message = `<div class="title">${data.message}</div><div class="content">穩定版本：V${data.latest}</div><div class="footer">前往 dev 分支查看更新？</div>`;
                        UI.createConfirmDialog(message, () => window.open(data.url, '_blank'), {
                            confirmText: '前往 dev 分支',
                            type: 'warning'
                        });
                    } else if (data.hasUpdate) {
                        // 有新版本
                        const message = `<div class="title">${data.message}</div><div class="content">目前版本：V${data.current}</div><div class="footer">是否前往下載？</div>`;
                        UI.createConfirmDialog(message, () => window.open(data.url, '_blank'), {
                            confirmText: '前往下載',
                        });
                    } else {
                        // 已是最新版本
                        UI.showNotification(`目前版本 v${data.current} 已是最新`, 'success');
                    }
                } else {
                    UI.showNotification(data.message, 'error');
                }
            });
        });

        document.getElementById('test-turnstile-btn')?.addEventListener('click', async (e) => {
            e.preventDefault();
            const btn = e.currentTarget;
            btn.disabled = true;
            const originalText = btn.textContent;
            btn.textContent = '連線測試中...';

            try {
                const secretKeyInput = document.getElementById('turnstile_secret_key');
                const secretKey = secretKeyInput ? secretKeyInput.value.trim() : '';
                const formData = new FormData();
                formData.append('action', 'test_turnstile');
                if (secretKey) {
                    formData.append('secret_key', secretKey);
                }

                const response = await fetch('settings.php', {
                    method: 'POST',
                    headers: {'X-Requested-With': 'XMLHttpRequest'},
                    body: formData
                });

                const data = await response.json();
                UI.showNotification(data.message, data.success ? 'success' : 'error');
            } catch (error) {
                console.error('Error testing turnstile:', error);
                UI.showNotification('連線測試出錯，請檢查網路或稍後再試', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        });

        // 存储测试按钮
        document.querySelectorAll('.test-storage-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                btn.disabled = true;
                btn.classList.add('testing');
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'test_storage');
                    formData.append('storage_type', btn.dataset.storage);
                    
                    const response = await fetch('settings.php', {
                        method: 'POST',
                        headers: {'X-Requested-With': 'XMLHttpRequest'},
                        body: formData
                    });
                    
                    const data = await response.json();
                    UI.showNotification(data.message, data.success ? 'success' : 'error');
                    
                    if (!data.success && data.error) {
                            console.error('儲存連線錯誤詳情:', data.error);
                    }
                } finally {
                    btn.disabled = false;
                    btn.classList.remove('testing');
                }
            });
        });
    };

    const openSettings = async (e) => {
        e.preventDefault();
        try {
            const response = await fetch('settings.php', {
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            });
            
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            
            modal.querySelector('.modal-content').innerHTML = await response.text();
            modal.style.display = 'block';
            void modal.offsetHeight;
            modal.classList.add('show');
            
            if (window.lucide) {
                lucide.createIcons();
            }

            initializeSettingsForm();
            setupModalCloseHandlers(modal, () => closeModal(modal));
        } catch (error) {
            console.error('Error loading settings:', error);
            UI.showNotification('載入設定失敗', 'error');
        }
    };

    settingsLinks.forEach(link => link.addEventListener('click', openSettings));
});
