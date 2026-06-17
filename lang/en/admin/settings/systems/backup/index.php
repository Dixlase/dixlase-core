<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

return [
    'heading' => 'Backups',
    'description' => 'Manage backups of the database, media, private content, and customizations. Create new backups manually, restore from existing ones, and download backup archives.',

    // 新規作成
    'create_button' => 'New Backup',
    'create_modal' => [
        'title' => 'Create New Backup',
        'message' => 'Select what to include in this backup.',
        'targets_label' => 'Backup Targets',
        'retention_label' => 'Retention Period (days)',
        'retention_help' => 'After this period, the backup is eligible for cleanup. Leave blank to keep indefinitely.',
        'confirm_label' => 'Create',
        'cancel_label' => 'Cancel',
    ],

    // 復元
    'restore_modal' => [
        'title' => 'Restore From Backup',
        'message' => 'This will overwrite the current state with the backup contents. A safety snapshot of the current state will be taken automatically before restore. Continue?',
        'confirm_label' => 'Restore',
        'cancel_label' => 'Cancel',
    ],

    // 削除
    'delete_modal' => [
        'title' => 'Delete Backup',
        'message' => 'This will permanently delete the backup file. This action cannot be undone.',
        'confirm_label' => 'Delete',
        'cancel_label' => 'Cancel',
    ],

    // テーブル
    'table' => [
        'caption' => 'Backup List',
        'created_at' => 'Created At',
        'type' => 'Type',
        'targets' => 'Targets',
        'size' => 'Size',
        'status' => 'Status',
        'verification' => 'Verification',
        'hash' => 'Hash',
        'actions' => 'Actions',
        'path' => 'Storage path',
        'file_name' => 'File name',
        'no_records' => 'No backups have been created yet.',
    ],

    // 対象
    'targets' => [
        'database' => 'Database',
        'media' => 'Media',
        'private' => 'Private',
        'custom' => 'Custom',
        'logs' => 'Logs',
        'core_source' => 'Core source',
        'plugins_all' => 'Plugins',
        'themes_all' => 'Themes',
    ],

    // Auto-generated notes for backups taken automatically by the
    // update / restore flows. Operators can edit them afterwards.
    'auto_note' => [
        'pre_extension_update' => 'Pre-update backup before updating: :names',
        'pre_core_update' => 'Pre-update backup before updating the core (v:current → v:available)',
        'core_update_db' => 'Database snapshot before applying the core update (v:current → v:version)',
        'pre_restore' => 'Safety snapshot taken automatically before restoring backup #:id. To undo that restore and return to the previous state, restore from this one.',
    ],

    // タイプ
    'types' => [
        'full' => 'Full',
        'database' => 'Database Only',
        'files' => 'Files Only',
    ],

    // ステータス
    'statuses' => [
        'completed' => 'Completed',
        'failed' => 'Failed',
        'expired' => 'Expired',
        'deleted' => 'Deleted',
    ],

    // 検証ステータス
    'verifications' => [
        'unchecked' => 'Unchecked',
        'valid' => 'Valid',
        'invalid' => 'Invalid',
    ],

    // アクションボタン
    'actions' => [
        'detail' => 'Details',
        'download' => 'Download',
        'restore' => 'Restore',
        'delete' => 'Delete',
    ],

    // 詳細画面（メタデータ + 編集可能なメモ）
    'detail' => [
        'heading' => 'Backup Details',
        'back' => 'Back to backups',
        'created_at' => 'Created at',
        'status' => 'Status',
        'targets' => 'Targets',
        'size' => 'Size',
        'file_name' => 'File name',
        'path' => 'Storage path',
        'hash' => 'Hash',
        'retention' => 'Retention until',
        'retention_none' => 'Kept indefinitely',
        'duration' => 'Duration',
        'file_missing' => 'The archive file for this backup is no longer on disk.',
        'note_label' => 'Note',
        'note_help' => 'A free-form memo for this backup. Update-triggered backups are pre-filled automatically; you can edit it here.',
        'note_placeholder' => 'e.g. Manual backup before editing the homepage',
        'note_save' => 'Save Note',
    ],

    // フラッシュメッセージ
    'flash' => [
        'create_success' => 'Backup created successfully (:size, :duration s).',
        'create_failed' => 'Failed to create backup: :error',
        'delete_success' => 'Backup deleted successfully.',
        'delete_failed' => 'Failed to delete backup.',
        'download_failed' => 'Backup file not found.',
        'restore_success' => 'Restore completed successfully (:duration s). A safety snapshot was taken automatically.',
        'restore_failed' => 'Restore failed: :error',
        'restore_unavailable' => 'This backup is not available for restore.',
        'note_updated' => 'Backup note updated.',
    ],

    // バリデーション
    'validation' => [
        'targets_required' => 'Please select at least one backup target.',
        'target_invalid' => 'The selected backup target is invalid.',
        'retention_invalid' => 'Retention period must be a positive integer between 1 and 3650 days.',
    ],

    'placeholder' => 'This page is under construction. The full UI will be available in an upcoming release.',
];
