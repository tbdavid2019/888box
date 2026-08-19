<?php
require_once __DIR__ . '/database.php';

/**
 * Render Theme Styles block dynamically
 * @param PDO $pdo
 */
function renderThemeStyles($pdo) {
    $themeConfig = require __DIR__ . '/themes.php';
    $activePreset = Database::getConfig($pdo, 'active_theme') ?: $themeConfig['active_theme'];
    $themes = $themeConfig['presets'];
    $theme = $themes[$activePreset] ?? $themes['default'];
    if (!empty($theme['alias_of']) && isset($themes[$theme['alias_of']])) {
        $theme = $themes[$theme['alias_of']];
    }
    
    ?>
    <style>
    :root {
        color-scheme: <?= ($theme['mode'] ?? 'dark') === 'light' ? 'light' : 'dark' ?>;
        --bg-dark: <?= $theme['bg_base'] ?> !important;
        --bg-color: <?= $theme['bg_base'] ?> !important;
        --bg-elevated: <?= $theme['bg_elevated'] ?> !important;
        --surface-bg: <?= $theme['bg_surface'] ?> !important;
        --surface-elevated: <?= $theme['bg_elevated'] ?> !important;
        --surface-deep: <?= $theme['bg_deep'] ?> !important;
        --panel-bg: <?= $theme['panel_bg'] ?> !important;
        --primary: <?= $theme['button'] ?> !important;
        --primary-color: <?= $theme['button'] ?> !important;
        --primary-hover: <?= $theme['highlight'] ?> !important;
        --accent-color: <?= $theme['highlight'] ?> !important;
        --accent: <?= $theme['highlight'] ?> !important;
        --accent-blue: <?= $theme['button'] ?> !important;
        --accent-cyan: <?= $theme['accent'] ?> !important;
        --accent-purple: <?= $theme['highlight'] ?> !important;
        --accent-orange: <?= $theme['warning'] ?> !important;
        --accent-green: <?= $theme['success'] ?> !important;
        --text-main: <?= $theme['content'] ?> !important;
        --text-primary: <?= $theme['content'] ?> !important;
        --text-white: <?= $theme['content'] ?> !important;
        --text-light: <?= $theme['muted'] ?> !important;
        --text-secondary: <?= $theme['muted'] ?> !important;
        --text-muted: <?= $theme['muted'] ?> !important;
        --text-gray: <?= $theme['muted'] ?> !important;
        --text-placeholder: <?= $theme['muted'] ?> !important;
        --border: <?= $theme['border_color'] ?> !important;
        --border-white-20: <?= $theme['border_soft'] ?> !important;
        --border-white-30: <?= $theme['border_soft'] ?> !important;
        --border-white-40: <?= $theme['border_color'] ?> !important;
        --border-dashed: <?= $theme['border_color'] ?> !important;
        --card-bg: <?= $theme['card_bg'] ?> !important;
        --card-hover-bg: <?= $theme['card_hover_bg'] ?> !important;
        --card-border: <?= $theme['border_soft'] ?> !important;
        --card-border-strong: <?= $theme['border_color'] ?> !important;
        --shadow-color: <?= $theme['shadow_color'] ?> !important;
        --bg-white-10: <?= $theme['overlay_soft'] ?> !important;
        --bg-white-15: <?= $theme['overlay_soft'] ?> !important;
        --bg-white-20: <?= $theme['overlay_mid'] ?> !important;
        --bg-white-25: <?= $theme['overlay_mid'] ?> !important;
        --bg-white-40: <?= $theme['overlay_mid'] ?> !important;
        --bg-black-20: <?= $theme['overlay_deep'] ?> !important;
        --bg-black-30: <?= $theme['overlay_deep'] ?> !important;
        --bg-black-50: <?= $theme['overlay_deep'] ?> !important;
        --bg-black-60: <?= $theme['overlay_deep'] ?> !important;
        --bg-black-70: <?= $theme['overlay_deep'] ?> !important;
        --gradient-glass: <?= $theme['panel_bg'] ?> !important;
        --gradient-glass-light: <?= $theme['panel_bg'] ?> !important;
        --gradient-progress: linear-gradient(90deg, <?= $theme['button'] ?> 0%, <?= $theme['accent'] ?> 50%, <?= $theme['highlight'] ?> 100%) !important;
        --link-color: <?= $theme['button'] ?> !important;
        --link-hover: <?= $theme['highlight'] ?> !important;
        --success-color: <?= $theme['success'] ?> !important;
        --warning-color: <?= $theme['warning'] ?> !important;
        --danger-color: <?= $theme['danger'] ?> !important;
        --error-color: <?= $theme['danger'] ?> !important;
    }
    
    body {
        background: <?= $theme['bg_gradient'] ?> !important;
        color: <?= $theme['content'] ?> !important;
    }
    
    h1, h2, h3, h4, h5, h6, 
    .video-header h1, .header h1, .asset-title, 
    .card-title, .gallery-item .info-p span {
        color: <?= $theme['title'] ?> !important;
    }
    
    .btn.primary, .submit-btn, .btn-download, .password-gate button, .btn-copy, .btn-edit {
        background: <?= $theme['button'] ?> !important;
        color: <?= $theme['button_text'] ?> !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2) !important;
        border: none !important;
    }
    .login-container button {
        background: <?= $theme['button'] ?> !important;
        border-color: <?= $theme['button'] ?> !important;
        color: <?= $theme['button_text'] ?> !important;
    }
    
    .btn.primary:hover, .submit-btn:hover, .btn-download:hover, .password-gate button:hover, .btn-copy:hover, .btn-edit:hover {
        background: <?= $theme['highlight'] ?> !important;
        color: <?= $theme['highlight_text'] ?> !important;
        opacity: 0.95 !important;
        transform: translateY(-1px) !important;
    }
    .login-container button:hover {
        background: <?= $theme['highlight'] ?> !important;
        border-color: <?= $theme['highlight'] ?> !important;
        color: <?= $theme['highlight_text'] ?> !important;
    }

    .btn.secondary {
        border: 1px solid <?= $theme['border_color'] ?> !important;
        color: <?= $theme['content'] ?> !important;
    }
    .btn.secondary:hover {
        background: rgba(255, 255, 255, 0.1) !important;
    }
    
    .upload-panel, .result-panel, .history-panel, .view-container, .modal-content, .card {
        background: <?= $theme['panel_bg'] ?> !important;
        border-color: <?= $theme['border_color'] ?> !important;
    }

    .login-container,
    .admin-header,
    .admin-footer,
    .asset-card,
    .gallery,
    .sidebar,
    .settings-panel {
        background: <?= $theme['panel_bg'] ?> !important;
        border-color: <?= $theme['border_color'] ?> !important;
        color: <?= $theme['content'] ?> !important;
    }
    
    p, span, label, td, th, div.video-info, div.file-info p {
        color: <?= $theme['content'] ?> !important;
    }

    a {
        color: <?= $theme['highlight'] ?>;
    }
    a:hover {
        color: <?= $theme['title'] ?> !important;
    }

    .stats-badge {
        background: rgba(255, 255, 255, 0.08) !important;
        border: 1px solid <?= $theme['border_color'] ?> !important;
        color: <?= $theme['content'] ?> !important;
    }
    
    input[type="text"], input[type="password"], textarea, select {
        border-color: <?= $theme['border_color'] ?> !important;
        color: <?= $theme['content'] ?> !important;
        background: <?= $theme['overlay_deep'] ?> !important;
    }

    .box-language-switcher {
        position: fixed;
        top: max(16px, env(safe-area-inset-top));
        right: max(18px, env(safe-area-inset-right));
        z-index: 2000;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px;
        border: 1px solid <?= $theme['border_color'] ?>;
        border-radius: 999px;
        background: <?= $theme['panel_bg'] ?>;
        box-shadow: 0 10px 28px rgba(0, 0, 0, 0.24);
        backdrop-filter: blur(14px);
    }
    .box-language-switcher-inline {
        position: static;
        top: auto;
        right: auto;
        z-index: auto;
        box-shadow: none;
        backdrop-filter: none;
    }
    .box-language-label {
        padding: 0 5px 0 8px;
        color: <?= $theme['muted'] ?> !important;
        font-size: 0.72rem;
        font-weight: 700;
    }
    .box-language-switcher button {
        min-width: 32px;
        min-height: 30px;
        padding: 4px 8px;
        border: 0;
        border-radius: 999px;
        background: transparent;
        color: <?= $theme['content'] ?> !important;
        cursor: pointer;
        font: inherit;
        font-size: 0.78rem;
        font-weight: 800;
        transition: background 160ms ease, color 160ms ease, transform 160ms ease;
    }
    .box-language-switcher button:hover,
    .box-language-switcher button:focus-visible {
        background: <?= $theme['overlay_mid'] ?>;
        outline: none;
    }
    .box-language-switcher button.is-active {
        background: <?= $theme['accent'] ?>;
        color: #111827 !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.18);
    }
    .box-site-header {
        position: relative;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        width: 100vw;
        margin-top: -20px;
        margin-left: calc(50% - 50vw);
        margin-right: calc(50% - 50vw);
        min-height: 64px;
        box-sizing: border-box;
        padding: 10px clamp(18px, 4vw, 48px);
        color: <?= $theme['content'] ?>;
        background: rgba(15, 18, 32, 0.94);
        border-bottom: 1px solid <?= $theme['border_color'] ?>;
        box-shadow: 0 10px 28px rgba(0, 0, 0, 0.22);
    }
    .box-site-header-left,
    .box-site-section-nav,
    .box-site-header-actions {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }
    .box-site-section-nav {
        gap: 4px;
        flex: 1 1 auto;
        justify-content: center;
    }
    .box-site-section-link {
        display: inline-flex;
        align-items: center;
        min-height: 32px;
        padding: 6px 10px;
        border: 1px solid transparent;
        border-radius: 7px;
        color: <?= $theme['muted'] ?> !important;
        text-decoration: none;
        font-size: 0.82rem;
        font-weight: 700;
        white-space: nowrap;
        transition: background 160ms ease, border-color 160ms ease, color 160ms ease;
    }
    .box-site-section-link:hover,
    .box-site-section-link.is-active {
        color: <?= $theme['content'] ?> !important;
        background: rgba(122, 162, 247, 0.16);
        border-color: rgba(125, 207, 255, 0.46);
    }
    .box-site-header-actions {
        justify-content: flex-end;
        flex-wrap: wrap;
    }
    .box-site-brand,
    .box-site-header-action {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 34px;
        box-sizing: border-box;
        color: <?= $theme['content'] ?> !important;
        text-decoration: none;
        font-size: 0.98rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .box-site-brand {
        color: <?= $theme['title'] ?> !important;
        letter-spacing: 0.01em;
    }
    .box-site-brand svg,
    .box-site-header-action svg {
        width: 16px;
        height: 16px;
    }
    .box-site-separator {
        color: <?= $theme['muted'] ?>;
        font-size: 0.85rem;
    }
    .box-site-context {
        color: <?= $theme['muted'] ?> !important;
        font-size: 0.95rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .box-site-header-action {
        min-height: 36px;
        padding: 8px 13px;
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.04);
        transition: background 160ms ease, border-color 160ms ease, color 160ms ease, transform 160ms ease;
    }
    .box-site-header-action:hover,
    .box-site-header-action:focus-visible {
        color: #ffffff !important;
        background: rgba(125, 207, 255, 0.14);
        border-color: rgba(125, 207, 255, 0.58);
        transform: translateY(-1px);
    }
    .box-site-header + .video-main,
    .box-site-header + main,
    .box-site-header + .admin-header {
        margin-top: 24px;
    }
    .box-site-header + .admin-header {
        margin-top: 0;
    }
    .box-site-header + .login-container {
        margin-top: 88px;
    }
    .admin-login-page .box-site-header {
        margin-top: 0;
    }
    @media (max-width: 640px) {
        .box-language-switcher {
            top: max(10px, env(safe-area-inset-top));
            right: max(10px, env(safe-area-inset-right));
        }
        .box-language-label {
            display: none;
        }
        .box-site-header {
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 8px 12px;
            width: 100vw;
            margin-top: -20px;
            margin-left: calc(50% - 50vw);
            margin-right: calc(50% - 50vw);
            padding: 10px max(14px, env(safe-area-inset-right)) 10px max(14px, env(safe-area-inset-left));
        }
    .box-site-header-left,
        .box-site-section-nav,
        .box-site-header-actions {
            flex-wrap: wrap;
        }
        .box-site-header-action {
            padding-inline: 10px;
        }
    }
    </style>
    <!-- WebMCP Agent Interface (Chrome 146+ document.modelContext / Cloudflare WebMCP) -->
    <script type="module" src="/static/js/webmcp.js" data-mcp-url="/mcp.php"></script>
    <?php
}

