let deferredInstallPrompt;
let installPromptElement;
const pwaLanguageStorageKey = '888box:share-language:v1';
const pwaTranslations = {
    'zh-Hant': {
        ariaLabel: '安裝 888 BOX 應用程式',
        title: '安裝 888 BOX',
        description: '加到手機主畫面，像 App 一樣快速開啟。',
        dismiss: '暫時不要',
        install: '安裝 888 BOX'
    },
    en: {
        ariaLabel: 'Install 888 BOX app',
        title: 'Install 888 BOX',
        description: 'Add it to your home screen for quick App-like access.',
        dismiss: 'Not now',
        install: 'Install 888 BOX'
    }
};

function getPwaLanguage() {
    try {
        const storedLanguage = localStorage.getItem(pwaLanguageStorageKey);
        if (pwaTranslations[storedLanguage]) return storedLanguage;
    } catch (error) {
        // Use browser preference when storage is unavailable.
    }
    return /^zh/i.test(navigator.language || '') ? 'zh-Hant' : 'en';
}

function applyPwaLanguage(language = getPwaLanguage()) {
    const strings = pwaTranslations[language] || pwaTranslations['zh-Hant'];
    if (!installPromptElement) return;

    installPromptElement.setAttribute('aria-label', strings.ariaLabel);
    installPromptElement.querySelector('.pwa-install-prompt__title').textContent = strings.title;
    installPromptElement.querySelector('.pwa-install-prompt__description').textContent = strings.description;
    installPromptElement.querySelector('.pwa-install-prompt__dismiss').textContent = strings.dismiss;
    installPromptElement.querySelector('.pwa-install-prompt__install').textContent = strings.install;
}

window.BOX_PWA = { applyLanguage: applyPwaLanguage };

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
    installPromptElement.setAttribute('aria-label', '安裝 888 BOX 應用程式');
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
                border: 1px solid #17324d !important;
                border-radius: 16px;
                color: #2b251a !important;
                background: #fff9e8 !important;
                box-shadow: 0 16px 40px rgba(79, 58, 14, 0.18);
                font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
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
                color: #17324d !important;
                font-size: 1rem;
                font-weight: 700;
            }
            .pwa-install-prompt__description {
                margin: 4px 0 0;
                color: #655a43 !important;
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
                border: 1px solid #6b5b38 !important;
                border-radius: 8px;
                padding: 0 13px;
                font: inherit;
                font-size: 0.9rem;
                cursor: pointer;
            }
            .pwa-install-prompt__install {
                flex: 1;
                color: #2b251a !important;
                background: #ffb010 !important;
                font-weight: 700;
            }
            .pwa-install-prompt__dismiss {
                color: #17324d !important;
                background: #fff2d1 !important;
            }
            .pwa-install-prompt button:focus-visible {
                outline: 3px solid #17324d !important;
                outline-offset: 2px !important;
            }
            .pwa-install-prompt button:disabled {
                cursor: wait;
                color: #655a43 !important;
                background: #f7e9c5 !important;
                border-color: #9b8350 !important;
                opacity: 1;
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
                <p class="pwa-install-prompt__title">安裝 888 BOX</p>
                <p class="pwa-install-prompt__description">加到手機主畫面，像 App 一樣快速開啟。</p>
            </div>
        </div>
        <div class="pwa-install-prompt__actions">
            <button class="pwa-install-prompt__dismiss" type="button">暫時不要</button>
            <button class="pwa-install-prompt__install" type="button">安裝 888 BOX</button>
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
    applyPwaLanguage();
}

window.addEventListener('boxlanguagechange', (event) => {
    applyPwaLanguage(event.detail?.language);
});

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
