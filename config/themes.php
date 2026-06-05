<?php
/**
 * 888box Theme Presets Registry
 */
return [
    'active_theme' => 'default', // default fallback theme
    'presets' => [
        'default' => [
            'name' => 'Catppuccin Macchiato',
            'mode' => 'dark',
            'title' => '#f4dbd6',
            'content' => '#cad3f5',
            'muted' => '#a5adcb',
            'button' => '#8aadf4',
            'button_text' => '#181926',
            'highlight' => '#c6a0f6',
            'highlight_text' => '#181926',
            'accent' => '#8bd5ca',
            'accent_alt' => '#f5bde6',
            'success' => '#a6da95',
            'warning' => '#eed49f',
            'danger' => '#ed8796',
            'bg_base' => '#24273a',
            'bg_surface' => '#303446',
            'bg_surface_2' => '#363a4f',
            'bg_elevated' => '#414559',
            'bg_deep' => '#181926',
            'bg_gradient' => 'radial-gradient(circle at top, rgba(138, 173, 244, 0.16), transparent 32%), radial-gradient(circle at 85% 15%, rgba(198, 160, 246, 0.14), transparent 26%), linear-gradient(180deg, #303446 0%, #24273a 42%, #181926 100%)',
            'panel_bg' => 'linear-gradient(135deg, rgba(48, 52, 70, 0.94), rgba(36, 39, 58, 0.92))',
            'panel_solid' => '#303446',
            'card_bg' => 'rgba(48, 52, 70, 0.78)',
            'card_hover_bg' => 'rgba(65, 69, 89, 0.92)',
            'border_color' => '#626880',
            'border_soft' => 'rgba(138, 173, 244, 0.22)',
            'shadow_color' => 'rgba(24, 25, 38, 0.46)',
            'overlay_soft' => 'rgba(65, 69, 89, 0.72)',
            'overlay_mid' => 'rgba(81, 87, 109, 0.82)',
            'overlay_deep' => 'rgba(24, 25, 38, 0.72)'
        ],
        // Backward-compatible key for existing installs that already saved this theme id.
        'middle_east_dart' => [
            'name' => 'Catppuccin Macchiato',
            'alias_of' => 'default'
        ],
        'kanagawa_wave' => [
            'name' => 'Kanagawa Wave Light',
            'mode' => 'light',
            'title' => '#16161D',
            'content' => '#1F1F28',
            'muted' => '#54546D',
            'button' => '#7E9CD8',
            'button_text' => '#16161D',
            'highlight' => '#E6C384',
            'highlight_text' => '#16161D',
            'accent' => '#7AA89F',
            'accent_alt' => '#D27E99',
            'success' => '#76946A',
            'warning' => '#DCA561',
            'danger' => '#C34043',
            'bg_base' => '#DCD7BA',
            'bg_surface' => '#E6DFBF',
            'bg_surface_2' => '#F2ECBC',
            'bg_elevated' => '#C8C093',
            'bg_deep' => '#BDB58A',
            'bg_gradient' => 'radial-gradient(circle at top, rgba(126, 156, 216, 0.22), transparent 32%), radial-gradient(circle at 85% 15%, rgba(230, 195, 132, 0.2), transparent 26%), linear-gradient(180deg, #F2ECBC 0%, #DCD7BA 42%, #C8C093 100%)',
            'panel_bg' => 'linear-gradient(135deg, rgba(242, 236, 188, 0.94), rgba(220, 215, 186, 0.9))',
            'panel_solid' => '#E6DFBF',
            'card_bg' => 'rgba(242, 236, 188, 0.78)',
            'card_hover_bg' => 'rgba(230, 223, 191, 0.94)',
            'border_color' => '#938056',
            'border_soft' => 'rgba(147, 128, 86, 0.3)',
            'shadow_color' => 'rgba(84, 84, 100, 0.18)',
            'overlay_soft' => 'rgba(242, 236, 188, 0.76)',
            'overlay_mid' => 'rgba(200, 192, 147, 0.5)',
            'overlay_deep' => 'rgba(84, 84, 100, 0.18)'
        ]
    ]
];
