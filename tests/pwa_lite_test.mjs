import { existsSync, readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import process from 'node:process';

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
assert(serviceWorker.includes('const NETWORK_ONLY_PATHS = ['), 'The service worker must define protected network-only paths.');
assert(serviceWorker.includes("'/api.php'"), 'The service worker must exclude api.php from caching.');
assert(serviceWorker.includes("'/admin/'"), 'The service worker must exclude admin pages from caching.');

const registrationModule = readProjectFile('static/js/pwa.js');
assert(registrationModule.includes('window.isSecureContext'), 'The registration module must only register on secure contexts.');
assert(registrationModule.includes("register('/sw.js', { scope: '/' })"), 'The registration module must use the root-scoped service worker.');

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
