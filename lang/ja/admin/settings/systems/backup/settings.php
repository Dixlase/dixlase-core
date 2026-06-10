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
    'heading' => 'バックアップ設定',
    'description' => 'デフォルトのバックアップ対象、保持期間、アプリケーションログを含めるかを設定します。',

    'form' => [
        'default_targets_label' => 'デフォルトのバックアップ対象',
        'default_targets_help' => 'ここで選択した対象は、新規バックアップ作成時に初期チェック状態になります。「ログ」はファイル変更が頻繁でサイズが大きくなりやすいため、オプション扱いです。',
        'default_retention_label' => 'デフォルト保持期間（日）',
        'default_retention_help' => 'この期間が過ぎると、データベース管理画面からのクリーンアップ対象になります。空欄でデフォルト無期限保持。',
        'save_button' => '設定を保存',
    ],

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

    'flash' => [
        'update_success' => 'バックアップ設定を保存しました。',
    ],

    'validation' => [
        'targets_required' => 'デフォルトのバックアップ対象を1つ以上選択してください。',
        'target_invalid' => '選択したバックアップ対象が無効です。',
        'retention_invalid' => 'デフォルト保持期間は1〜3650日の整数で指定してください。',
    ],

    'placeholder' => 'このページは現在構築中です。完全なUIは今後のリリースで提供されます。',
];
