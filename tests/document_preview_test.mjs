import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import process from 'node:process';

const projectRoot = resolve(import.meta.dirname, '..');
const view = readFileSync(resolve(projectRoot, 'view.php'), 'utf8');

function assert(condition, message) {
    if (!condition) {
        console.error(message);
        process.exit(1);
    }
}

assert(
    view.includes("const TEXT_PREVIEW_EXTENSIONS = ['txt', 'md', 'json', 'csv', 'log', 'yaml', 'yml'];"),
    'Supported text-like extensions must be centrally declared.'
);
assert(
    view.includes("$inlineMode = $_GET['inline'] ?? '';"),
    'The share page must explicitly select a restricted inline preview mode.'
);
assert(
    view.includes("outputInlineAsset($asset, $config, $inlineMode);"),
    'Inline preview delivery must remain behind the existing asset authorization flow.'
);
assert(
    view.includes("elseif (in_array($ext, TEXT_PREVIEW_EXTENSIONS, true))"),
    'Text-like assets must be classified for preview.'
);
assert(
    view.includes('id="text-viewer"'),
    'The share page must include a dedicated readable text viewer.'
);
assert(
    view.includes('textContent = text;'),
    'Text preview content must be inserted as text, not HTML.'
);
assert(
    view.includes('inline=epub'),
    'EPUB.js must load the book through the same-origin authorized inline route.'
);
assert(
    view.includes('https://cdn.jsdelivr.net/npm/epubjs@0.3.88/dist/epub.min.js'),
    'EPUB preview must use the verified EPUB.js distribution instead of the broken CDN URL.'
);
assert(
    view.includes('data-type="share"') && view.includes("selectEmbedType('share', this)"),
    'The share-page URL must be available as the primary link format.'
);
assert(
    view.includes('share: <?= json_encode($shareUrl) ?>'),
    'The share-page URL must be the value copied by the share tab.'
);

console.log('Document preview contract checks passed.');
