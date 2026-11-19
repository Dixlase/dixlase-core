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
        'exclusions_label' => '除外設定（プラグイン／テーマ対象）',
        'exclude_node_modules_label' => 'node_modules を除外',
        'exclude_node_modules_help' => 'ビルド時にのみ使用されるパッケージキャッシュで、サイトの動作には不要です。',
        'exclude_vendor_label' => 'vendor を除外',
        'exclude_vendor_help' => '除外すると、復元時にプラグイン／テーマのソースと依存ライブラリのバージョンが合わなくなる可能性があります。完全な状態で復元したい場合はオフにしてください。',
        'exclusions_help' => 'バックアップ時に除外したディレクトリは、そのバックアップの復元時にも削除・上書きされず保護されます。.git は常に除外・保護されます。',
        'default_retention_label' => 'デフォルト保持期間（日）',
        'default_retention_help' => 'この期間を過ぎたバックアップは、毎日の整理で削除されます。定期バックアップにも使います。空欄にすると、手動のバックアップは無期限、定期バックアップは 14 日間残します。変更は、そのあとに作るバックアップから効きます。',
        'save_button' => '設定を保存',
    ],

    'schedule' => [
        'label' => '定期バックアップ',
        'enabled_label' => '自動でバックアップを取る',
        'enabled_help' => ':time(サーバー時刻)に実行し、上の既定の保持期間に従って :days 日間残します。スケジューラー(cron)が動いている必要があります。',
        'frequency_label' => '頻度',
        'frequency_daily' => '毎日',
        'frequency_weekly' => '毎週(日曜日)',
        'targets_label' => '定期バックアップの対象',
        'targets_help' => 'メディアは大きくなりがちです。毎日のバックアップに含めると、保持期間の日数ぶんディスクを使います。',
        'failed_title' => '定期バックアップに失敗しました',
        'failed_message' => '定期バックアップが :count 回続けて失敗しています: :error',
        'failed_mail' => 'このサイトの定期バックアップに失敗しました: :error。定期バックアップがまた成功するまで、新しいバックアップは作られません。バックアップの画面と保存領域の空きを確認してください。',
        'open_backups' => 'バックアップを開く',
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
        'schedule_targets_required' => '定期バックアップの対象を1つ以上選択してください。',
        'schedule_frequency_invalid' => '選択した頻度が無効です。',
    ],

    'placeholder' => 'このページは現在構築中です。完全なUIは今後のリリースで提供されます。',
];
