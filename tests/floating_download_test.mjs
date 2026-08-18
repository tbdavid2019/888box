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

assert(
    view.includes('class="asset-primary-actions"') &&
    view.indexOf('class="asset-primary-actions"') < view.indexOf('class="asset-meta"'),
    'Download and report actions must appear between the title and the asset metadata.'
);
assert(
    view.includes('class="btn-download"') && view.includes('立即下載'),
    'The primary action must retain the established 立即下載 label.'
);
assert(
    !view.includes('floating-download') && view.includes('background: #365fd1;'),
    'The old floating action must be removed and the primary download button must be visually distinct.'
);
assert(
    view.includes('color: #172554 !important;') && view.includes('background: #4f86df;'),
    'Large EPUB guidance and upload actions must remain legible against their surfaces.'
);

console.log('Floating download contract checks passed.');
