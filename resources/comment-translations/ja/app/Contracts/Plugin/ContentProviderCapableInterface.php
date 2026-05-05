<?php

return [
    'Interface declaring content provider capability' => 'コンテンツ提供機能を宣言するインターフェース',
    'Implemented by plugins that provide content to other plugins' => '他のプラグインにコンテンツを提供するプラグインが実装します。',
    'Extends the existing LinkableProviderInterface and' => '既存の LinkableProviderInterface を拡張し、',
    'enables resolution with permission checks in PluginServiceResolver' => 'PluginServiceResolver で権限チェック付きの解決を可能にします。',
    'Get the types of content to provide' => '提供するコンテンツの種類を取得',
    'Example: [\'page\', \'post\']' => '例: [\'page\', \'post\']',
    'Check if the specified content type is provided' => '指定したコンテンツタイプを提供しているか',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Interface declaring content provider capability' => 'machine',
        'Implemented by plugins that provide content to other plugins' => 'machine',
        'Extends the existing LinkableProviderInterface and' => 'machine',
        'enables resolution with permission checks in PluginServiceResolver' => 'machine',
        'Get the types of content to provide' => 'machine',
        'Example: [\'page\', \'post\']' => 'machine',
        'Check if the specified content type is provided' => 'machine',
    ],
];
