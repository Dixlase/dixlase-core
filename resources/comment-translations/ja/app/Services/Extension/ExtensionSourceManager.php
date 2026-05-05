<?php

return [
    'Extension source management, provider registry, update checks,' => '拡張機能ソースの管理、プロバイダーレジストリ、更新チェック、',
    'and central service managing fallback downloads across multiple sources' => '複数ソース間のフォールバックダウンロードを管理する中央サービス。',
    'Auto-create default sources from config presets if no sources exist in DB' => 'DB にソースが1件もない場合は config プリセットからデフォルトソースを自動作成する。',
    'Create default sources in DB from config presets' => 'config プリセットからデフォルトソースを DB に作成',
    'Skip if a source of the same type already exists' => '既に同タイプのソースが存在する場合はスキップ',
    'Set default values by type' => 'タイプ別のデフォルト値を設定',
    'Determine if source is official (hardcoded check + Ed25519 signature)' => 'ソースが公式かどうかを判定する（ハードコード + Ed25519 署名併用）',
    '1. Hardcoded check for Core preset source types' => '1. コアがプリセットしたソースタイプのハードコードチェック',
    '2. Verification by Ed25519 signature' => '2. Ed25519 署名による検証',
    'Retrieve extension detail data for the specified slug from sources' => '指定スラッグの拡張機能の詳細データをソースから取得する',
    'mixed>|null Detail data with source_id/source_name' => 'mixed>|null source_id/source_name 付きの詳細データ',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Extension source management, provider registry, update checks,' => 'machine',
        'and central service managing fallback downloads across multiple sources' => 'machine',
        'Auto-create default sources from config presets if no sources exist in DB' => 'machine',
        'Create default sources in DB from config presets' => 'machine',
        'Skip if a source of the same type already exists' => 'machine',
        'Set default values by type' => 'machine',
        'Determine if source is official (hardcoded check + Ed25519 signature)' => 'machine',
        '1. Hardcoded check for Core preset source types' => 'machine',
        '2. Verification by Ed25519 signature' => 'machine',
        'Retrieve extension detail data for the specified slug from sources' => 'machine',
        'mixed>|null Detail data with source_id/source_name' => 'machine',
    ],
];
