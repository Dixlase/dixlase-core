<?php

return [
    'Restore service interface' => '復元サービスインターフェース',
    'Provides restore and rollback from backups.' => 'バックアップからの復元およびロールバックを提供します。',
    'Automatically takes a safety snapshot (backup of current state) before restore,' => '復元前に自動的にセーフティスナップショット（現在状態のバックアップ）を取得し、',
    'enabling rollback on failure.' => '失敗時のロールバックを可能にします。',
    'Execute restore from backup' => 'バックアップから復元を実行',
    'Automatically takes a safety snapshot before execution.' => '実行前に自動的にセーフティスナップショットを取得します。',
    'Backup to restore from' => '復元元のバックアップ',
    'Targets to restore (if empty array, all targets included in backup)' => '復元する対象（空配列の場合はバックアップに含まれる全対象）',
    'Additional options (e.g., [\'skip_pre_restore_backup\' => false])' => '追加オプション（例: [\'skip_pre_restore_backup\' => false]）',
    'Rollback restore (restore from safety snapshot)' => '復元のロールバック（セーフティスナップショットからの復元）',
    'Can only be executed on RestoreRecord where canRollback() is true.' => 'canRollback() が true の RestoreRecord にのみ実行可能です。',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Restore service interface' => 'machine',
        'Provides restore and rollback from backups.' => 'machine',
        'Automatically takes a safety snapshot (backup of current state) before restore,' => 'machine',
        'enabling rollback on failure.' => 'machine',
        'Execute restore from backup' => 'machine',
        'Automatically takes a safety snapshot before execution.' => 'machine',
        'Backup to restore from' => 'machine',
        'Targets to restore (if empty array, all targets included in backup)' => 'machine',
        'Additional options (e.g., [\'skip_pre_restore_backup\' => false])' => 'machine',
        'Rollback restore (restore from safety snapshot)' => 'machine',
        'Can only be executed on RestoreRecord where canRollback() is true.' => 'machine',
    ],
];
