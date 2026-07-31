const CACHE_PREFIX = '888box-pwa-';
const CACHE_NAME = '888box-pwa-v1';
const OFFLINE_URL = '/offline.html';
const NETWORK_ONLY_PATHS = [
    '/admin/',
    '/api.php',
    '/api_file.php',
    '/get_file.php',
    '/mcp.php',
    '/skill.php',
    '/storage/',
    '/i/',
];
const PRECACHE_URLS = [
    OFFLINE_URL,
    '/static/site.webmanifest',
    '/static/favicon.svg',
    '/static/pwa-192.png',
    '/static/pwa-512.png',
    '/static/pwa-maskable-512.png',
    '/static/css/fonts.css',
    '/static/css/portal.css',
    '/static/js/lucide.min.js',
    '/static/js/pwa.js',
    '/static/fonts/JetBrainsMono-Medium.woff2',
    '/static/fonts/GenJyuuGothic-Medium.woff2',
    '/static/fonts/MapleMonoNormal-Medium.woff2',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => Promise.all(
                cacheNames
                    .filter((cacheName) => cacheName.startsWith(CACHE_PREFIX) && cacheName !== CACHE_NAME)
                    .map((cacheName) => caches.delete(cacheName))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin || isNetworkOnly(url.pathname)) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(OFFLINE_URL))
        );
        return;
    }

    if (url.pathname.startsWith('/static/')) {
        event.respondWith(cacheStaticAsset(request));
    }
});

function isNetworkOnly(pathname) {
    return NETWORK_ONLY_PATHS.some((path) => pathname.startsWith(path));
}

async function cacheStaticAsset(request) {
    const cache = await caches.open(CACHE_NAME);
    const cacheKey = createStaticCacheKey(request);
    const cachedResponse = await cache.match(cacheKey);

    if (cachedResponse) {
        return cachedResponse;
    }

    const response = await fetch(request);
    if (response.ok) {
        await cache.put(cacheKey, response.clone());
    }

    return response;
}

function createStaticCacheKey(request) {
    const url = new URL(request.url);
    url.search = '';
    return new Request(url.toString());
}
