<?php

return [
    'Which backup was restored from (retains history even after backup deletion)' => 'どのバックアップから復元したか（バックアップ削除後も履歴を保持）',
    'Restore executor (retains history even after member deletion)' => '復元実行者（メンバー削除後も履歴を保持）',
    'Snapshot for display' => '表示用スナップショット',
    'Restore execution time' => '復元実行時刻',
    'Actually restored targets (may restore only a portion of the entire backup)' => '実際に復元した対象（バックアップ全体の一部のみ復元する場合がある）',
    'e.g.: ["database"] / ["media", "custom"] / ["database", "media", "private", "custom"]' => '例: ["database"] / ["media", "custom"] / ["database", "media", "private", "custom"]',
    'Status: pending, in_progress, completed, failed, rolled_back' => 'ステータス: pending, in_progress, completed, failed, rolled_back',
    'Safety snapshot before restore (used to undo restore)' => '復元前の安全スナップショット（復元の取り消しに使用）',
    'Execution information' => '実行情報',
    'Metadata: number of restored tables, files, warnings, etc.' => 'メタデータ: 復元したテーブル数、ファイル数、警告等',
    'Composite index' => '複合インデックス',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Which backup was restored from (retains history even after backup deletion)' => 'machine',
        'Restore executor (retains history even after member deletion)' => 'machine',
        'Snapshot for display' => 'machine',
        'Restore execution time' => 'machine',
        'Actually restored targets (may restore only a portion of the entire backup)' => 'machine',
        'e.g.: ["database"] / ["media", "custom"] / ["database", "media", "private", "custom"]' => 'machine',
        'Status: pending, in_progress, completed, failed, rolled_back' => 'machine',
        'Safety snapshot before restore (used to undo restore)' => 'machine',
        'Execution information' => 'machine',
        'Metadata: number of restored tables, files, warnings, etc.' => 'machine',
        'Composite index' => 'machine',
    ],
];
