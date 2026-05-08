# AGENTS.md

This file guides coding agents working in `888box`.

## Scope

- Applies to the repository root.
- Follow this file before making assumptions based on generic PHP project norms.

## Project Shape

- This is a lightweight PHP application with vanilla JavaScript and static CSS.
- The product has evolved into a unified asset platform for images, videos, and files.
- The public portal lives at `index.php` and routes users to dedicated upload centers.
- The main backend gateway is `api.php`; older entrypoints still exist but several are explicitly marked deprecated.
- Admin UIs live under `admin/` and are split by asset type.
- Shared backend logic lives under `config/`.
- Frontend assets live under `static/`.
- Runtime data lives under `storage/`, including the SQLite database and generated podcast files.
- Composer dependencies are installed in `vendor/`.
- `package.json` is release metadata only; there is no Node-based app build.

## Important Files

- `README.md`, `composer.json`, `package.json`, `docker-compose.yml`, `Dockerfile`: setup, runtime expectations, release metadata, and container behavior.
- `install.sh`: current recommended installation flow; interactive Docker-first bootstrap that creates `.env`, prepares `storage/`, boots containers, and seeds the admin user.
- `index.php`: Bento-style portal for the three asset centers.
- `api.php`: unified API for upload, list, search, stats, delete, and remote URL ingestion.
- `upload_image.php`, `upload_video.php`, `upload_file.php`: public upload UIs; all are marked deprecated in favor of `api.php?action=upload`, but they are still active entrypoints and must remain compatible.
- `api_file.php`: deprecated document upload API kept for compatibility.
- `mcp.php` and `skill.php`: AI agent integration surfaces; `mcp.php` exposes MCP tools and `skill.php` renders dynamic markdown instructions with base URL and token hints.
- `view.php`: unified viewer/share page for stored assets.
- `config/database.php`: SQLite connection singleton, `.env` loading, install redirect, migration redirect, and config helpers.
- `config/upload.php`, `config/storage.php`, `config/delete.php`: upload validation/processing, storage abstraction, and deletion helpers.
- `config/video_logic.php` and `config/video_helper.php`: video metadata extraction, thumbnail generation, podcast RSS rebuilds, and related filesystem locking.
- `admin/index.php`, `admin/video.php`, `admin/file.php`, `admin/settings.php`: image, video, file, and settings management screens.
- `static/js/main.js`, `static/js/upload/handler.js`, `static/js/upload/utils.js`: image upload workflow and shared upload UI helpers.
- `static/js/video_app.js` and `static/js/file_app.js`: dedicated video/file upload center behavior.

## Setup / Install

- Install PHP dependencies: `composer install`
- Recommended project bootstrap: `./install.sh`
- Start containers: `docker compose up -d`
- Stop containers: `docker compose down`
- Rebuild when Docker image inputs change: `docker compose up -d --build`
- Validate Docker config before debugging containers: `docker compose config`

## Local Run

- The documented primary workflow is Docker.
- `docker-compose.yml` exposes the app on port `6767`.
- The install flow is no longer only `/install` in a browser; `install.sh` is the current preferred bootstrap path.
- A traditional PHP server can still be used for quick checks, for example `php -S 127.0.0.1:8000`, but that is a fallback workflow.

## Build / Release

- There is no frontend bundler, transpiler, formatter, or asset pipeline.
- GitHub Actions reads the version from `package.json`.
- Docker image publishing is handled by GitHub Actions rather than a local release script.

## Lint / Typecheck / Tests

- No first-party lint command exists.
- No first-party formatter command exists.
- No first-party typecheck command exists.
- No first-party automated test suite exists.
- Do not invent `npm test`, `npm run lint`, `phpunit`, `vitest`, or `jest` for this repo.
- Vendor packages contain upstream tests, but those are not repository validation.

## Verification

- If you changed PHP, run `php -l` on every changed PHP file.
- If you changed Docker-related files, run `docker compose config`.
- If you changed public upload behavior, do a manual upload smoke test through the affected UI or `api.php`.
- If you changed admin behavior, load the relevant admin page and exercise the changed interaction.
- If you changed video or podcast logic, verify the generated `storage/podcast.xml` path or the relevant rebuild flow when feasible.
- If you changed agent integration, manually inspect the output of `skill.php` or the relevant `mcp.php` request path when feasible.
- If no automated test/lint command exists, say so explicitly in the final report.
- Mention any validation you could not perform.

## Working Rules

- Never edit `vendor/` unless the user explicitly asks for a dependency patch.
- Treat `vendor/` as third-party code.
- Avoid adding new build tools, test frameworks, or lint configs unless the user explicitly requests them.
- Keep changes small and consistent with the existing lightweight architecture.
- Be careful with `.env`; it is generated during install and may contain secrets.
- Do not commit secrets, tokens, or environment-specific data.
- Preserve compatibility with the current Docker-first deployment model unless the user asks to change it.
- Respect deprecated entrypoints that are still live; do not remove or break them casually.

## Backend Conventions

