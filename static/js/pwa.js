let deferredInstallPrompt;
let installPromptElement;

function removeInstallPrompt() {
    if (!installPromptElement) {
        return;
    }

    installPromptElement.remove();
    installPromptElement = undefined;
}

function showInstallPrompt() {
    if (installPromptElement || window.matchMedia('(display-mode: standalone)').matches) {
        return;
    }

    installPromptElement = document.createElement('section');
    installPromptElement.className = 'pwa-install-prompt';
    installPromptElement.setAttribute('role', 'dialog');
    installPromptElement.setAttribute('aria-label', '安裝 888box 應用程式');
    installPromptElement.innerHTML = `
        <style>
            .pwa-install-prompt {
                position: fixed;
                right: 16px;
                bottom: 16px;
                left: 16px;
                z-index: 2147483647;
                box-sizing: border-box;
                max-width: 440px;
                margin: 0 auto;
                padding: 16px;
                border: 1px solid rgba(125, 207, 255, 0.32);
                border-radius: 18px;
                color: #e6edf3;
                background: rgba(26, 27, 38, 0.98);
                box-shadow: 0 16px 44px rgba(0, 0, 0, 0.38);
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            }
            .pwa-install-prompt__content {
                display: flex;
                gap: 12px;
                align-items: flex-start;
            }
            .pwa-install-prompt__icon {
                flex: 0 0 auto;
                width: 44px;
                height: 44px;
                border-radius: 12px;
            }
            .pwa-install-prompt__title {
                margin: 0;
                font-size: 1rem;
                font-weight: 700;
            }
            .pwa-install-prompt__description {
                margin: 4px 0 0;
                color: #a9b1d6;
                font-size: 0.84rem;
                line-height: 1.45;
            }
            .pwa-install-prompt__actions {
                display: flex;
                gap: 8px;
                margin-top: 14px;
            }
            .pwa-install-prompt button {
                min-height: 38px;
                border: 0;
                border-radius: 9px;
                padding: 0 13px;
                font: inherit;
                font-size: 0.9rem;
                cursor: pointer;
            }
            .pwa-install-prompt__install {
                flex: 1;
                color: #10111a;
                background: #7dcfff;
                font-weight: 700;
            }
            .pwa-install-prompt__dismiss {
                color: #c0caf5;
                background: transparent;
            }
            .pwa-install-prompt button:focus-visible {
                outline: 2px solid #bb9af7;
                outline-offset: 2px;
            }
            .pwa-install-prompt button:disabled {
                cursor: wait;
                opacity: 0.65;
            }
            @media (min-width: 560px) {
                .pwa-install-prompt {
                    right: 24px;
                    bottom: 24px;
                    left: auto;
                }
            }
        </style>
        <div class="pwa-install-prompt__content">
            <img class="pwa-install-prompt__icon" src="/static/pwa-192.png" alt="">
            <div>
                <p class="pwa-install-prompt__title">安裝 888box</p>
                <p class="pwa-install-prompt__description">加到手機主畫面，像 App 一樣快速開啟。</p>
            </div>
        </div>
        <div class="pwa-install-prompt__actions">
            <button class="pwa-install-prompt__dismiss" type="button">暫時不要</button>
            <button class="pwa-install-prompt__install" type="button">安裝 888box</button>
        </div>
    `;

    const dismissButton = installPromptElement.querySelector('.pwa-install-prompt__dismiss');
    const installButton = installPromptElement.querySelector('.pwa-install-prompt__install');

    dismissButton.addEventListener('click', removeInstallPrompt);
    installButton.addEventListener('click', async () => {
        if (!deferredInstallPrompt) {
            return;
        }

        installButton.disabled = true;
        const promptEvent = deferredInstallPrompt;
        deferredInstallPrompt = undefined;

        try {
            await promptEvent.prompt();
        } catch (error) {
            console.error('PWA install prompt failed:', error);
        }

        removeInstallPrompt();
    });

    document.body.append(installPromptElement);
}

if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredInstallPrompt = event;
        showInstallPrompt();
    });

    window.addEventListener('appinstalled', () => {
        deferredInstallPrompt = undefined;
        removeInstallPrompt();
    });

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' })
            .catch((error) => console.error('PWA service worker registration failed:', error));
    });
}
