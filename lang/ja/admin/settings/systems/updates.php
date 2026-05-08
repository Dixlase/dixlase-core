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
 */

return [
    'heading' => 'アップデート管理',
    'description' => 'コア・プラグイン・テーマのアップデート状況を確認し、選択して一括適用します。',

    // 共通
    'check_now' => 'いますぐ確認',
    'apply_selected' => '選択を更新',
    'select_all' => 'すべて選択',
    'last_checked_at' => '最終チェック: :date',
    'never_checked' => '未チェック',
    'no_updates' => 'すべて最新です。',
    'all_up_to_date' => 'インストール済みの拡張機能はすべて最新バージョンです。',

    // セクション見出し
    'core' => [
        'heading' => 'コア',
        'label' => 'Dixlase コア',
        'current_version' => '現在のバージョン: v:version',
        'update_available' => '更新あり',
        'up_to_date' => 'コアは最新です。',
        'release_notes_link' => 'リリースノートを GitHub で見る',
        'cli_required' => 'コアのアップグレードは、リクエスト処理中に動作中のアプリを置き換えないようターミナルから実行する必要があります。下記コマンドをコピーしてサーバ上で実行してください。',
        'cli_command' => 'docker exec -i dixlase-dev-app php artisan dls:core:update',
        'cli_followups' => '更新完了後、composer.json が変更されていれば `composer install --no-dev`、アセットが変更されていれば `npm install && npm run build` を実行し、PHP-FPM を再起動してください。',
        'execute_not_implemented' => 'コアの更新検知は有効化されましたが、Web UI からのアップグレード実行は別途実装中です。当面は上記の CLI コマンドを使用してください。',
        'not_implemented' => 'コア本体のアップデート機能は別タスクで準備中です。利用可能になり次第、ここに表示されます。',
    ],
    'plugins' => [
        'heading' => 'プラグイン',
        'count' => ':count 件のアップデートが利用可能',
        'none' => 'プラグインのアップデートはありません。',
    ],
    'themes' => [
        'heading' => 'テーマ',
        'count' => ':count 件のアップデートが利用可能',
        'none' => 'テーマのアップデートはありません。',
    ],

    // テーブルヘッダ
    'table' => [
        'name' => '名前',
        'current' => '現在',
        'available' => '利用可能',
    ],

    // フラッシュメッセージ
    'messages' => [
        'check_done' => 'アップデートチェックを完了しました。',
        'check_failed' => 'アップデートチェックに失敗しました: :error',
        'no_selection' => '更新対象が選択されていません。',
        'apply_summary' => ':total 件中 :succeeded 件成功 / :failed 件失敗',
    ],

    // 確認モーダル
    'confirm' => [
        'title' => 'アップデートを実行',
        'message' => '選択された :count 件のアップデートを順次適用します。よろしいですか？',
    ],
];
