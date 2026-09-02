---
name: security-audit
description: Perform comprehensive full-codebase security audits, vulnerability assessments, secret detection, API/SSRF inspection, file upload validation, path traversal defense, and dependency scans for 888box asset management platform.
license: MIT
metadata:
  author: 888box
  version: "1.0"
---

# 888box Security Audit Skill

This skill guides Antigravity agents in conducting structured, full-spectrum defensive security reviews, penetration audits, and remediation for the `888box` unified asset platform (PHP, SQLite, S3/OSS/Upyun storage, MCP/WebMCP integrations).

---

## 🎯 7-Layer Audit Methodology

```
[Layer 1: Secrets & Credentials] ──> [Layer 2: API, SSRF & File Upload] ──> [Layer 3: AI Agents & MCP Server]
             │                                                                      │
[Layer 4: Client, XSS & Auth] ───> [Layer 5: Supply Chain & Packages] ────> [Layer 6: Headers & File Protection]
                                           │
                                [Layer 7: Storage & Cloud Engines]
```

---

## 📋 Step-by-Step Audit Procedure

### 1. 🔑 Secrets & Credentials Exposure
- Scan all tracked code, configs, and shell scripts for hardcoded tokens, fallback bearer keys (e.g. `'ai_agent'`), or database credentials.
- Ensure `.env` permissions are set to `0600` and covered in `.gitignore` and `.htaccess`.
- Verify external storage credentials (AWS S3, Alibaba Cloud OSS, Upyun) are ingested strictly via `.env` or database configuration, never leaked in client payloads or public error messages.

### 2. 🛡️ API Endpoints & Server-Side Security (SSRF, Uploads, SQLi, IDOR, Traversal)
- **SSRF Prevention**: Inspect all remote ingestion endpoints (`api.php?action=upload_url`, `mcp.php -> upload_asset_by_url`). Enforce strict `http/https` whitelist, block private/loopback/link-local/cloud-metadata IP ranges (`127.0.0.0/8`, `10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`, `169.254.169.254`, `::1`, `fc00::/7`), and validate redirect destinations.
- **Strict File Upload Validation**: Enforce rigorous extension whitelists. Reject executable/script extensions (`.php`, `.phtml`, `.phar`, `.sh`, `.cgi`, `.exe`, `.svg`, `.html`) for document uploads.
- **SQL Injection Prevention**: Verify all database reads and writes use PDO prepared statements with parameter binding. Sanitize and validate any dynamic column or `ORDER BY` clauses. Validate LIKE parameters to prevent wildcard (`%`, `_`) token leakage.
- **Path Traversal & Safe Deletion**: Verify file deletion actions (`config/storage.php`, `config/delete.php`, `api_delete_*.php`) resolve targets strictly inside `storage/` and verify asset ownership via database lookup rather than trusting client-supplied paths.
- **Shell Escaping**: Verify `escapeshellarg()` is used without wrapping double quotes (`sprintf('... %s', escapeshellarg($arg))`).

### 3. 🧠 AI Agent & MCP Protocol Security
- **Authentication**: Ensure MCP endpoints (`mcp.php`) require valid database API tokens or active authenticated sessions. Never allow hardcoded bypass tokens.
- **Tool Permissions**: Restrict state-changing operations (`delete_asset`, `rebuild_podcast_rss`) strictly to authorized administrators.
- **CORS Compliance**: When `Access-Control-Allow-Origin` is `*` or `null`, `Access-Control-Allow-Credentials: true` must NOT be sent.

### 4. 💻 Client-Side Security, XSS & Authentication
- **XSS Sanitization**: Ensure dynamic filenames, descriptions, and user inputs rendered into HTML attributes, JavaScript templates, and embed snippets use `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- **Session Management**: Ensure `session_regenerate_id(true)` is executed upon login to prevent session fixation.
- **Admin Panel Cache Control**: Administrative dashboards (`admin/*.php`) must output `Cache-Control: no-store, no-cache, must-revalidate, max-age=0` to prevent browser history disclosure on shared workstations.

### 5. 📦 Supply Chain & Dependency Health
- Audit PHP Composer dependencies in `composer.json` / `composer.lock` for known CVEs.
- Ensure CDN resources (Lucide icons, JS libraries) use HTTPS and reputable endpoints.

### 6. 🌐 Storage Isolation & HTTP Security Headers
- Ensure `storage/.htaccess` explicitly denies script execution (`php_flag engine off`, `<FilesMatch "\.(php|phtml|phar|sh|cgi)"> Deny from all`) and blocks direct downloading of `.db` / `.sqlite` files.
- Verify security headers in root `.htaccess`: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`.
- Block direct web access to sensitive project files (`composer.json`, `package.json`, `Dockerfile`, `docker-compose.yml`, `install.sh`, `*.sh`, `*.log`, `*.env`).

### 7. ☁️ Cloud & Edge Storage Protection
- Ensure private S3/OSS buckets deliver assets through verified CDN domains or authorization proxy (`get_file.php`) rather than exposing raw bucket endpoints.
- Ensure password-protected assets always route through authorization gates before streaming.

---

## 📊 Deliverables & Reporting Format

Every audit run must generate:
1. **Executive Summary**: Health score, summary of audited vectors, and vulnerability distribution.
2. **Findings & Remediation Table**: Categorized by severity (Critical, High, Medium, Low), affected files with clickable links, root cause, and remediation details.
3. **Verification Results**: Test runs and security checks.
