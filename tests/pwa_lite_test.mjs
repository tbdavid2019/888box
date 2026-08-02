import { existsSync, readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import process from 'node:process';
import vm from 'node:vm';

const projectRoot = resolve(import.meta.dirname, '..');

function fail(message) {
    console.error(message);
    process.exit(1);
}

function assert(condition, message) {
    if (!condition) {
        fail(message);
    }
}

function readProjectFile(path) {
    return readFileSync(resolve(projectRoot, path), 'utf8');
}

const manifest = JSON.parse(readProjectFile('static/site.webmanifest'));
assert(manifest.display === 'standalone', 'The web app manifest must use standalone display mode.');

const iconDefinitions = new Map(manifest.icons.map((icon) => [icon.src, icon]));
for (const [src, size] of [
    ['/static/pwa-192.png', '192x192'],
    ['/static/pwa-512.png', '512x512'],
    ['/static/pwa-maskable-512.png', '512x512'],
]) {
    const icon = iconDefinitions.get(src);
    assert(icon, `The manifest must define ${src}.`);
    assert(icon.sizes === size, `${src} must declare ${size}.`);
    assert(existsSync(resolve(projectRoot, `.${src}`)), `${src} must exist.`);
}

assert(
    iconDefinitions.get('/static/pwa-maskable-512.png').purpose.includes('maskable'),
    'The 512px maskable icon must declare maskable purpose.'
);

const serviceWorker = readProjectFile('sw.js');
assert(serviceWorker.includes("const OFFLINE_URL = '/offline.html';"), 'The service worker must define the offline fallback.');
assert(serviceWorker.includes("const CACHE_NAME = '888box-pwa-v5';"), 'The service worker cache version must advance when precached PWA code changes.');
assert(serviceWorker.includes('const NETWORK_ONLY_PATHS = ['), 'The service worker must define protected network-only paths.');
assert(serviceWorker.includes("'/api.php'"), 'The service worker must exclude api.php from caching.');
assert(serviceWorker.includes("'/admin/'"), 'The service worker must exclude admin pages from caching.');

const registrationModule = readProjectFile('static/js/pwa.js');
assert(registrationModule.includes('window.isSecureContext'), 'The registration module must only register on secure contexts.');
assert(registrationModule.includes("register('/sw.js', { scope: '/' })"), 'The registration module must use the root-scoped service worker.');
assert(registrationModule.includes("window.addEventListener('beforeinstallprompt'"), 'The registration module must capture the browser install event.');
assert(registrationModule.includes("event.preventDefault();"), 'The registration module must defer the browser install event until the user taps install.');
assert(registrationModule.includes('promptEvent.prompt()'), 'The installation button must open the browser install dialog from a user action.');
assert(registrationModule.includes("window.addEventListener('appinstalled'"), 'The registration module must remove the prompt after installation.');
assert(registrationModule.includes('安裝 888 BOX'), 'The installation prompt must use a clear Chinese call to action.');

const windowListeners = {};
const appendedElements = [];
const fakeDocument = {
    body: {
        append(element) {
            appendedElements.push(element);
        },
    },
    createElement() {
        const dismissButton = { listeners: {}, addEventListener(type, listener) { this.listeners[type] = listener; } };
        const installButton = { disabled: false, listeners: {}, addEventListener(type, listener) { this.listeners[type] = listener; } };

        return {
            dismissed: false,
            setAttribute() {},
            querySelector(selector) {
                return selector.includes('dismiss') ? dismissButton : installButton;
            },
            remove() {
                this.dismissed = true;
            },
            dismissButton,
            installButton,
        };
    },
};

vm.runInNewContext(registrationModule, {
    console,
    document: fakeDocument,
    navigator: { serviceWorker: { register() { return Promise.resolve(); } } },
    window: {
        isSecureContext: true,
        addEventListener(type, listener) {
            windowListeners[type] = listener;
        },
        matchMedia() {
            return { matches: false };
        },
    },
});

let prevented = false;
let browserPromptCalls = 0;
windowListeners.beforeinstallprompt({
    preventDefault() {
        prevented = true;
    },
    prompt() {
        browserPromptCalls += 1;
        return Promise.resolve();
    },
});

assert(prevented, 'The browser install event must be deferred.');
assert(appendedElements.length === 1, 'A custom install prompt must appear when Chrome allows installation.');
await appendedElements[0].installButton.listeners.click();
assert(browserPromptCalls === 1, 'Tapping the custom install button must open the browser install dialog.');
assert(appendedElements[0].dismissed, 'The custom install prompt must close after requesting installation.');

const offlinePage = readProjectFile('offline.html');
assert(offlinePage.includes('上傳功能需要網路連線'), 'The offline fallback must explain that uploads require a connection.');

for (const page of [
    'index.php',
    'upload_image.php',
    'upload_video.php',
    'upload_audio.php',
    'upload_file.php',
    'view.php',
]) {
    const contents = readProjectFile(page);
    assert(contents.includes('static/site.webmanifest'), `${page} must include the PWA manifest.`);
    assert(contents.includes('static/js/pwa.js'), `${page} must load the PWA registration module.`);
}

console.log('PWA Lite contract checks passed.');
