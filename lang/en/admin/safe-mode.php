<?php

return [
    // モードラベル
    'csp_label' => 'CSP Safe Mode',
    'plugins_label' => 'Plugin Safe Mode',
    'theme_label' => 'Theme Safe Mode',

    // CSPバナー
    'csp_banner_title' => 'CSP Safe Mode is Active',
    'csp_banner_message' => 'Content Security Policy headers are disabled. This poses a security risk. Please disable safe mode after completing your configuration.',
    'csp_go_to_settings' => 'CSP Settings',

    // プラグインバナー
    'plugins_banner_title' => 'Plugin Safe Mode is Active',
    'plugins_banner_message' => 'Plugin routes and assets are disabled. Admin pages provided by plugins are currently inaccessible.',
    'plugins_go_to_settings' => 'Plugin Settings',

    // テーマバナー
    'theme_banner_title' => 'Theme Safe Mode is Active',
    'theme_banner_message' => 'The theme is disabled. Front-end pages are displayed with a minimal fallback layout.',
    'theme_go_to_settings' => 'Theme Settings',

    // テーマセーフモード（フロント表示用）
    'theme_safe_mode_label' => 'Safe Mode',
    'theme_safe_mode_title' => 'Theme Safe Mode is Active',
    'theme_safe_mode_front_message' => 'The current theme has been temporarily disabled. Pages are displayed with a minimal layout. To restore the theme, disable safe mode from the admin panel.',

    // アクション
    'disable' => 'Disable',
    'disable_all' => 'Disable All',

    // フラッシュメッセージ
    'disabled' => ':mode has been disabled.',
    'all_disabled' => 'All safe modes have been disabled.',
    'invalid_mode' => 'Invalid safe mode specified.',

    // プラグインルートブロック
    'plugins_route_blocked' => 'Plugin pages are disabled while Plugin Safe Mode is active.',
];
