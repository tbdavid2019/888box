import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

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

console.log('global i18n contract checks passed');
