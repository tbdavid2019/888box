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
    view.includes('.floating-download') && view.includes('position: fixed'),
    'The direct-download action must remain fixed and visible while the preview is open.'
);
assert(
    view.includes('class="floating-download"') && view.includes('下載原檔'),
    'The fixed action must clearly identify the file download action.'
);
assert(
    view.includes('class="floating-download"') && view.includes('href="<?= htmlspecialchars($url) ?>"'),
    'The fixed action must use the existing authorized asset URL.'
);

console.log('Floating download contract checks passed.');
