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
        color-scheme: light;
        --brand-saffron: <?= $theme['button'] ?> !important;
        --brand-deep-blue: <?= $theme['accent'] ?> !important;
        --brand-clay: <?= $theme['accent_alt'] ?> !important;
        --brand-warm-cream: <?= $theme['panel_solid'] ?> !important;
        --brand-ink: <?= $theme['content'] ?> !important;
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
        --accent-purple: <?= $theme['accent_alt'] ?> !important;
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
        --link-color: <?= $theme['link'] ?? $theme['accent'] ?> !important;
        --link-hover: <?= $theme['link_hover'] ?? $theme['accent'] ?> !important;
        --success-color: <?= $theme['success'] ?> !important;
        --warning-color: <?= $theme['warning'] ?> !important;
        --danger-color: <?= $theme['danger'] ?> !important;
        --error-color: <?= $theme['danger'] ?> !important;
    }
    
    body {
        background: <?= $theme['bg_gradient'] ?> !important;
        color: <?= $theme['content'] ?> !important;
    }
    body:has(.box-site-header) {
        margin: 0 !important;
        padding-top: 0 !important;
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
        background: <?= $theme['overlay_soft'] ?> !important;
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
        color: <?= $theme['link'] ?? $theme['accent'] ?>;
    }
    a:hover {
        color: <?= $theme['title'] ?> !important;
    }

    .stats-badge {
        background: <?= $theme['overlay_soft'] ?> !important;
        border: 1px solid <?= $theme['border_color'] ?> !important;
        color: <?= $theme['content'] ?> !important;
    }
    
    input[type="text"], input[type="password"], textarea, select {
        border-color: <?= $theme['border_color'] ?> !important;
        color: <?= $theme['content'] ?> !important;
        background: <?= $theme['overlay_deep'] ?> !important;
    }

    :root {
        --nav-height: 60px;
        --nav-control-height: 34px;
        --nav-radius: 8px;
        --nav-gap: 8px;
    }

    .box-language-switcher {
        position: fixed;
        top: max(14px, env(safe-area-inset-top));
        right: max(16px, env(safe-area-inset-right));
        z-index: 2000;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 3px;
        border: 1px solid <?= $theme['border_color'] ?>;
        border-radius: 999px;
        background: <?= $theme['panel_bg'] ?>;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.24);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
    }
    .box-language-switcher-inline {
        position: static;
        top: auto;
        right: auto;
        z-index: auto;
        box-shadow: none;
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
    }
    .box-language-label {
        padding: 0 4px 0 6px;
        color: <?= $theme['muted'] ?> !important;
        font-size: 0.72rem;
        font-weight: 700;
    }
    .box-language-switcher button {
        min-width: 28px;
        min-height: 28px;
        padding: 2px 8px;
        border: 0;
        border-radius: 999px;
        background: transparent;
        color: <?= $theme['content'] ?> !important;
        cursor: pointer;
        font: inherit;
        font-size: 0.76rem;
        font-weight: 800;
        transition: background 160ms ease, color 160ms ease, transform 160ms ease;
    }
    .box-language-switcher button:hover,
    .box-language-switcher button:focus-visible {
        background: <?= $theme['overlay_mid'] ?>;
        outline: none;
    }
    .box-language-switcher button.is-active {
        background: <?= $theme['button'] ?>;
        color: <?= $theme['content'] ?> !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.18);
    }
    .box-site-header {
        position: relative;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        width: 100%;
        margin: 0;
        min-height: 56px;
        box-sizing: border-box;
        padding: 8px clamp(14px, 2.5vw, 32px);
        color: <?= $theme['content'] ?>;
        background: rgba(15, 18, 32, 0.94);
        border-bottom: 1px solid <?= $theme['border_color'] ?>;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        flex-wrap: nowrap !important;
        white-space: nowrap !important;
    }
    .box-site-header-left {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }
    .box-site-section-nav {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        flex: 0 0 auto;
        margin: 0 auto;
    }
    .box-site-header-actions {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
        flex-wrap: nowrap !important;
        white-space: nowrap !important;
    }
    .box-site-section-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: var(--nav-control-height);
        padding: 0 12px;
        border: 1px solid transparent;
        border-radius: var(--nav-radius);
        color: <?= $theme['panel_solid'] ?> !important;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
        white-space: nowrap;
        transition: all 160ms cubic-bezier(0.16, 1, 0.3, 1);
    }
    .box-site-section-link svg {
        width: 15px;
        height: 15px;
        opacity: 0.75;
        transition: opacity 160ms ease;
    }
    .box-site-section-link:hover {
        color: <?= $theme['content'] ?> !important;
        background: <?= $theme['overlay_soft'] ?>;
        border-color: <?= $theme['border_soft'] ?>;
    }
    .box-site-section-link:hover svg {
        opacity: 1;
    }
    .box-site-section-link.is-active {
        color: <?= $theme['content'] ?> !important;
        background: <?= $theme['button'] ?>;
        border-color: <?= $theme['button'] ?>;
        font-weight: 700;
        box-shadow: 0 2px 8px <?= $theme['shadow_color'] ?>;
    }
    .box-site-section-link.is-active svg {
        opacity: 1;
        color: <?= $theme['content'] ?> !important;
    }
    .box-site-brand {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: var(--nav-control-height);
        box-sizing: border-box;
        color: <?= $theme['title'] ?> !important;
        text-decoration: none;
        font-size: 0.98rem;
        font-weight: 700;
        letter-spacing: 0.01em;
        white-space: nowrap;
    }
    .box-site-brand svg {
        width: 16px;
        height: 16px;
    }
    .box-site-separator {
        color: <?= $theme['muted'] ?>;
        font-size: 0.85rem;
    }
    .box-site-context {
        color: <?= $theme['muted'] ?> !important;
        font-size: 0.92rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .box-site-header-action {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: var(--nav-control-height);
        box-sizing: border-box;
        padding: 0 12px;
        border: 1px solid <?= $theme['border_color'] ?>;
        border-radius: var(--nav-radius);
        background: <?= $theme['panel_solid'] ?>;
        color: <?= $theme['content'] ?> !important;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
        white-space: nowrap;
        transition: all 160ms ease;
    }
    .box-site-header-action svg {
        width: 15px;
        height: 15px;
    }
    .box-site-header-action:hover {
        color: <?= $theme['content'] ?> !important;
        background: <?= $theme['overlay_soft'] ?>;
        border-color: <?= $theme['accent'] ?>;
        transform: translateY(-1px);
    }
    .box-site-section-link:focus-visible,
    .box-site-header-action:focus-visible,
    .box-site-brand:focus-visible,
    .box-language-switcher button:focus-visible {
        outline: 3px solid <?= $theme['accent'] ?> !important;
        outline-offset: 2px !important;
    }
    .box-site-header + .video-main,
    .box-site-header + main {
        margin-top: 24px;
    }
    .box-site-header + .login-container {
        margin-top: 60px;
    }
    @media (max-width: 820px) {
        .box-language-switcher {
            top: max(10px, env(safe-area-inset-top));
            right: max(10px, env(safe-area-inset-right));
        }
        .box-language-label {
            display: none;
        }
        .box-site-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 8px 12px;
            padding: 8px max(14px, env(safe-area-inset-right)) 8px max(14px, env(safe-area-inset-left));
        }
        .box-site-header-left {
            order: 1;
            flex-shrink: 0;
        }
        .box-site-header-actions {
            order: 2;
            flex-wrap: nowrap !important;
            justify-content: flex-end;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .box-site-header-actions::-webkit-scrollbar {
            display: none;
        }
        .box-site-section-nav {
            order: 3;
            width: 100%;
            justify-content: flex-start;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            padding-bottom: 2px;
        }
        .box-site-section-nav::-webkit-scrollbar {
            display: none;
        }
        .box-site-section-link {
            flex: 0 0 auto;
            padding: 0 10px;
            font-size: 0.82rem;
        }
        .box-site-header-action {
            padding: 0 10px;
            font-size: 0.82rem;
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
        'image' => ['label' => '圖片', 'href' => '/upload_image.php', 'icon' => 'image'],
        'video' => ['label' => '影片', 'href' => '/upload_video.php', 'icon' => 'video'],
        'audio' => ['label' => '音訊', 'href' => '/upload_audio.php', 'icon' => 'music'],
        'file'  => ['label' => '文件', 'href' => '/upload_file.php',  'icon' => 'file-text'],
    ];
    ?>
    <header class="box-site-header">
        <div class="box-site-header-left">
            <a href="/" class="box-site-brand" aria-label="888 BOX">
                <i data-lucide="box"></i>
                <span data-i18n="brandName">888 BOX</span>
            </a>
            <?php if ($activeSection === false && !empty($context)): ?>
                <span class="box-site-separator" aria-hidden="true">/</span>
                <span class="box-site-context" data-i18n><?= htmlspecialchars($context, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>
        <?php if ($activeSection !== false): ?>
            <nav class="box-site-section-nav" aria-label="資產類型導覽">
                <?php foreach ($sections as $key => $section): ?>
                    <a href="<?= htmlspecialchars($section['href'], ENT_QUOTES, 'UTF-8') ?>" class="box-site-section-link<?= $activeSection === $key ? ' is-active' : '' ?>"<?= $activeSection === $key ? ' aria-current="page"' : '' ?>>
                        <i data-lucide="<?= htmlspecialchars($section['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
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
