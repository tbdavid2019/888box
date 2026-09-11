import { readFileSync, existsSync } from 'node:fs';
import { resolve } from 'node:path';
import assert from 'node:assert';

const projectRoot = resolve('.');

// 1. Verify config/turnstile.php
assert(existsSync(resolve(projectRoot, 'config/turnstile.php')), 'config/turnstile.php must exist.');
const turnstilePhp = readFileSync(resolve(projectRoot, 'config/turnstile.php'), 'utf8');
assert(turnstilePhp.includes('function isTurnstileEnabled'), 'isTurnstileEnabled must be defined.');
assert(turnstilePhp.includes('function getTurnstileSiteKey'), 'getTurnstileSiteKey must be defined.');
assert(turnstilePhp.includes('function verifyTurnstileToken'), 'verifyTurnstileToken must be defined.');
assert(turnstilePhp.includes('function testTurnstileSecretKey'), 'testTurnstileSecretKey must be defined.');
assert(turnstilePhp.includes('function checkUploadTurnstilePermission'), 'checkUploadTurnstilePermission must be defined.');
assert(turnstilePhp.includes('function renderTurnstileScript'), 'renderTurnstileScript must be defined.');
assert(turnstilePhp.includes('function renderTurnstileWidget'), 'renderTurnstileWidget must be defined.');
assert(turnstilePhp.includes('challenges.cloudflare.com/turnstile/v0/siteverify'), 'siteverify endpoint must be targeted.');

// 2. Verify config/schema.php defaults
const schemaPhp = readFileSync(resolve(projectRoot, 'config/schema.php'), 'utf8');
assert(schemaPhp.includes("'turnstile_enabled' => ['false'"), 'turnstile_enabled must default to false for fallback.');
assert(schemaPhp.includes("'turnstile_site_key'"), 'turnstile_site_key must be configured in schema.');
assert(schemaPhp.includes("'turnstile_secret_key'"), 'turnstile_secret_key must be configured in schema.');

// 3. Verify admin/login.php
const loginPhp = readFileSync(resolve(projectRoot, 'admin/login.php'), 'utf8');
assert(loginPhp.includes("require_once '../config/turnstile.php'"), 'login.php must require turnstile.php.');
assert(loginPhp.includes("isTurnstileEnabled($pdo, 'login')"), 'login.php must check isTurnstileEnabled for login.');
assert(loginPhp.includes("verifyTurnstileToken"), 'login.php must verify token.');
assert(loginPhp.includes("cf-turnstile"), 'login.php must include turnstile widget.');

// 4. Verify admin/settings.php and static/js/settings.js
const settingsPhp = readFileSync(resolve(projectRoot, 'admin/settings.php'), 'utf8');
assert(settingsPhp.includes("case 'test_turnstile':"), 'settings.php must handle test_turnstile AJAX action.');
assert(settingsPhp.includes("'turnstile_enabled'"), 'settings.php must render turnstile_enabled setting.');
assert(settingsPhp.includes("test-turnstile-btn"), 'settings.php must have test-turnstile-btn.');

const settingsJs = readFileSync(resolve(projectRoot, 'static/js/settings.js'), 'utf8');
assert(settingsJs.includes("test-turnstile-btn"), 'settings.js must bind test-turnstile-btn.');
assert(settingsJs.includes("test_turnstile"), 'settings.js must call test_turnstile.');

// 5. Verify backend upload endpoints
const apiPhp = readFileSync(resolve(projectRoot, 'api.php'), 'utf8');
assert(apiPhp.includes("require_once 'config/turnstile.php'"), 'api.php must require turnstile.php.');
assert(apiPhp.includes("checkUploadTurnstilePermission"), 'api.php must call checkUploadTurnstilePermission.');

const videoPhp = readFileSync(resolve(projectRoot, 'video.php'), 'utf8');
assert(videoPhp.includes("checkUploadTurnstilePermission"), 'video.php must call checkUploadTurnstilePermission.');

const audioPhp = readFileSync(resolve(projectRoot, 'audio.php'), 'utf8');
assert(audioPhp.includes("checkUploadTurnstilePermission"), 'audio.php must call checkUploadTurnstilePermission.');

const apiFilePhp = readFileSync(resolve(projectRoot, 'api_file.php'), 'utf8');
assert(apiFilePhp.includes("checkUploadTurnstilePermission"), 'api_file.php must call checkUploadTurnstilePermission.');

// 6. Verify frontend upload centers
const indexPhp = readFileSync(resolve(projectRoot, 'index.php'), 'utf8');
assert(indexPhp.includes("renderTurnstileScript($pdo, 'upload')"), 'index.php must render turnstile script.');
assert(indexPhp.includes("renderTurnstileWidget"), 'index.php must render turnstile widget.');
assert(indexPhp.includes("cf-turnstile-response"), 'index.php must send cf-turnstile-response.');

const uploadImagePhp = readFileSync(resolve(projectRoot, 'upload_image.php'), 'utf8');
assert(uploadImagePhp.includes("renderTurnstileScript($pdo, 'upload')"), 'upload_image.php must render turnstile script.');
assert(uploadImagePhp.includes("renderTurnstileWidget"), 'upload_image.php must render turnstile widget.');

const utilsJs = readFileSync(resolve(projectRoot, 'static/js/upload/utils.js'), 'utf8');
assert(utilsJs.includes("cf-turnstile-response"), 'utils.js must support cf-turnstile-response.');
assert(utilsJs.includes("turnstile.reset"), 'utils.js must reset turnstile on completion.');

const videoAppJs = readFileSync(resolve(projectRoot, 'static/js/video_app.js'), 'utf8');
assert(videoAppJs.includes("cf-turnstile-response"), 'video_app.js must support cf-turnstile-response.');

const fileAppJs = readFileSync(resolve(projectRoot, 'static/js/file_app.js'), 'utf8');
assert(fileAppJs.includes("cf-turnstile-response"), 'file_app.js must support cf-turnstile-response.');

const audioAppJs = readFileSync(resolve(projectRoot, 'static/js/audio_app.js'), 'utf8');
assert(audioAppJs.includes("cf-turnstile-response"), 'audio_app.js must support cf-turnstile-response.');

console.log('✅ All Turnstile integration contract tests passed cleanly!');
