<?php

return [
    'Command for verifying migration immutability.' => 'マイグレーションの不可侵性をチェックするコマンド。',
    'Absolute path to the lockfile' => 'ロックファイルの絶対パス',
    'Scan base path. Tests can override this via --base-path.' => 'スキャン基準パス。テスト時は --base-path で差し替え可能。',
    'Hash every current migration file with SHA-256 and write the result to the lockfile.' => '現在のマイグレーションファイル群から SHA-256 ハッシュを生成しロックファイルに書き出す。',
    'Compare the lockfile against the current migration state.' => 'ロックファイルと現在のマイグレーション状態を照合する。',
    'Detect modifications and deletions' => '改変・削除の検出',
    'Display a human-readable report.' => '人間向けレポートを表示する。',
    'Collect every migration file across core, plugins, and themes.' => 'コア・プラグイン・テーマの全マイグレーションファイルを収集する。',
    'Skip underscore-prefixed backup files (which Laravel itself ignores)' => 'アンダースコア始まりのバックアップファイル（Laravel のスキャン対象外）は除外する',
    'Generate database/migration-lock.json with the `--lock` option just before v0.1.0 release,' => 'v0.1.0 リリース直前に `--lock` オプションで database/migration-lock.json を生成し、',
    'then compare that lock file with current migration files using SHA-256' => '以降はそのロックファイルと現在のマイグレーションファイル群を SHA-256 で比較する。',
    'Detects modification, deletion, or renaming of existing migrations, and' => '既存マイグレーションの改変・削除・リネーム、およびリリース後の `0001_01_01_*` 形式での',
    'new additions in `0001_01_01_*` format (after release, Laravel standard `YYYY_MM_DD_HHMMSS_*` is required)' => '新規追加（リリース後は Laravel 標準の `YYYY_MM_DD_HHMMSS_*` が必須）を検出する。',
    'Detection of new additions and naming convention violations' => '新規追加と命名規則違反の検出',
    'New migrations added after release (when lock file exists)' => 'リリース後（ロックファイル存在時）に追加する新規マイグレーションは',
    'must use Laravel standard YYYY_MM_DD_HHMMSS_* format; 0001_01_01_* is a violation' => 'Laravel 標準の YYYY_MM_DD_HHMMSS_* 形式のみ許可し、0001_01_01_* は違反とする。',
    'string> relative path => absolute path (sorted)' => 'string> 相対パス => 絶対パス（ソート済み）',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Generate database/migration-lock.json with the `--lock` option just before v0.1.0 release,' => 'machine',
        'then compare that lock file with current migration files using SHA-256' => 'machine',
        'Detects modification, deletion, or renaming of existing migrations, and' => 'machine',
        'new additions in `0001_01_01_*` format (after release, Laravel standard `YYYY_MM_DD_HHMMSS_*` is required)' => 'machine',
        'Detection of new additions and naming convention violations' => 'machine',
        'New migrations added after release (when lock file exists)' => 'machine',
        'must use Laravel standard YYYY_MM_DD_HHMMSS_* format; 0001_01_01_* is a violation' => 'machine',
        'string> relative path => absolute path (sorted)' => 'machine',
    ],
];
