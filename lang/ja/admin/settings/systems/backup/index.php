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
    'heading' => 'バックアップ',
    'description' => 'データベース、メディア、プライベートコンテンツ、カスタマイズのバックアップを管理します。手動でのバックアップ作成、既存バックアップからの復元、アーカイブのダウンロードができます。',

    // 新規作成
    'create_button' => '新規バックアップ',
    'create_modal' => [
        'title' => '新規バックアップ作成',
        'message' => 'バックアップに含める対象を選択してください。',
        'targets_label' => 'バックアップ対象',
        'retention_label' => '保持期間（日）',
        'retention_help' => 'この期間が過ぎたバックアップは自動クリーンアップの対象になります。空欄で無期限に保持。',
        'confirm_label' => '作成',
        'cancel_label' => 'キャンセル',
    ],

    // 復元
    'restore_modal' => [
        'title' => 'バックアップから復元',
        'message' => '現在の状態をバックアップの内容で上書きします。復元前に現在の状態のセーフティスナップショットが自動的に取得されます。続行しますか？',
        'confirm_label' => '復元',
        'cancel_label' => 'キャンセル',
    ],

    // 削除
    'delete_modal' => [
        'title' => 'バックアップを削除',
        'message' => 'このバックアップファイルを完全に削除します。この操作は元に戻せません。',
        'confirm_label' => '削除',
        'cancel_label' => 'キャンセル',
    ],

    // テーブル
    'table' => [
        'caption' => 'バックアップ一覧',
        'created_at' => '作成日時',
        'type' => 'タイプ',
        'targets' => '対象',
        'size' => 'サイズ',
        'status' => 'ステータス',
        'verification' => '検証',
        'hash' => 'ハッシュ',
        'actions' => '操作',
        'path' => '保存先パス',
        'file_name' => 'ファイル名',
        'no_records' => 'まだバックアップは作成されていません。',
    ],

    // 対象
    'targets' => [
        'database' => 'データベース',
        'media' => 'メディア',
        'private' => 'プライベート',
        'custom' => 'カスタム',
        'logs' => 'ログ',
        'core_source' => 'コアソース',
        'plugins_all' => 'プラグイン',
        'themes_all' => 'テーマ',
    ],

    // 更新／復元フローが自動取得するバックアップに付ける自動メモ。
    // あとから手動で編集できる。
    'auto_note' => [
        'pre_extension_update' => 'アップデート前のバックアップ: :names。このアップデートを取り消して元の状態に戻す場合は、このバックアップから復元してください。',
        'pre_core_update' => 'コアのアップデート前のバックアップ (v:current → v:available)。このアップデートを取り消して元の状態に戻す場合は、このバックアップから復元してください。',
        'core_update_db' => 'コアアップデート適用前のデータベーススナップショット (v:current → v:version)',
        'pre_restore' => 'バックアップ #:id の復元前に自動取得した安全スナップショット。直前の復元を取り消して元の状態に戻す場合は、このバックアップから復元してください。',
    ],

    // タイプ
    'types' => [
        'full' => 'フル',
        'database' => 'データベースのみ',
        'files' => 'ファイルのみ',
    ],

    // ステータス
    'statuses' => [
        'completed' => '完了',
        'failed' => '失敗',
        'expired' => '期限切れ',
        'deleted' => '削除済み',
    ],

    // 検証ステータス
    'verifications' => [
        'unchecked' => '未検証',
        'valid' => '有効',
        'invalid' => '無効',
    ],

    // アクションボタン
    'actions' => [
        'detail' => '詳細',
        'download' => 'ダウンロード',
        'restore' => '復元',
        'delete' => '削除',
    ],

    // 詳細画面（メタデータ + 編集可能なメモ）
    'detail' => [
        'heading' => 'バックアップの詳細',
        'back' => 'バックアップ一覧へ戻る',
        'created_at' => '作成日時',
        'status' => 'ステータス',
        'targets' => '対象',
        'size' => 'サイズ',
        'file_name' => 'ファイル名',
        'path' => '保存先パス',
        'hash' => 'ハッシュ',
        'retention' => '保持期限',
        'retention_none' => '無期限で保持',
        'duration' => '所要時間',
        'file_missing' => 'このバックアップのアーカイブファイルはディスク上に存在しません。',
        'note_label' => 'メモ',
        'note_help' => 'このバックアップ用の自由記述メモです。更新時に取得したバックアップには自動でメモが入ります。ここで編集できます。',
        'note_placeholder' => '例: トップページ編集前の手動バックアップ',
        'note_save' => 'メモを保存',
    ],

    // フラッシュメッセージ
    'flash' => [
        'create_success' => 'バックアップを作成しました（:size、:duration 秒）。',
        'create_failed' => 'バックアップの作成に失敗しました: :error',
        'delete_success' => 'バックアップを削除しました。',
        'delete_failed' => 'バックアップの削除に失敗しました。',
        'download_failed' => 'バックアップファイルが見つかりません。',
        'restore_success' => '復元が完了しました（:duration 秒）。セーフティスナップショットが自動取得されています。',
        'restore_failed' => '復元に失敗しました: :error',
        'restore_unavailable' => 'このバックアップは復元できません。',
        'note_updated' => 'バックアップのメモを更新しました。',
    ],

    // バリデーション
    'validation' => [
        'targets_required' => 'バックアップ対象を1つ以上選択してください。',
        'target_invalid' => '選択したバックアップ対象が無効です。',
        'retention_invalid' => '保持期間は1〜3650日の整数で指定してください。',
    ],

    'placeholder' => 'このページは現在構築中です。完全なUIは今後のリリースで提供されます。',
];
