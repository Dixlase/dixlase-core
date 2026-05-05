<?php

return [
    'Contract for plugins that provide CAPTCHA forms' => 'CAPTCHA フォームを提供するプラグイン用 Contract',
    'To apply CAPTCHA to your plugin\'s forms, implement this interface and' => '自プラグインのフォームに CAPTCHA を適用したい場合、このインターフェースを実装し',
    'register it with a tag in the service container via ServiceProvider' => 'ServiceProvider でサービスコンテナにタグ付き登録します。',
    '```' => '```',
    'Additionally, declare "captcha" in the "capabilities" array of plugin.json' => '加えて plugin.json の "capabilities" 配列に "captcha" を宣言してください。',
    'The Core CaptchaService will aggregate form definitions from all plugin' => 'コアの CaptchaService が PluginServiceResolver 経由で',
    'implementations via PluginServiceResolver' => '全プラグインの実装からフォーム定義を集約します。',
    'Validation and rendering continue to use the Core CaptchaHelper (to centrally' => '検証・描画は引き続きコアの CaptchaHelper を使用します（プロバイダ切替・',
    'manage provider switching, failover, emergency bypass, etc.)' => 'フェイルオーバー・緊急バイパス等を一元管理するため）。',
    'Return form definitions to which CAPTCHA should be applied in your plugin' => '自プラグインで CAPTCHA を適用するフォーム定義を返す',
    'The returned DTO keys are converted to "{plugin_slug}.{key}" format, and' => '返した DTO の key は「{plugin_slug}.{key}」形式に変換され、',
    'that full key is used in the captcha_enabled_forms table and admin panel' => 'captcha_enabled_forms テーブルおよび管理画面でその完全キーが使われます。',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Contract for plugins that provide CAPTCHA forms' => 'machine',
        'To apply CAPTCHA to your plugin\'s forms, implement this interface and' => 'machine',
        'register it with a tag in the service container via ServiceProvider' => 'machine',
        '```' => 'machine',
        'Additionally, declare "captcha" in the "capabilities" array of plugin.json' => 'machine',
        'The Core CaptchaService will aggregate form definitions from all plugin' => 'machine',
        'implementations via PluginServiceResolver' => 'machine',
        'Validation and rendering continue to use the Core CaptchaHelper (to centrally' => 'machine',
        'manage provider switching, failover, emergency bypass, etc.)' => 'machine',
        'Return form definitions to which CAPTCHA should be applied in your plugin' => 'machine',
        'The returned DTO keys are converted to "{plugin_slug}.{key}" format, and' => 'machine',
        'that full key is used in the captcha_enabled_forms table and admin panel' => 'machine',
    ],
];
