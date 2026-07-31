import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import process from 'node:process';

const projectRoot = resolve(import.meta.dirname, '..');

function fail(message) {
    console.error(message);
    process.exit(1);
}

function assert(condition, message) {
    if (!condition) {
        fail(message);
    }
}

function readProjectFile(path) {
    return readFileSync(resolve(projectRoot, path), 'utf8');
}

const portal = readProjectFile('index.php');
assert(
    portal.includes("const pageUrl = data.share_url || data.page_url || data.url || '#';"),
    'The portal upload queue must prefer share_url for its copy and view actions.'
);

for (const script of [
    'static/js/video_app.js',
    'static/js/audio_app.js',
    'static/js/file_app.js',
]) {
    const contents = readProjectFile(script);
    assert(
        contents.includes('const shareUrl = entry.shareUrl || entry.url;'),
        `${script} must prefer a stored share URL when rendering upload history.`
    );
    assert(contents.includes('link.href = shareUrl;'), `${script} must open the share URL from history.`);
    assert(contents.includes('openLink.href = shareUrl;'), `${script} must use the share URL for its history open action.`);
    assert(contents.includes('copyUrl(shareUrl,'), `${script} must copy the share URL from history.`);
}

const imageHistory = readProjectFile('static/js/main.js');
assert(
    imageHistory.includes('const shareUrl = entry.shareUrl || entry.url;'),
    'Image history must prefer a stored share URL when rendering upload history.'
);
assert(imageHistory.includes('link.href = shareUrl;'), 'Image history must open the share URL.');
assert(imageHistory.includes('openLink.href = shareUrl;'), 'Image history open action must use the share URL.');
assert(imageHistory.includes('copyHistoryUrl(shareUrl)'), 'Image history copy action must use the share URL.');

for (const script of [
    'static/js/video_app.js',
    'static/js/audio_app.js',
    'static/js/file_app.js',
]) {
    const contents = readProjectFile(script);
    assert(
        contents.includes('url: res.data.share_url || res.data.url'),
        `${script} must store the share URL as the primary URL for new history entries.`
    );
}

assert(
    imageHistory.includes('thumb.src = entry.previewUrl || entry.url;'),
    'Image history thumbnails must continue using the raw preview URL.'
);

for (const page of [
    'admin/index.php',
    'admin/file.php',
    'admin/video.php',
    'admin/audio.php',
]) {
    assert(
        readProjectFile(page).includes('buildAssetShareUrl'),
        `${page} must retain its share-page action for administrators.`
    );
}

console.log('Share page link contract checks passed.');
