# AGENTS.md

This file guides coding agents working in `PixPro`.

## Scope

- Applies to the repository root.
- There was no existing root `AGENTS.md` when this file was written.
- No Cursor rules were found in `.cursor/rules/`.
- No `.cursorrules` file was found.
- No Copilot instructions were found in `.github/copilot-instructions.md`.

## Project Shape

- This is a PHP application with vanilla JavaScript and static CSS.
- `package.json` is metadata only; it does not define scripts or a JS toolchain.
- Backend entrypoints live in root PHP files such as `index.php`, `api.php`, and `migrate.php`.
- Admin UI lives under `admin/`.
- Shared backend logic lives under `config/`.
- Frontend code lives under `static/`.
- Composer dependencies are installed in `vendor/`.

## Important Files

- `README.md`, `composer.json`, and `package.json`: setup, runtime deps, and release version metadata.
- `api.php`, `config/upload.php`, and `config/storage.php`: upload API, validation/compression, and storage abstraction.
- `config/database.php`: SQLite connection singleton plus config access helpers.
- `admin/index.php` and `admin/settings.php`: admin dashboard shell, pagination, settings AJAX, and server-rendered templates.
- `static/js/main.js`, `static/js/upload/handler.js`, and `static/js/upload/utils.js`: upload page bootstrap, workflow class, and shared UI helpers.

## Commands

## Setup / Install

- Install PHP dependencies: `composer install`
- Run with Docker: `docker compose up -d` / `docker compose down`
- Validate Docker config before debugging containers: `docker compose config`

## Local Run

- Preferred runtime is a normal PHP web server or Docker.
- The app is designed to be installed by visiting `/install` in a browser.
- `docker-compose.yml` exposes the container on port `6767`.
- If you need a quick local server for manual checks, a reasonable fallback is `php -S 127.0.0.1:8000`, but this repo does not document that as the official workflow.

## Build / Release

- There is no frontend build step, bundler, transpiler, or asset pipeline.
- GitHub Actions reads the version with `node -p "require('./package.json').version"` in `.github/workflows/docker-build.yml`.
- Docker image publishing is handled by GitHub Actions, not by a local build script.

## Lint / Typecheck / Tests

- No first-party lint command exists.
- No first-party formatter command exists.
- No first-party typecheck command exists.
- No first-party automated test command exists.
- Do not invent `npm test`, `npm run lint`, `phpunit`, `vitest`, or `jest` for this repo.
- Vendor packages contain their own tests, but those are not project tests and should not be treated as repository validation.

## Single-File / Single-Target Verification

- Verify one PHP file for syntax: `php -l path/to/file.php` (for example `php -l api.php`).
- Verify JS changes manually; there is no JS linter in the repo.
- For frontend-only changes, load the affected page and exercise the modified interaction.
- For upload/API changes, do a manual upload through the UI or call the endpoint directly.
- For Docker-related changes, use `docker compose config` before trying to run containers.

## Working Rules

- Never edit `vendor/` unless the user explicitly asks for a dependency patch.
- Treat `vendor/` as third-party code.
- Avoid adding new build tools, test frameworks, or lint configs unless the user explicitly requests them.
- Keep changes small and consistent with the existing lightweight architecture.
- Preserve PHP 7.2 compatibility unless the user asks for a version bump.
- Be careful with `.env`; it is generated during install and may contain secrets.
- Do not commit secrets, tokens, or environment-specific data.

## Backend Conventions

- Use `require_once` near the top of PHP entrypoints and helper files.
- Load Composer autoload with `vendor/autoload.php` or `__DIR__ . '/../vendor/autoload.php'` as appropriate.
- Place `use ...;` imports after `require_once` lines.
- Prefer procedural helper functions in entrypoint-oriented files like `api.php` and `config/upload.php`.
- Prefer small static utility classes for shared infrastructure like `Database` and `StorageHelper`.
- Use `PascalCase` for classes.
- Use `camelCase` for PHP function names.
- Variables are mostly `camelCase`, but some legacy locals use snake_case; follow surrounding file style.
- Use PDO prepared statements for database writes and parameterized reads.
- Access config through `Database::getConfig($pdo, $key = null)` and the shared connection through `Database::getInstance()->getConnection()`.

