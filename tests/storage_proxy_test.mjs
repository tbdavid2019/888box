import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import process from 'node:process';

const projectRoot = resolve(import.meta.dirname, '..');
const getFile = readFileSync(resolve(projectRoot, 'get_file.php'), 'utf8');
const storageHtaccess = readFileSync(resolve(projectRoot, 'storage/.htaccess'), 'utf8');

function assert(condition, message) {
    if (!condition) {
        console.error(message);
        process.exit(1);
    }
}

assert(
    !getFile.includes("header('Location: ' . $url, true, 302);"),
    'Cloud-backed storage must not redirect the browser to the upstream URL.'
);
assert(
    getFile.includes("$_SERVER['HTTP_RANGE']") || getFile.includes("HTTP_RANGE"),
    'The storage proxy must preserve HTTP range requests for video and audio playback.'
);
assert(
    getFile.includes("auth_asset_") && getFile.includes("$asset['password']"),
    'The storage proxy must enforce the existing per-asset password session.'
);
assert(
    getFile.includes('S3Client') && getFile.includes('getObject'),
    'The storage proxy must fetch S3 objects server-side.'
);
assert(
    storageHtaccess.includes('RewriteRule ^(i|file)/(.+)') && storageHtaccess.includes('get_file.php?path='),
    'Asset paths must be routed through the authorization-aware proxy even when local files exist.'
);

console.log('Storage proxy contract checks passed.');
