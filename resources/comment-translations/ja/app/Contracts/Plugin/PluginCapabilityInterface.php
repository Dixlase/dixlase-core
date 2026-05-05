<?php

return [
    'Base interface for plugin capability declaration' => 'プラグイン機能宣言の基底インターフェース',
    'Base interface to implement when a plugin provides specific capabilities' => 'プラグインが特定の機能を提供する場合に実装する基底インターフェースです。',
    'Each capability-specific interface (e.g. MailCapableInterface) inherits this' => '各機能固有のインターフェース（MailCapableInterface 等）はこれを継承します。',
    'By registering with tags in the service container via the plugin\'s ServiceProvider,' => 'プラグインの ServiceProvider でサービスコンテナにタグ付き登録することで、',
    'PluginServiceResolver automatically discovers and resolves them' => 'PluginServiceResolver が自動的に発見・解決します。',
    'Get the plugin slug' => 'プラグインのスラッグを取得',
    'e.g. \'dixlase-inquiry\'' => '例: \'dixlase-inquiry\'',
    'Whether this capability is currently available' => 'この機能が現在利用可能かどうか',
    'Depending on the plugin settings state and dependencies,' => 'プラグインの設定状態や依存関係により、',
    'the capability may be temporarily disabled' => '機能が一時的に無効になる場合があります。',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Base interface for plugin capability declaration' => 'machine',
        'Base interface to implement when a plugin provides specific capabilities' => 'machine',
        'Each capability-specific interface (e.g. MailCapableInterface) inherits this' => 'machine',
        'By registering with tags in the service container via the plugin\'s ServiceProvider,' => 'machine',
        'PluginServiceResolver automatically discovers and resolves them' => 'machine',
        'Get the plugin slug' => 'machine',
        'e.g. \'dixlase-inquiry\'' => 'machine',
        'Whether this capability is currently available' => 'machine',
        'Depending on the plugin settings state and dependencies,' => 'machine',
        'the capability may be temporarily disabled' => 'machine',
    ],
];
