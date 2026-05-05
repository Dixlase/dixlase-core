<?php

return [
    'CAPTCHA form definition DTO' => 'CAPTCHA フォーム定義 DTO',
    'Immutable data object for plugins to declare forms where CAPTCHA validation should be enabled.' => 'プラグインが CAPTCHA 検証を有効化したいフォームを宣言するための不変データオブジェクト。',
    'Passed to Core\'s CaptchaService via CaptchaFormProviderInterface,' => 'CaptchaFormProviderInterface 経由でコアの CaptchaService に渡され、',
    'used for CAPTCHA settings UI in the admin panel and managing enabled state in the database.' => '管理画面の CAPTCHA 設定 UI とデータベース上の有効状態管理に使用される。',
    'Pass a unique identifier within the plugin for key (e.g., \'inquiry_contact\', \'user_login\').' => 'key にはプラグイン側で一意な識別子（例: \'inquiry_contact\', \'user_login\'）を渡す。',
    'The final form key will be aggregated in the format "{plugin_slug}.{key}".' => '最終的なフォームキーは「{plugin_slug}.{key}」の形式で集約される。',
    'Unique form identifier within the plugin (e.g., \'inquiry_contact\')' => 'プラグイン内で一意なフォーム識別子（例: \'inquiry_contact\'）',
    'Translation key for display name (e.g., \'dixlase-inquiry::captcha.forms.inquiry_contact\')' => '表示名の翻訳キー（例: \'dixlase-inquiry::captcha.forms.inquiry_contact\'）',
    'Route name for form submission (e.g., \'inquiry.send\')' => 'フォーム送信先ルート名（例: \'inquiry.send\'）',
    'Category for UI grouping (e.g., \'contact\', \'users\')' => 'UI グルーピング用カテゴリ（例: \'contact\', \'users\'）',
    'Default enabled state on initial registration' => '初回登録時のデフォルト有効状態',
    'Display order (smaller values appear first)' => '表示順序（小さいほど上位）',
    'Serialize to JSON format' => 'JSON 形式にシリアライズ',
    'Convert to array format' => '配列形式に変換',
    'Create DTO from array' => '配列から DTO を生成',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'CAPTCHA form definition DTO' => 'machine',
        'Immutable data object for plugins to declare forms where CAPTCHA validation should be enabled.' => 'machine',
        'Passed to Core\'s CaptchaService via CaptchaFormProviderInterface,' => 'machine',
        'used for CAPTCHA settings UI in the admin panel and managing enabled state in the database.' => 'machine',
        'Pass a unique identifier within the plugin for key (e.g., \'inquiry_contact\', \'user_login\').' => 'machine',
        'The final form key will be aggregated in the format "{plugin_slug}.{key}".' => 'machine',
        'Unique form identifier within the plugin (e.g., \'inquiry_contact\')' => 'machine',
        'Translation key for display name (e.g., \'dixlase-inquiry::captcha.forms.inquiry_contact\')' => 'machine',
        'Route name for form submission (e.g., \'inquiry.send\')' => 'machine',
        'Category for UI grouping (e.g., \'contact\', \'users\')' => 'machine',
        'Default enabled state on initial registration' => 'machine',
        'Display order (smaller values appear first)' => 'machine',
        'Serialize to JSON format' => 'machine',
        'Convert to array format' => 'machine',
        'Create DTO from array' => 'machine',
    ],
];
