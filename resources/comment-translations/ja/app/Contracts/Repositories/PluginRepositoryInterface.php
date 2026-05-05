<?php

return [
    'Plugin repository interface' => 'プラグインリポジトリインターフェース',
    'Abstraction layer for retrieving information about enabled plugins.' => '有効化されたプラグインの情報を取得するための抽象レイヤー。',
    'Eliminates direct dependency on Plugin Eloquent model and enables SDK separation.' => 'Plugin Eloquent モデルへの直接依存を排除し、SDK分離を可能にする。',
    'Retrieve list of enabled plugins' => '有効化されたプラグインの一覧を取得',
    'Returns an empty collection if the plugins table does not exist.' => 'pluginsテーブルが存在しない場合は空コレクションを返す。',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Plugin repository interface' => 'machine',
        'Abstraction layer for retrieving information about enabled plugins.' => 'machine',
        'Eliminates direct dependency on Plugin Eloquent model and enables SDK separation.' => 'machine',
        'Retrieve list of enabled plugins' => 'machine',
        'Returns an empty collection if the plugins table does not exist.' => 'machine',
    ],
];
