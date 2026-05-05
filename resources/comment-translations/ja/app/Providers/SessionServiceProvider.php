<?php

return [
    'Set table for member guard' => 'メンバーガードのテーブルを設定',
    'Set table for user guard (can be added from plugins)' => 'ユーザーガードのテーブルを設定（プラグインから追加可能）',
    'Added from plugin ServiceProvider' => 'プラグインのServiceProviderから追加される',
    'Extend session driver in register()' => 'セッションドライバーを register() で拡張',
    'Allow session access from other ServiceProviders\' boot() methods without depending on boot order' => 'boot 順序に依存せず、他の ServiceProvider の boot() からセッションを',
    '(prevents "Driver [guard-aware-database] not supported" error' => '利用できるようにする（AppServiceProvider 等でのセッションアクセス時に',
    'when accessing session in AppServiceProvider, etc.)' => '"Driver [guard-aware-database] not supported" エラーを防止）',
    'Do nothing (already extended in register())' => '何もしない（register() で拡張済み）',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Set table for member guard' => 'machine',
        'Set table for user guard (can be added from plugins)' => 'machine',
        'Added from plugin ServiceProvider' => 'machine',
        'Extend session driver in register()' => 'machine',
        'Allow session access from other ServiceProviders\' boot() methods without depending on boot order' => 'machine',
        '(prevents "Driver [guard-aware-database] not supported" error' => 'machine',
        'when accessing session in AppServiceProvider, etc.)' => 'machine',
        'Do nothing (already extended in register())' => 'machine',
    ],
];
