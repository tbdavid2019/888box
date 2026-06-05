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
    </style>
    <?php
}
