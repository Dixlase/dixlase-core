<?php

return [
    // モードラベル
    'csp_label' => 'CSPセーフモード',
    'plugins_label' => 'プラグインセーフモード',
    'theme_label' => 'テーマセーフモード',

    // CSPバナー
    'csp_banner_title' => 'CSPセーフモードが有効です',
    'csp_banner_message' => 'CSPヘッダーが無効化されています。セキュリティリスクがあるため、設定完了後は必ずセーフモードを解除してください。',
    'csp_go_to_settings' => 'CSP設定へ',

    // プラグインバナー
    'plugins_banner_title' => 'プラグインセーフモードが有効です',
    'plugins_banner_message' => 'プラグインのルートとアセットが無効化されています。プラグインが提供する管理ページは現在アクセスできません。',
    'plugins_go_to_settings' => 'プラグイン設定へ',

    // テーマバナー
    'theme_banner_title' => 'テーマセーフモードが有効です',
    'theme_banner_message' => 'テーマが無効化されています。フロントページは最小限のフォールバックレイアウトで表示されます。',
    'theme_go_to_settings' => 'テーマ設定へ',

    // テーマセーフモード（フロント表示用）
    'theme_safe_mode_label' => 'セーフモード',
    'theme_safe_mode_title' => 'テーマセーフモードが有効です',
    'theme_safe_mode_front_message' => '現在のテーマが一時的に無効化されています。最小限のレイアウトでページが表示されています。テーマを復元するには、管理画面からセーフモードを解除してください。',

    // アクション
    'disable' => '解除',
    'disable_all' => 'すべて解除',

    // フラッシュメッセージ
    'disabled' => ':modeを解除しました。',
    'all_disabled' => 'すべてのセーフモードを解除しました。',
    'invalid_mode' => '無効なセーフモードが指定されました。',

    // プラグインルートブロック
    'plugins_route_blocked' => 'プラグインセーフモードが有効なため、プラグインページは無効化されています。',
];
