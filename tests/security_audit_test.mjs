import { readFileSync, existsSync } from 'node:fs';
import { resolve } from 'node:path';
import process from 'node:process';

const projectRoot = resolve(import.meta.dirname, '..');

function assert(condition, message) {
    if (!condition) {
        console.error('❌ ' + message);
        process.exit(1);
    }
}

// 1. Check config/security.php exists and contains SSRF protections
assert(existsSync(resolve(projectRoot, 'config/security.php')), 'config/security.php must exist.');
const securityHelper = readFileSync(resolve(projectRoot, 'config/security.php'), 'utf8');
assert(securityHelper.includes('function validateSafeRemoteUrl'), 'validateSafeRemoteUrl must be defined.');
assert(securityHelper.includes('function isPrivateOrReservedIp'), 'isPrivateOrReservedIp must be defined.');
assert(securityHelper.includes('applySafeCurlOptions'), 'applySafeCurlOptions must be defined.');
assert(securityHelper.includes('169.254.0.0'), 'Cloud metadata IP 169.254.x.x must be blocked.');
assert(securityHelper.includes('127.0.0.0'), 'Loopback IP 127.x.x.x must be blocked.');
assert(securityHelper.includes('10.0.0.0'), 'Private IP 10.x.x.x must be blocked.');

// 2. Check api.php and mcp.php for backdoor removal and SSRF integration
const apiPhp = readFileSync(resolve(projectRoot, 'api.php'), 'utf8');
const mcpPhp = readFileSync(resolve(projectRoot, 'mcp.php'), 'utf8');
assert(!apiPhp.includes("'ai_agent'"), "api.php must not contain hardcoded backdoor token 'ai_agent'.");
assert(!mcpPhp.includes("'ai_agent'"), "mcp.php must not contain hardcoded backdoor token 'ai_agent'.");
assert(apiPhp.includes('validateSafeRemoteUrl'), 'api.php must validate remote URLs with validateSafeRemoteUrl.');
assert(mcpPhp.includes('validateSafeRemoteUrl'), 'mcp.php must validate remote URLs with validateSafeRemoteUrl.');
assert(apiPhp.includes('applySafeCurlOptions'), 'api.php must apply safe cURL options.');
assert(mcpPhp.includes('applySafeCurlOptions'), 'mcp.php must apply safe cURL options.');

// 3. Check api_file.php strict extension whitelist
const apiFilePhp = readFileSync(resolve(projectRoot, 'api_file.php'), 'utf8');
assert(apiFilePhp.includes('$allowedDocs'), 'api_file.php must define an allowed document extension whitelist.');
assert(apiFilePhp.includes('$dangerousExtensions'), 'api_file.php must define dangerous extension blacklist.');
assert(
    !apiFilePhp.includes("strpos($mimeType, 'application/') === false && strpos($mimeType, 'text/') === false"),
    'api_file.php must not allow arbitrary files solely based on text/ or application/ MIME prefix.'
);

// 4. Check storage/.htaccess script execution denial
const storageHtaccess = readFileSync(resolve(projectRoot, 'storage/.htaccess'), 'utf8');
assert(storageHtaccess.includes('Require all denied'), 'storage/.htaccess must deny execution of script files.');
assert(storageHtaccess.includes('php_flag engine off'), 'storage/.htaccess must disable PHP engine.');
assert(storageHtaccess.includes('sqlite'), 'storage/.htaccess must protect database files.');

// 5. Check root .htaccess sensitive file protection
const rootHtaccess = readFileSync(resolve(projectRoot, '.htaccess'), 'utf8');
assert(rootHtaccess.includes('composer'), 'root .htaccess must block access to composer files.');
assert(rootHtaccess.includes('Dockerfile'), 'root .htaccess must block access to Dockerfile.');
assert(rootHtaccess.includes('sh'), 'root .htaccess must block shell scripts.');
assert(rootHtaccess.includes('X-Content-Type-Options') && rootHtaccess.includes('nosniff'), 'root .htaccess must set X-Content-Type-Options: nosniff.');

// 6. Check view.php token validation and XSS escaping
const viewPhp = readFileSync(resolve(projectRoot, 'view.php'), 'utf8');
assert(viewPhp.includes("preg_match('/^[a-fA-F0-9]{6,32}$/', $token)"), 'view.php must strictly validate token format.');
assert(viewPhp.includes("htmlspecialchars($customTitle ?: 'image', ENT_QUOTES, 'UTF-8')"), 'view.php must escape alt in HTML embed template.');

// 7. Check admin session regeneration and cache control
const adminLogin = readFileSync(resolve(projectRoot, 'admin/login.php'), 'utf8');
assert(adminLogin.includes('session_regenerate_id(true)'), 'admin/login.php must regenerate session ID on successful login.');
const adminIndex = readFileSync(resolve(projectRoot, 'admin/index.php'), 'utf8');
assert(!adminIndex.includes('max-age=10800'), 'admin/index.php must not cache admin pages with public max-age.');
assert(adminIndex.includes('no-store'), 'admin/index.php must set Cache-Control: no-store.');

// 8. Check StorageHelper::delete directory traversal defense
const storageHelperCode = readFileSync(resolve(projectRoot, 'config/storage.php'), 'utf8');
assert(storageHelperCode.includes('storageRoot'), 'StorageHelper::delete must verify path stays within storageRoot.');
assert(storageHelperCode.includes('str_starts_with($realTarget, $storageRoot)'), 'StorageHelper::delete must enforce prefix validation.');

// 9. Check install.sh permissions
const installSh = readFileSync(resolve(projectRoot, 'install.sh'), 'utf8');
assert(installSh.includes('chmod 600 .env'), 'install.sh must set .env permissions to 600.');
assert(installSh.includes('INIT_ADMIN_PASS'), 'install.sh must pass admin credentials safely via env.');

console.log('✅ Security audit regression test suite passed cleanly.');
