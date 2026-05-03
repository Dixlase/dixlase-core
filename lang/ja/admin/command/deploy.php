<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

return [

    'init' => [
        'description' => 'dixlase-deploy.json設定ファイルを生成します',
        'already_exists' => '設定ファイルは既に存在します: :path',
        'use_force' => '上書きするには --force オプションを使用してください',
        'generated' => '設定ファイルを生成しました: :path',
        'next_steps' => '次のステップ:',
        'step_edit' => ':path を環境設定に合わせて編集してください',
        'step_env' => '機密情報用の環境変数を設定してください',
        'step_doctor' => "'php artisan deploy:doctor' を実行して設定を検証してください",
        'step_list' => "'php artisan deploy:list' を実行して利用可能な環境を確認してください",
        'gitignore_warning' => "重要: 認証情報を保護するため 'dixlase-deploy.json' を .gitignore に追加してください",
        'failed' => '設定ファイルの生成に失敗しました: :error',
    ],
    'doctor' => [
        'description' => 'デプロイ設定とシステム要件をチェックします',
        'title' => 'Dixlase Deploy Doctor',
        'checking_config' => '設定をチェック中...',
        'config_not_found' => '設定ファイルが見つかりません',
        'config_exists' => '設定ファイルが存在します',
        'config_valid' => '設定は有効です',
        'checking_requirements' => 'システム要件をチェック中...',
        'rsync_installed' => 'rsync がインストールされています',
        'rsync_not_installed' => 'rsync がインストールされていません（SSH同期に必要）',
        'lftp_installed' => 'lftp がインストールされています',
        'lftp_not_installed' => 'lftp がインストールされていません（FTP同期に必要）',
        'ssh_installed' => 'ssh がインストールされています',
        'ssh_not_installed' => 'ssh がインストールされていません',
        'mysql_installed' => 'mysql クライアントがインストールされています',
        'mysql_not_installed' => 'mysql クライアントがインストールされていません（データベース同期に必要）',
        'mysqldump_installed' => 'mysqldump がインストールされています',
        'mysqldump_not_installed' => 'mysqldump がインストールされていません（データベース同期に必要）',
        'gzip_installed' => 'gzip がインストールされています',
        'gzip_not_installed' => 'gzip がインストールされていません',
        'available_environments' => '利用可能な環境:',
        'failed_to_load' => '環境の読み込みに失敗しました: :error',
        'checks_failed' => 'いくつかのチェックが失敗しました。上記の問題を修正してください。',
        'all_passed' => 'すべてのチェックに合格しました！デプロイの準備ができています。',
    ],
    'list' => [
        'description' => '利用可能なデプロイ環境を一覧表示します',
        'config_not_found' => '設定ファイルが見つかりません。',
        'no_environments' => '環境が設定されていません。',
        'title' => '利用可能な環境',
        'local' => 'ローカル:',
        'url' => 'URL:',
        'path' => 'パス:',
        'db' => 'DB:',
        'connection' => '接続:',
        'host' => 'ホスト:',
        'failed' => '設定の読み込みに失敗しました: :error',
    ],
    'push' => [
        'description' => 'ローカルのDixlaseデータをリモート環境にプッシュします',
        'config_not_found' => '設定ファイルが見つかりません。',
        'environment_not_found' => "環境 ':environment' が設定に見つかりません。",
        'available_environments' => '利用可能な環境: :environments',
        'no_targets' => '同期対象が指定されていません。',
        'use_options' => '--plugins, --themes, --custom, --uploads, --database, または --all を使用してください',
        'push_to' => ':environment にプッシュ',
        'targets' => '対象: :targets',
        'tables' => 'テーブル: :tables',
        'dry_run' => '[ドライランモード]',
        'warning_overwrite' => '警告: :vhost のデータベースが上書きされます',
        'confirm' => ':environment にプッシュしてもよろしいですか？',
        'cancelled' => '操作がキャンセルされました。',
    ],
    'pull' => [
        'description' => 'リモート環境からローカルにDixlaseデータをプルします',
        'config_not_found' => '設定ファイルが見つかりません。',
        'environment_not_found' => "環境 ':environment' が設定に見つかりません。",
        'available_environments' => '利用可能な環境: :environments',
        'no_targets' => '同期対象が指定されていません。',
        'use_options' => '--plugins, --themes, --custom, --uploads, --database, または --all を使用してください',
        'pull_from' => ':environment からプル',
        'targets' => '対象: :targets',
        'tables' => 'テーブル: :tables',
        'dry_run' => '[ドライランモード]',
        'warning_overwrite' => '警告: ローカルデータベースが上書きされます',
        'confirm' => ':environment からプルしてもよろしいですか？',
        'cancelled' => '操作がキャンセルされました。',
    ],
    'common' => [
        'starting_push' => ':environment へのプッシュを開始...',
        'starting_pull' => ':environment からのプルを開始...',
        'connecting' => ':connection で接続中',
        'connected' => '接続成功',
        'connection_failed' => ':environment への接続に失敗しました',
        'executing_hooks' => ':timing フックを実行中...',
        'running_command' => '実行中: :command (:where)',
        'hook_failed' => 'フックが失敗しました: :output',
        'hook_output' => '出力: :output',
        'syncing' => ':target を同期中: :path',
        'sync_failed' => ':target の同期に失敗しました',
        'would_sync' => '[ドライラン] :local <-> :remote を同期します',
        'would_sync_db' => '[ドライラン] データベースを同期します',
        'push_completed' => ':environment へのプッシュが正常に完了しました',
        'push_completed_with_errors' => ':environment へのプッシュがエラーで完了しました',
        'pull_completed' => ':environment からのプルが正常に完了しました',
        'pull_completed_with_errors' => ':environment からのプルがエラーで完了しました',
    ],
];
