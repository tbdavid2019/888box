import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import process from 'node:process';

const view = readFileSync(resolve(import.meta.dirname, '..', 'view.php'), 'utf8');

function assert(condition, message) {
    if (!condition) {
        console.error(message);
        process.exit(1);
    }
}

for (const tag of [
    'name="description"',
    'property="og:description"',
    'property="og:image"',
    'property="og:url"',
    'property="og:site_name"',
    'property="og:locale"',
    'name="twitter:card"',
    'name="twitter:description"',
    'name="twitter:image"',
    'rel="canonical"',
    'type="application/ld+json"',
    'rel="icon" href="/static/favicon.ico"',
    'sizes="32x32"'
]) {
    assert(view.includes(tag), `Share pages must include ${tag}.`);
}

assert(
    view.includes('class="language-switcher"') &&
    view.includes('data-language="zh-Hant"') &&
    view.includes('data-language="en"') &&
    view.includes('navigator.languages') &&
    view.includes('localStorage.setItem(shareLanguageStorageKey, language)'),
    'Share pages must provide automatic language detection and a persistent top-right language switcher.'
);

assert(
    view.includes('const shareTranslations = {') &&
    view.includes('data-i18n="download"') &&
    view.includes('data-i18n="embedTitle"') &&
    view.includes('data-i18n="reportTitle"'),
    'Share-page interface labels must be available in Traditional Chinese and English.'
);

console.log('Share SEO and bilingual UI contract checks passed.');
