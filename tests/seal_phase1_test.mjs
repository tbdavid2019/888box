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
assert(schema.includes("'asset_capabilities' =>"), 'schema.php must create asset capability storage.');
assert(schema.includes('token_hash') && schema.includes('asset_id INTEGER NOT NULL UNIQUE'), 'asset capabilities must be asset-scoped and hashed.');

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
    'generateAssetManageToken',
    'issueAssetManageToken',
    'findAssetByManageToken',
    'createSealRecord',
]) {
    assert(new RegExp(`function\\s+${functionName}\\b`).test(sealHelper), `${functionName} must be defined.`);
}
assert(sealHelper.includes('view_count < max_views'), 'ephemeral views must use an atomic upper-bound update.');
assert(!sealHelper.includes('seal_delivery_'), 'ephemeral views must not grant unlimited session downloads.');

const api = read('api.php');
for (const action of ['seal_create', 'seal_status', 'seal_admin_status', 'seal_pulse', 'seal_burn', 'seal_revoke', 'seal_cleanup']) {
    assert(api.includes(`'${action}'`), `api.php must route ${action}.`);
}
assert(api.includes("if (in_array($action, $sealActions, true))"), 'Seal token actions must remain reachable when login restriction is enabled.');
assert(api.includes('requireSealAdmin'), 'Seal creation and revoke must require an administrator session.');
assert(api.includes('csrf_token'), 'Admin Seal mutations must validate a CSRF token.');
for (const action of ['seal_capability_status']) {
    assert(api.includes(`'${action}'`), `api.php must route ${action}.`);
}
assert(api.includes('manage_token') && api.includes('requireSealMutationAccess'), 'REST Seal mutations must support asset capability authorization.');

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
assert(adminPage.includes("header('Location: /admin/index.php?notice=seal_controls_moved')"), 'legacy admin/seals.php must redirect to asset-local Seal controls.');

for (const adminFile of ['admin/index.php', 'admin/video.php', 'admin/audio.php', 'admin/file.php']) {
    const adminCode = read(adminFile);
    assert(adminCode.includes('seal-controls.js'), `${adminFile} must load shared Seal controls.`);
    assert(adminCode.includes('seal-controls.css'), `${adminFile} must load shared Seal styles.`);
    assert(adminCode.includes('seal-csrf-token'), `${adminFile} must expose the Seal CSRF token.`);
}
const sealControls = read('static/js/admin/seal-controls.js');
assert(sealControls.includes('seal_admin_status') && sealControls.includes('seal_create') && sealControls.includes('seal_revoke'), 'Shared Seal controls must support status, create, and revoke.');

const userSealControls = read('static/js/seal-user-controls.js');
assert(userSealControls.includes('seal_capability_status') && userSealControls.includes('manage_token'), 'User Seal controls must use the asset capability API.');
assert(read('static/css/seal-user-controls.css').includes('.seal-user-dialog'), 'User Seal controls must include a responsive dialog style.');

for (const uploadFile of ['config/upload.php', 'config/video_logic.php', 'config/audio_logic.php', 'api_file.php', 'mcp.php']) {
    assert(read(uploadFile).includes('manage_token'), `${uploadFile} must return an asset manage token.`);
}

const mcp = read('mcp.php');
for (const tool of ['create_asset_seal', 'get_asset_seal', 'revoke_asset_seal', 'pulse_asset_seal', 'burn_asset_seal']) {
    assert(mcp.includes(`'name' => '${tool}'`), `MCP must expose ${tool}.`);
}

const skill = read('skill.php');
assert(skill.includes('seal_create') && skill.includes('manage_token'), 'skill.php must document REST Seal management and capability tokens.');
assert(read('index.php').includes("name: 'create_asset_seal'") && read('index.php').includes("name: 'get_asset_seal'"), 'WebMCP portal tools must expose Seal configuration and status.');

console.log('Seal phase 1 contract checks passed.');