## PHP Formatting

- Indent with 4 spaces.
- Opening braces stay on the same line for classes, functions, conditionals, and loops.
- Existing files keep a space after control keywords like `if (...)`, `foreach (...)`, and `catch (...)`.
- Short early returns are common and array literals usually use short syntax `[]`.
- Inline comments are brief and usually written in Chinese when they explain user-facing logic.
- Existing files include section-divider comments like `// ============================================`; preserve them if you are editing inside that structure.

## PHP Error Handling

- Wrap top-level execution in `try/catch` when returning JSON or performing storage/database actions.
- For JSON endpoints, return structured JSON rather than raw text; reuse `respondAndExit()` in `api.php` and `logMessage()` in upload flows.
- In settings/admin flows, existing code uses `json_encode([...])` plus HTTP status codes directly.
- Prefer explicit user-facing error messages.
- Do not add silent failures unless the file already intentionally tolerates them.
- There is one legacy empty catch in `static/js/upload/handler.js`; do not copy that pattern into new code.

## Frontend Conventions

- Frontend JS uses native ES modules with top-of-file imports and named exports for reusable modules.
- Use `PascalCase` for classes like `ImageHandler`, `camelCase` for functions/variables, and `SCREAMING_SNAKE_CASE` only for true globals/constants like `CONFIG` and `DOM`.
- Use object literals for shared UI/state helpers such as `PreviewState`, `UI`, `API`, `Clipboard`, and `Navigation`.
- Cache DOM references in one object during initialization rather than querying repeatedly.
- Use semicolons consistently and prefer single quotes unless interpolation makes another form clearer.
- Prefer arrow functions for event handlers and small callbacks, and prefer `async/await` around `fetch` workflows.
- XHR is still used for upload progress; keep that pattern where progress events are required.

## Frontend Error Handling

- Show user-visible failures with `UI.showNotification(message, 'error')`.
- Log unexpected failures with `console.error(...)` when helpful for debugging.
- Keep notifications concise and action-oriented.
- Preserve progressive cleanup behavior for `FileReader`, `XMLHttpRequest`, object URLs, and timers.

## Templates and HTML

- PHP templates mix HTML and PHP directly.
- Shorthand echo tags `<?= ... ?>` are common and preferred in templates.
- Escape dynamic HTML attributes/content with `htmlspecialchars(...)` where appropriate.
- Heredoc is used in some rendering helpers like `renderImagesList()` for larger HTML blocks.
- Keep admin markup server-rendered unless the surrounding file already uses AJAX replacement.

## Storage and Upload Logic

- Supported storage types are `local`, `oss`, `s3`, and `upyun`.
- Route all storage operations through `StorageHelper`.
- Route upload validation/compression/response behavior through helpers in `config/upload.php`.
- Do not bypass `generateFileUrl()`, `validateFile()`, or `generateUploadResponse()` without a good reason.
- Respect existing behavior around daily upload limits, max file size, and output format conversion.

## Validation Checklist For Agents

- If you changed PHP, run `php -l` on every changed PHP file.
- If you changed upload or admin behavior, do a manual browser/API smoke test.
- If you changed Docker config, run `docker compose config`.
- If no automated test/lint command exists, say so explicitly in your final report.
- Mention any validation you could not perform.

## What Not To Assume

- Do not assume Node tooling exists because `package.json` is present.
- Do not assume there is a PHPUnit suite for app code.
- Do not assume there is a REST framework, router, ORM, template engine, or frontend bundler.
- Do not assume modern PHP typing features are acceptable everywhere.

## Good Defaults

- Match the surrounding file before introducing a new pattern.
- Prefer minimal fixes over architectural rewrites.
- Keep backend responses compatible with existing JSON shapes and frontend changes dependency-free unless asked otherwise.
- When in doubt, follow the conventions in `api.php`, `config/upload.php`, `config/storage.php`, `static/js/main.js`, and `static/js/upload/handler.js`.