- Use `require_once` near the top of PHP entrypoints and helpers.
- Load Composer autoload with `vendor/autoload.php` or `__DIR__ . '/../vendor/autoload.php'` as appropriate.
- Place `use ...;` imports after `require_once` lines.
- Entry-point files are mostly procedural; follow that style unless a helper is already class-based.
- Shared infrastructure uses small utility classes such as `Database` and `StorageHelper`.
- Use `PascalCase` for classes and `camelCase` for functions.
- Follow surrounding variable style; most code is `camelCase`, but legacy locals may use snake_case.
- Use PDO prepared statements for database reads and writes that accept input.
- Access the shared connection through `Database::getInstance()->getConnection()`.
- Access config through `Database::getConfig($pdo, $key = null)`.
- The app still stores multiple asset types in the `images` table; do not assume the table name reflects only images.

## PHP Formatting

- Indent with 4 spaces.
- Opening braces stay on the same line for classes, functions, conditionals, and loops.
- Keep a space after control keywords like `if (...)`, `foreach (...)`, and `catch (...)`.
- Short early returns are common.
- Use short arrays `[]`.
- Inline comments are brief and often in Chinese when they explain user-facing behavior.
- Preserve section-divider comments like `// ============================================` when editing within those files.

## PHP Error Handling

- Wrap top-level JSON/storage/database flows in `try/catch`.
- For JSON endpoints, return structured JSON rather than raw text.
- Reuse `respondAndExit()` in `api.php` where relevant instead of inventing a different response shape.
- Prefer explicit user-facing error messages.
- Do not add silent failures unless the surrounding code intentionally tolerates them.
- Some legacy files contain empty catches for best-effort migrations or compatibility; do not spread that pattern into new logic without a strong reason.

## Frontend Conventions

- Frontend JS uses native ES modules in the shared upload flow.
- Use `PascalCase` for classes, `camelCase` for functions/variables, and `SCREAMING_SNAKE_CASE` only for true constants/globals.
- Cache DOM references during initialization instead of repeatedly querying.
- Use semicolons consistently.
- Prefer single quotes unless interpolation makes another form clearer.
- Prefer `async/await` around `fetch` flows.
- XHR is still used where upload progress is required; keep that pattern where progress events matter.
- Some newer pages use inline scripts instead of modules; match the surrounding file rather than forcing a rewrite.

## Frontend Error Handling

- Show user-visible failures with `UI.showNotification(message, 'error')` where that shared UI helper exists.
- In pages without the shared helper, follow the local pattern already in that file.
- Log unexpected failures with `console.error(...)` when useful.
- Keep notifications concise and action-oriented.
- Preserve cleanup behavior for `FileReader`, `XMLHttpRequest`, object URLs, temporary previews, and timers.

## Templates and HTML

- PHP templates mix HTML and PHP directly.
- Shorthand echo tags `<?= ... ?>` are common and preferred in templates.
- Escape dynamic HTML attributes/content with `htmlspecialchars(...)` where appropriate.
- Heredoc is used in some rendering helpers and is acceptable for larger HTML fragments.
- Keep admin markup server-rendered unless the surrounding file already replaces sections through AJAX.

## Storage, Upload, and Data Model

- Supported storage types are `local`, `oss`, `s3`, and `upyun`.
- Route storage operations through `StorageHelper`.
- Route upload validation and response shaping through helpers in `config/upload.php` and the unified API flow in `api.php`.
- Do not bypass `generateFileUrl()`, `validateFile()`, `generateUploadResponse()`, or equivalent shared upload helpers without a reason grounded in existing code.
- Respect existing behavior around daily upload limits, max file size, login restriction, remote URL ingestion, output conversion, and password-protected assets.
- Remember that images, videos, and files currently share storage in the same main table and are often distinguished by extension or MIME type.

## Video and Podcast Logic

- Video uploads are surfaced through `upload_video.php` and managed in `admin/video.php`.
- Core logic resides in `config/video_logic.php` and `config/video_helper.php`.
- The system expects `ffmpeg` and `ffprobe` in `PATH` inside the runtime environment.
- Podcast artifacts are generated under `storage/`, especially `storage/podcast.xml`.
- `flock()`-based locking is used to protect podcast and daily video JSON writes; preserve that concurrency behavior.
- Password-protected videos have special behavior in RSS exposure; do not break that assumption.

## AI Agent Integration

- `skill.php` is a user-facing markdown capability document for external agents.
- `mcp.php` implements an MCP server over stdio and exposes tool-style operations backed by the app.
- Changes here affect automation clients directly, so keep request/response shapes stable unless the user explicitly asks for a breaking change.
- When editing these surfaces, verify auth assumptions around session, token, and base URL generation.

## What Not To Assume

- Do not assume Node tooling exists because `package.json` is present.
- Do not assume there is a PHPUnit suite for app code.
- Do not assume `/install` browser flow is the only supported initialization path.
- Do not assume legacy upload endpoints can be removed just because they are marked deprecated.
- Do not assume the `images` table only contains images.
- Do not assume there is a framework router, ORM, template engine, or frontend bundler.

## Good Defaults

- Match the surrounding file before introducing a new pattern.
- Prefer minimal fixes over architectural rewrites.
- Keep backend responses compatible with existing JSON shapes.
- Preserve compatibility across portal, upload center, admin, and agent-facing flows.
- When in doubt, follow the conventions in `api.php`, `config/upload.php`, `config/storage.php`, `config/database.php`, `index.php`, `admin/index.php`, and the relevant asset-specific UI file you are touching.
