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

const countStart = view.indexOf('// 3. 只接受瀏覽器首次標記後的 POST 計數請求。');
const countEnd = view.indexOf("if ($isAuthorized && isset($_GET['pdf_inline']))");
const countBlock = view.slice(countStart, countEnd);

assert(
    !view.includes('VIEW_COUNT_DEDUPLICATION_SECONDS') &&
    !view.includes('shouldIncrementAssetViewCount') &&
    countBlock.includes("$_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_view'])"),
    'View counting must no longer use a short-lived server-session cooldown.'
);
assert(
    countBlock.includes("$pdo->prepare(\"UPDATE images SET view_count = view_count + 1 WHERE id = ?\")") &&
    countBlock.includes("'view_count' => (int)$asset['view_count'] + 1") &&
    view.includes("const viewMarkerKey = '888box:view-counted:v1:'") &&
    view.includes('localStorage.getItem(viewMarkerKey)') &&
    view.includes("localStorage.setItem(viewMarkerKey, '1')") &&
    view.includes("body: 'record_view=1'"),
    'A browser-local permanent marker must ensure the same device only records each asset once.'
);

console.log('View count deduplication contract checks passed.');
