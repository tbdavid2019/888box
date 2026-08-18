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
    'The download action must appear between the title and the asset metadata.'
);
assert(
    view.includes('class="btn-download"') && view.includes('立即下載') &&
    !view.slice(
        view.indexOf('class="asset-primary-actions"'),
        view.indexOf('class="asset-meta"')
    ).includes('report-panel'),
    'The primary action must retain the established 立即下載 label.'
);
assert(
    !view.includes('floating-download') &&
    view.includes('background: #7dcfff;') &&
    view.includes('color: #10111a;') &&
    view.includes('.btn-download,') &&
    view.includes('color: #10111a !important;'),
    'The primary download button must use the same high-contrast light-blue treatment as the install action.'
);
assert(
    view.includes('margin-inline: auto;'),
    'The primary download action must be centered beneath the title.'
);
assert(
    view.includes('@keyframes download-attention-nudge') &&
    view.includes('animation: download-attention-nudge 460ms cubic-bezier(0.16, 1, 0.3, 1) 900ms 1 both;') &&
    view.includes('@media (prefers-reduced-motion: reduce)') &&
    view.includes('animation: none;'),
    'The download action must provide a single subtle attention nudge that respects reduced-motion preferences.'
);
assert(
    view.indexOf('class="user-guide-cta"') < view.indexOf('class="report-panel report-panel-footer"') &&
    view.indexOf('class="report-panel report-panel-footer"') < view.indexOf('<footer class="portal-footer">'),
    'The report action must be a secondary control immediately above the footer.'
);
assert(
    view.includes('.asset-header {') && view.includes('padding-top: 34px;'),
    'The asset title must reserve space beneath the fixed navigation bar.'
);
assert(
    view.includes('color: #172554 !important;') && view.includes('background: #4f86df;'),
    'Large EPUB guidance and upload actions must remain legible against their surfaces.'
);

console.log('Floating download contract checks passed.');
