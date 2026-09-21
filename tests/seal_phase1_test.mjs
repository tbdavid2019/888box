import { readFileSync, existsSync } from 'node:fs';
import { resolve } from 'node:path';
import process from 'node:process';

const projectRoot = resolve(import.meta.dirname, '..');

function read(relativePath) {
    return readFileSync(resolve(projectRoot, relativePath), 'utf8');
}

function assert(condition, message) {
    if (!condition) {
        console.error(`Seal phase 1 contract failed: ${message}`);
        process.exit(1);
    }
}

assert(existsSync(resolve(projectRoot, 'config/seal.php')), 'config/seal.php must exist.');
assert(existsSync(resolve(projectRoot, 'admin/seals.php')), 'admin/seals.php must exist.');

const schema = read('config/schema.php');
assert(schema.includes("'seals' =>"), 'schema.php must create a seals table.');
assert(schema.includes('seal_token') && schema.includes('pulse_token_hash'), 'seals must store public and hashed private tokens.');
assert(schema.includes('max_views') && schema.includes('view_count'), 'seals must support ephemeral view limits.');

const sealHelper = read('config/seal.php');
for (const functionName of [
    'getSealState',
    'getSealStatusPayload',
    'getActiveSealForAsset',
    'findSealByToken',
    'findSealByPulseToken',
    'recordSealView',
    'scheduleEphemeralAssetDeletion',
    'cleanupExpiredSeals',
]) {
    assert(new RegExp(`function\\s+${functionName}\\b`).test(sealHelper), `${functionName} must be defined.`);
}
assert(sealHelper.includes('view_count < max_views'), 'ephemeral views must use an atomic upper-bound update.');
assert(!sealHelper.includes('seal_delivery_'), 'ephemeral views must not grant unlimited session downloads.');

const api = read('api.php');
for (const action of ['seal_create', 'seal_status', 'seal_pulse', 'seal_burn', 'seal_revoke', 'seal_cleanup']) {
    assert(api.includes(`'${action}'`), `api.php must route ${action}.`);
}
assert(api.includes("if (in_array($action, $sealActions, true))"), 'Seal token actions must remain reachable when login restriction is enabled.');
assert(api.includes('requireSealAdmin'), 'Seal creation and revoke must require an administrator session.');
assert(api.includes('csrf_token'), 'Admin Seal mutations must validate a CSRF token.');

const view = read('view.php');
assert(view.includes("require_once 'config/seal.php'"), 'view.php must load Seal authorization helpers.');
assert(view.includes('getActiveSealForAsset'), 'view.php must enforce an active Seal before rendering content.');
assert(view.includes('sealLocked') && view.includes('sealExhausted'), 'view.php must render locked and exhausted Seal states.');

const getFile = read('get_file.php');
assert(getFile.includes("require_once 'config/seal.php'"), 'get_file.php must load Seal authorization helpers.');
assert(getFile.includes('getSealAccessDecision'), 'get_file.php must enforce Seal authorization for direct delivery.');
assert(sealHelper.includes('recordSealView'), 'Seal delivery authorization must count Ephemeral deliveries.');
assert(getFile.includes('scheduleEphemeralAssetDeletion'), 'Ephemeral delivery must schedule cleanup after the final view.');
assert(getFile.includes("Cache-Control: private, no-store"), 'sealed deliveries must not be publicly cached.');

const htaccess = read('storage/.htaccess');
assert(htaccess.includes('get_file.php?path='), 'storage assets must continue through the authorization proxy.');

const adminPage = read('admin/seals.php');
assert(adminPage.includes('seal_create') && adminPage.includes('seal_pulse') && adminPage.includes('seal_burn') && adminPage.includes('seal_revoke'), 'admin page must expose Seal operations.');
assert(adminPage.includes('seal_csrf_token'), 'admin page must provision a CSRF token.');

console.log('Seal phase 1 contract checks passed.');
