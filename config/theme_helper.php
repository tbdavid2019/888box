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
    
    ?>
    <style>
    :root {
        --bg-dark: <?= $theme['bg_gradient'] ?> !important;
        --bg-color: #1a1b26;
        --panel-bg: <?= $theme['panel_bg'] ?> !important;
        --primary: <?= $theme['button'] ?> !important;
        --primary-hover: <?= $theme['highlight'] ?> !important;
        --accent: <?= $theme['highlight'] ?> !important;
        --accent-blue: <?= $theme['button'] ?> !important;
        --accent-purple: <?= $theme['highlight'] ?> !important;
        --text-main: <?= $theme['content'] ?> !important;
        --text-primary: <?= $theme['content'] ?> !important;
        --text-muted: <?= $theme['content'] ?>bb !important;
        --border: <?= $theme['border_color'] ?> !important;
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
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2) !important;
        border: none !important;
    }
    
    .btn.primary:hover, .submit-btn:hover, .btn-download:hover, .password-gate button:hover, .btn-copy:hover, .btn-edit:hover {
        background: <?= $theme['highlight'] ?> !important;
        color: #000000 !important;
        opacity: 0.95 !important;
        transform: translateY(-1px) !important;
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
        background: rgba(0, 0, 0, 0.2) !important;
    }
    </style>
    <?php
}
