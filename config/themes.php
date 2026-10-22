<?php
/**
 * 888box Theme Presets Registry
 */
return [
    'active_theme' => 'default', // default fallback theme
    'presets' => [
        'default' => [
            'name' => '2MD：藏紅花與暖紙',
            'mode' => 'light',
            'title' => '#17324d',
            'content' => '#2b251a',
            'muted' => '#655a43',
            'button' => '#ffb010',
            'button_text' => '#2b251a',
            'highlight' => '#f2a600',
            'highlight_text' => '#2b251a',
            'link' => '#17324d',
            'link_hover' => '#6b3e08',
            'accent' => '#17324d',
            'accent_alt' => '#a9471d',
            'success' => '#166534',
            'warning' => '#92400e',
            'danger' => '#991b1b',
            'bg_base' => '#fff0cc',
            'bg_surface' => '#fff9e8',
            'bg_surface_2' => '#fff2d1',
            'bg_elevated' => '#f7e9c5',
            'bg_deep' => '#fffefa',
            'bg_gradient' => 'linear-gradient(180deg, #fff0cc 0%, #fff0cc 100%)',
            'panel_bg' => '#fff9e8',
            'panel_solid' => '#fff9e8',
            'card_bg' => '#fff9e8',
            'card_hover_bg' => '#fff2d1',
            'border_color' => '#6b5b38',
            'border_soft' => '#9b8350',
            'shadow_color' => 'rgba(79, 58, 14, 0.12)',
            'overlay_soft' => '#fff2d1',
            'overlay_mid' => '#f7e9c5',
            'overlay_deep' => '#fffefa'
        ],
        // Backward-compatible key for existing installs that already saved this theme id.
        'middle_east_dart' => [
            'name' => '2MD：暖紙（相容舊設定）',
            'alias_of' => 'default'
        ],
        'kanagawa_wave' => [
            'name' => '2MD：暖紙（相容舊設定）',
            'alias_of' => 'default'
        ]
    ]
];
