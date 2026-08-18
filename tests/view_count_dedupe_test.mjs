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

const countStart = view.indexOf('// 3. 同一訪客在 30 分鐘內重整同一分享頁');
const countEnd = view.indexOf("if ($isAuthorized && isset($_GET['pdf_inline']))");
const countBlock = view.slice(countStart, countEnd);

assert(
    view.includes('const VIEW_COUNT_DEDUPLICATION_SECONDS = 1800;') &&
    view.includes('function shouldIncrementAssetViewCount($assetId)') &&
    view.includes("$_SESSION['view_counted_assets']"),
    'View counting must track recent asset views in the visitor session.'
);
assert(
    countBlock.includes('shouldIncrementAssetViewCount($id)') &&
    countBlock.includes("$asset['view_count'] = (int)$asset['view_count'] + 1;") &&
    !countBlock.includes('if ($isAuthorized && !isset($_GET[\'pdf_inline\']) && $inlineMode === \'\') {\n        $pdo->prepare("UPDATE images SET view_count = view_count + 1 WHERE id = ?")'),
    'Refreshing the same share page must not increment the database counter again during the cooldown window.'
);

console.log('View count deduplication contract checks passed.');