/**
 * Render the shared Traditional Chinese / English language control.
 * The preference is stored in the browser so it follows the user across UI pages.
 */
function renderLanguageSwitcher($inline = false) {
    ?>
    <div class="box-language-switcher<?= $inline ? ' box-language-switcher-inline' : '' ?>" role="group" aria-label="語言" data-i18n-aria="languageLabel">
        <span class="box-language-label" aria-hidden="true" data-i18n="languageLabel">語言</span>
        <button type="button" data-language="zh-Hant" aria-pressed="true">繁</button>
        <button type="button" data-language="en" aria-pressed="false">EN</button>
    </div>
    <?php
}

/**
 * Render the shared top header used by every non-home page.
 * @param string $context Current center or admin section label.
 * @param array<int, array<string, string>> $actions Header links/buttons.
 */
function renderSiteHeader($context, $actions = [], $activeSection = null) {
    $sections = [
        'image' => ['label' => '圖片', 'href' => '/upload_image.php'],
        'video' => ['label' => '影片', 'href' => '/upload_video.php'],
        'audio' => ['label' => '音訊', 'href' => '/upload_audio.php'],
        'file' => ['label' => '文件', 'href' => '/upload_file.php'],
    ];
    ?>
    <header class="box-site-header">
        <div class="box-site-header-left">
            <a href="/" class="box-site-brand" aria-label="888 BOX">
                <i data-lucide="box"></i>
                <span data-i18n="brandName">888 BOX</span>
            </a>
            <span class="box-site-separator" aria-hidden="true">/</span>
            <span class="box-site-context" data-i18n><?= htmlspecialchars($context, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <?php if ($activeSection !== false): ?>
            <nav class="box-site-section-nav" aria-label="管理分類">
                <?php foreach ($sections as $key => $section): ?>
                    <a href="<?= htmlspecialchars($section['href'], ENT_QUOTES, 'UTF-8') ?>" class="box-site-section-link<?= $activeSection === $key ? ' is-active' : '' ?>"<?= $activeSection === $key ? ' aria-current="page"' : '' ?>>
                        <span data-i18n><?= htmlspecialchars($section['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
        <div class="box-site-header-actions">
            <?php renderLanguageSwitcher(true); ?>
            <?php foreach ($actions as $action): ?>
                <?php
                $label = $action['label'] ?? '';
                $icon = $action['icon'] ?? '';
                $actionClass = trim('box-site-header-action ' . ($action['class'] ?? ''));
                ?>
                <?php if (($action['type'] ?? 'link') === 'button'): ?>
                    <button type="button" class="<?= htmlspecialchars($actionClass, ENT_QUOTES, 'UTF-8') ?>" onclick="<?= htmlspecialchars($action['onclick'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($icon): ?><i data-lucide="<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>"></i><?php endif; ?>
                        <span data-i18n><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                    </button>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($action['href'] ?? '#', ENT_QUOTES, 'UTF-8') ?>" class="<?= htmlspecialchars($actionClass, ENT_QUOTES, 'UTF-8') ?>"<?= !empty($action['onclick']) ? ' onclick="' . htmlspecialchars($action['onclick'], ENT_QUOTES, 'UTF-8') . '"' : '' ?><?= !empty($action['target']) ? ' target="' . htmlspecialchars($action['target'], ENT_QUOTES, 'UTF-8') . '" rel="noopener noreferrer"' : '' ?>>
                        <?php if ($icon): ?><i data-lucide="<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>"></i><?php endif; ?>
                        <span data-i18n><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </header>
    <?php
}

/**
 * Load the page-independent i18n runtime. Visible text is translated through
 * exact text mappings and optional data-i18n attributes, including dynamically
 * inserted upload/admin content.
 */
function renderI18nAssets($scope = 'common') {
    ?>
    <script>window.BOX_I18N_SCOPE = <?= json_encode($scope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
    <script src="/static/js/i18n.js?v=3"></script>
    <?php
}
