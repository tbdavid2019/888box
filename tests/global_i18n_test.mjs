import assert from 'node:assert/strict';
import { access, readFile } from 'node:fs/promises';

const root = new URL('../', import.meta.url);

const read = async (path) => readFile(new URL(path, root), 'utf8');

const helper = await read('config/theme_helper.php');
assert.match(helper, /function renderLanguageSwitcher\s*\(/);
assert.match(helper, /function renderSiteHeader\s*\(/);
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
        /renderLanguageSwitcher\s*\(|renderSiteHeader\s*\(/.test(source) || rendersInSharedAdminLayout,
        `${page} must render the language switcher`
    );
    assert.ok(
        /renderI18nAssets\s*\(/.test(source) || rendersInSharedAdminLayout,
        `${page} must load the shared i18n runtime`
    );
}

const runtime = await read('static/js/i18n.js');
const pwa = await read('static/js/pwa.js');
assert.match(runtime, /navigator\.languages/);
assert.match(runtime, /localStorage\.setItem\(languageStorageKey, language\)/);
assert.match(helper, /data-language="en"/);
assert.match(runtime, /MutationObserver/);
assert.match(pwa, /boxlanguagechange/);
assert.match(pwa, /BOX_PWA/);
assert.match(pwa, /Install 888 BOX/);

for (const phrase of [
    '支援 WebP 高效壓縮與瀑布流展示',
    '自動提取 MetaData 與 Podcast RSS 同步',
    '支援 ZIP, PDF, Word 及 EPUB 線上閱讀',
  '支援 MP3/WAV 上傳與 Podcast RSS 訂閱',
  '🚀 上傳的影片將會自動加入至 Podcast 訂閱中！',
  '自動辨識 🖼️ 圖片 · 🎬 影片 · 🎙️ 音訊 · 📂 文件 等格式並完成託管',
  '輸入圖片網址即可自動上傳，或使用 Ctrl+V 貼上',
  '原始圖片',
  '壓縮後'
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
assert.match(index, /pwa\.js\?v=2/);

const uploadImage = await read('upload_image.php');
assert.match(uploadImage, /pwa\.js\?v=2/);
assert.match(helper, /\.box-site-header\s*\{[\s\S]*?margin-top:\s*-20px/);

const view = await read('view.php');
assert.match(view, /\.asset-header-top\s*\{[\s\S]*?top:\s*0;[\s\S]*?left:\s*0;[\s\S]*?width:\s*100%;/);
assert.match(view, /border-radius:\s*0;/);
assert.ok(
    view.indexOf('<div class="asset-header-top">') < view.indexOf('<div class="view-container">'),
    'Share header must sit outside the content card so it can span the viewport.'
);
assert.match(view, /background:\s*#080d16;/);
assert.doesNotMatch(view, /門戶|888 BOX Portal/);
assert.match(view, /button\.is-active[\s\S]*?background:\s*#7dcfff/);
assert.match(view, /og:image:type/);
await access(new URL('../static/og-image.png', import.meta.url));

console.log('global i18n contract checks passed');
