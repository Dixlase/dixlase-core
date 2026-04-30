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
        'no_records' => 'まだバックアップは作成されていません。',
    ],

    // 対象
    'targets' => [
        'database' => 'データベース',
        'media' => 'メディア',
        'private' => 'プライベート',
        'custom' => 'カスタム',
        'logs' => 'ログ',
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
        'download' => 'ダウンロード',
        'delete' => '削除',
    ],

    // フラッシュメッセージ
    'flash' => [
        'create_success' => 'バックアップを作成しました（:size、:duration 秒）。',
        'create_failed' => 'バックアップの作成に失敗しました: :error',
        'delete_success' => 'バックアップを削除しました。',
        'delete_failed' => 'バックアップの削除に失敗しました。',
        'download_failed' => 'バックアップファイルが見つかりません。',
    ],

    // バリデーション
    'validation' => [
        'targets_required' => 'バックアップ対象を1つ以上選択してください。',
        'target_invalid' => '選択したバックアップ対象が無効です。',
        'retention_invalid' => '保持期間は1〜3650日の整数で指定してください。',
    ],

    'placeholder' => 'このページは現在構築中です。完全なUIは今後のリリースで提供されます。',
];
