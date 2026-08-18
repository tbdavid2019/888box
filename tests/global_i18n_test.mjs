import assert from 'node:assert/strict';
import { access, readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);

const read = async (path) => readFile(new URL(path, root), 'utf8');

const helper = await read('config/theme_helper.php');
assert.match(helper, /function renderLanguageSwitcher\s*\(/);
assert.match(helper, /function renderI18nAssets\s*\(/);

const pages = [
    'index.php',
    'upload_image.php',
    'upload_video.php',
    'upload_audio.php',
    'upload_file.php',
    'admin/login.php',
    'admin/index.php',
    'admin/video.php',
    'admin/audio.php',
    'admin/file.php',
];

for (const page of pages) {
    const source = await read(page);
    const rendersInSharedAdminLayout = page.startsWith('admin/') && page !== 'admin/login.php';
    assert.ok(
        /renderLanguageSwitcher\s*\(/.test(source) || rendersInSharedAdminLayout,
        `${page} must render the language switcher`
    );
    assert.ok(
        /renderI18nAssets\s*\(/.test(source) || rendersInSharedAdminLayout,
        `${page} must load the shared i18n runtime`
    );
}

const runtime = await read('static/js/i18n.js');
assert.match(runtime, /navigator\.languages/);
assert.match(runtime, /localStorage\.setItem\(languageStorageKey, language\)/);
assert.match(helper, /data-language="en"/);
assert.match(runtime, /MutationObserver/);

for (const phrase of [
    '支援 WebP 高效壓縮與瀑布流展示',
    '自動提取 MetaData 與 Podcast RSS 同步',
    '支援 ZIP, PDF, Word 及 EPUB 線上閱讀',
    '支援 MP3/WAV 上傳與 Podcast RSS 訂閱',
    '🚀 上傳的影片將會自動加入至 Podcast 訂閱中！'
]) {
    assert.match(runtime, new RegExp(phrase.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
}

const index = await read('index.php');
for (const phrase of [
    '支援 WebP 高效壓縮與瀑布流展示',
    '自動提取 MetaData 與 Podcast RSS 同步',
    '支援 ZIP, PDF, Word 及 EPUB 線上閱讀',
    '支援 MP3/WAV 上傳與 Podcast RSS 訂閱'
]) {
    assert.match(index, new RegExp(phrase.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
}

const view = await read('view.php');
assert.match(view, /\.asset-header-top\s*\{[\s\S]*?top:\s*0;[\s\S]*?left:\s*0;[\s\S]*?width:\s*100%;/);
assert.match(view, /border-radius:\s*0;/);
assert.ok(
    view.indexOf('<div class="asset-header-top">') < view.indexOf('<div class="view-container">'),
    'Share header must sit outside the content card so it can span the viewport.'
);
assert.match(view, /background:\s*#080d16;/);
assert.match(view, /og:image:type/);
await access(new URL('../static/og-image.png', import.meta.url));

console.log('global i18n contract checks passed');
