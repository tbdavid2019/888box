import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import process from 'node:process';

const projectRoot = resolve(import.meta.dirname, '..');

function readProjectFile(path) {
    return readFileSync(resolve(projectRoot, path), 'utf8');
}

function assert(condition, message) {
    if (!condition) {
        console.error(message);
        process.exit(1);
    }
}

const database = readProjectFile('config/database.php');

assert(
    database.includes('function getAssetPublicUrl($asset, $config)'),
    'A single delivery URL helper must distinguish public and password-protected assets.'
);
assert(
    database.includes("if (!empty($asset['password']))") && database.includes('return getMaskedUrl($asset'),
    'Password-protected assets must remain on the same-origin authorization proxy.'
);
assert(
    database.includes("$config['s3_cdn_domain']") && database.includes('parse_url($originUrl, PHP_URL_PATH)'),
    'Public S3 assets must use the configured CDN host while retaining legacy object keys.'
);

for (const file of [
    'api.php',
    'view.php',
    'config/audio_logic.php',
    'config/video_logic.php',
    'admin/index.php',
    'admin/video.php',
    'admin/audio.php',
]) {
    assert(
        readProjectFile(file).includes('getAssetPublicUrl('),
        `${file} must use the shared delivery URL policy.`
    );
}

console.log('CDN delivery contract checks passed.');
