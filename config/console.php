<?php
return [
    // 例: スタブのデフォルト格納先
    'default_stub_directory' => base_path('vendor/laravel/framework/src/Illuminate/Routing/Console/stubs'),

    // 独自のカスタムスタブディレクトリを複数設定したい場合
    'custom_stub_paths' => [
        base_path('stubs/custom'),
        base_path('stubs/overrides'),
    ],

    // 他にも繰り返し使うような定数など
    'license_txt' => base_path('license.txt'),
    'license_json' => base_path('license-info.json'),
];
