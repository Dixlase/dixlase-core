<?php

return [
    'Shared body of `dls:sync-gitignore` and `dls:sync-git-exclude`.' => '`dls:sync-gitignore` と `dls:sync-git-exclude` の共通の本体。',
    'Both commands used to add a `!plugins/<Name>/` or `!themes/<Name>/` rule' => 'どちらのコマンドも以前は、インストール済みの拡張機能ごとに',
    'for every installed extension, which made core\'s git track extension' => '`!plugins/<Name>/` か `!themes/<Name>/` のルールを追加しており、core の git が',
    'directories it ignores on purpose. They now only remove such rules. The' => 'わざと無視している拡張機能のディレクトリを追跡していた。いまはそのルールを削除するだけ。',
    'default run is a dry-run preview; `--confirm` (or the older `--force`)' => '既定の実行は確認だけのドライランで、`--confirm`（または以前の `--force`）で',
    'writes the cleaned file.' => '片付けたファイルを書き込む。',
    'The using command supplies the file through rulesFilePath(), which tests' => '対象のファイルは使う側のコマンドが rulesFilePath() で渡す。テストは',
    'override to point at a throwaway file.' => 'これを上書きして使い捨てのファイルを指す。',
    '@internal Core only. Do not reference from plugins/themes' => '@internal コア専用。プラグイン/テーマから参照しないこと',
    'Absolute path of the file to clean up.' => '片付けるファイルの絶対パス。',
    'Name of the file as shown to the operator.' => '運用者に見せるファイル名。',
    'Execute the console command.' => 'コンソールコマンドを実行する。',
    'Which rules this run removes: the rule types, and one directory name' => '今回の実行で削除するルール。ルールの種類と、--remove-plugin か',
    'when --remove-plugin or --remove-theme names it.' => '--remove-theme で指定されたときはそのディレクトリ名。',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Shared body of `dls:sync-gitignore` and `dls:sync-git-exclude`.' => 'machine',
        'Both commands used to add a `!plugins/<Name>/` or `!themes/<Name>/` rule' => 'machine',
        'for every installed extension, which made core\'s git track extension' => 'machine',
        'directories it ignores on purpose. They now only remove such rules. The' => 'machine',
        'default run is a dry-run preview; `--confirm` (or the older `--force`)' => 'machine',
        'writes the cleaned file.' => 'machine',
        'The using command supplies the file through rulesFilePath(), which tests' => 'machine',
        'override to point at a throwaway file.' => 'machine',
        '@internal Core only. Do not reference from plugins/themes' => 'machine',
        'Absolute path of the file to clean up.' => 'machine',
        'Name of the file as shown to the operator.' => 'machine',
        'Execute the console command.' => 'machine',
        'Which rules this run removes: the rule types, and one directory name' => 'machine',
        'when --remove-plugin or --remove-theme names it.' => 'machine',
    ],
];
