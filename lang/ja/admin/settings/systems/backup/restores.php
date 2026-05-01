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
    'heading' => '復元履歴',
    'description' => '復元操作の履歴を確認し、必要に応じて元の状態にロールバックできます。',

    'table' => [
        'caption' => '復元履歴',
        'restored_at' => '復元日時',
        'backup' => '復元元',
        'targets' => '対象',
        'restored_by' => '実行者',
        'duration' => '所要時間',
        'status' => 'ステータス',
        'actions' => '操作',
        'no_records' => '復元操作はまだ実行されていません。',
        'backup_deleted' => '(削除済み)',
    ],

    'targets' => [
        'database' => 'データベース',
        'media' => 'メディア',
        'private' => 'プライベート',
        'custom' => 'カスタム',
        'logs' => 'ログ',
    ],

    'statuses' => [
        'pending' => '待機中',
        'in_progress' => '実行中',
        'completed' => '完了',
        'failed' => '失敗',
        'rolled_back' => 'ロールバック済み',
    ],

    'actions' => [
        'rollback' => 'ロールバック',
    ],

    'rollback_modal' => [
        'title' => '復元のロールバック',
        'message' => '復元前の状態に戻します（セーフティスナップショットを使用）。現在の状態は上書きされます。続行しますか？',
        'confirm_label' => 'ロールバック',
        'cancel_label' => 'キャンセル',
    ],

    'flash' => [
        'rollback_success' => 'ロールバックが完了しました（:duration 秒）。',
        'rollback_failed' => 'ロールバックに失敗しました: :error',
        'rollback_unavailable' => 'この復元はロールバックできません。',
    ],

    'placeholder' => 'このページは現在構築中です。完全なUIは今後のリリースで提供されます。',
];
