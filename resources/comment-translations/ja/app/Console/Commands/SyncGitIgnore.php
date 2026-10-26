<?php

return [
    'Removes the `!plugins/<Name>/` and `!themes/<Name>/` rules that older' => '古い core がインストール済みの拡張機能ごとに .gitignore へ書いた',
    'versions of core wrote into .gitignore for every installed extension.' => '`!plugins/<Name>/` と `!themes/<Name>/` のルールを削除する。',
    'creating or deleting an extension no longer touches .gitignore; run this once' => '拡張機能のインストール・作成・削除は、もう .gitignore に触れない。',
    'Path to the .gitignore file' => '.gitignore ファイルのパス',
    'Whether the .gitignore file contains the given text' => '.gitignore ファイルが指定の文字列を含むかどうか',
    'Core ignores `plugins/*` and `themes/*` on purpose, and each of those' => 'core は `plugins/*` と `themes/*` をわざと無視しており、それらのルールは',
    'rules re-included one extension directory in core\'s git. Installing,' => '1 つずつ拡張機能のディレクトリを core の git で追跡し直させていた。',
    'to clean up a checkout that still carries the rules:' => 'ルールがまだ残っているチェックアウトを片付けるには、次を一度実行する:',
    '# preview' => '# 確認だけ',
    '# write' => '# 書き込む',
    'The name and signature of the console command.' => 'コンソールコマンドの名前とシグネチャ。',
    'The console command description.' => 'コンソールコマンドの説明。',
    'Name of the file as shown to the operator' => '運用者に見せるファイル名',
    'Check if plugin exclusion rule exists' => 'プラグインの除外ルールが存在するか確認',
    'Check if theme exclusion rule exists' => 'テーマの除外ルールが存在するか確認',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Removes the `!plugins/<Name>/` and `!themes/<Name>/` rules that older' => 'machine',
        'versions of core wrote into .gitignore for every installed extension.' => 'machine',
        'creating or deleting an extension no longer touches .gitignore; run this once' => 'machine',
        'Path to the .gitignore file' => 'machine',
        'Whether the .gitignore file contains the given text' => 'machine',
        'Core ignores `plugins/*` and `themes/*` on purpose, and each of those' => 'machine',
        'rules re-included one extension directory in core\'s git. Installing,' => 'machine',
        'to clean up a checkout that still carries the rules:' => 'machine',
        '# preview' => 'machine',
        '# write' => 'machine',
        'The name and signature of the console command.' => 'machine',
        'The console command description.' => 'machine',
        'Name of the file as shown to the operator' => 'machine',
        'Check if plugin exclusion rule exists' => 'machine',
        'Check if theme exclusion rule exists' => 'machine',
    ],
];
