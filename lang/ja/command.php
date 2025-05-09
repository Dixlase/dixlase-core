<?php

return [
    'scope' => [
        'prompt' => 'スコープを選択してください',
        'labels' => [
            'plain' => 'スコープなし',
            'front' => 'フロント用',
            'admin' => '管理画面用',
        ],
        'map' => [
            'スコープなし' => 'plain',
            'フロント用' => 'front',
            '管理画面用' => 'admin',
        ]
    ],
    'file_type' => [
        'prompt' => 'ファイルの種類を選択してください',
        'labels' => [
            'core' => 'コアファイル（core）',
            'plugin' => 'プラグインファイル（plugin）',
        ],
    ],
];
