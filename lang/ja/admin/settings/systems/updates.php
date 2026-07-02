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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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
    'apply_one' => '更新',
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
        'update_button' => 'コアをアップデート',
        'update_started' => 'コアの v:version へのアップグレードを開始しました。完了までおよそ 1〜2 分かかります。このページを再読込すると結果を確認できます。',
        'no_update_to_apply' => '現在、コアのアップデートはありません。',
        'exec_disabled' => 'このサーバでは PHP の exec() が無効化されているため、Web UI からコアのアップグレードを開始できません。下記の CLI コマンドを使用してください。',
        'php_cli_not_found' => 'このサーバ上で CLI 版の php バイナリを特定できなかったため、Web UI からコアのアップグレードを開始できません。下記の CLI コマンドを使用してください。',
        'update_failed_heading' => '前回のコアアップグレードに失敗しました',
        'in_progress_title' => 'コアアップデート実行中',
        'in_progress_message' => 'v:version へアップグレード中です。完了次第、管理画面が再び利用可能になります。',
        'in_progress_elapsed' => '経過時間: :min 分 :sec 秒',
        'in_progress_refresh_note' => 'このページは 10 秒ごとに自動更新されます。',
        'cli_alternative_heading' => 'ターミナルから実行する場合',
        'cli_alternative_intro' => 'Web UI からアップグレードを実行できない場合、または手動で実行・出力を確認したい場合は、下記のコマンドをコピーしてサーバ上で実行してください。',
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
        'action' => '操作',
    ],

    // フラッシュメッセージ
    'messages' => [
        'check_done' => 'アップデートチェックを完了しました。',
        'check_failed' => 'アップデートチェックに失敗しました: :error',
        'no_selection' => '更新対象が選択されていません。',
        'apply_summary' => ':total 件中 :succeeded 件成功 / :failed 件失敗',
        'backup_failed' => '更新前のバックアップに失敗したため、アップデートを中止しました。エラー: :error',
        'update_started' => 'アップデートを開始しました。完了するまでこのページは自動で再読み込みされます。',
    ],

    // デタッチ実行中のプラグイン／テーマ更新を待つポーリング用プレースホルダ
    'extension_in_progress' => [
        'title' => 'アップデートを実行中...',
        'message' => ':count 件のアップデートを適用しています。テーマのアップデートはフロントエンドアセットを再ビルドするため、数分かかることがあります。',
    ],

    // 更新前のバックアップ推奨
    'backup' => [
        'recommendation_title' => 'アップデート前にバックアップを取ることを推奨します',
        'recommendation_body' => 'アップデートが途中で失敗すると、ファイルや DB スキーマが不整合な状態で残ることがあります。先にバックアップを取っておくと、問題が起きたときにバックアップ管理画面から復元できます。',
        'recommendation_link' => 'バックアップ管理画面を開く',
        'checkbox_label' => '先にバックアップを取る',
    ],

    // コア更新確認モーダルで「先にバックアップを取る」チェックボックス
    // の下に表示する控えめな補足。自動バックアップに何が含まれるかを
    // バックアップ管理画面を別途開かずに把握できるようにする。
    'core_confirm_backup_note' => 'バックアップに含まれる項目: データベース + コアソース + テーマ。',

    // 確認モーダル (一括適用)
    'confirm' => [
        'title' => 'アップデートを実行',
        'message' => '選択された :count 件のアップデートを順次適用します。よろしいですか？',
    ],

    // 「今すぐ確認」リクエスト中に表示する実行中モーダル
    'checking' => [
        'title' => 'アップデートを確認中...',
        'message' => 'アップデートソースに問い合わせています。このページを閉じないでください。',
    ],

    // 実行中モーダル（一括適用リクエストが処理中で、ページがまだリダイレクト
    // していない間に表示される。各行の個別「更新」ボタンの経路もこの同じ
    // モーダルを開き、一括適用と同じ in-flight UX になる）
    'in_progress' => [
        'title' => 'アップデートの準備中…',
        'backup_phase' => 'アップデート前のバックアップを取得しています…',
        'starting_phase' => 'アップデートを開始しています…',
        'description_line1' => 'このページを閉じないでください。',
        'description_line2' => 'しばらくお待ちください。',
    ],

    // 確認モーダル (各行の個別「更新」ボタン)
    'single_confirm' => [
        'title' => 'アップデートを実行',
        'message' => ':name をアップデートしますか？',
    ],

    // 確認モーダル (コアの「更新」ボタン — single_confirm と分けて、
    // v:current → v:available のバージョン遷移を明示できるように
    // メッセージを別に持つ)
    'core_confirm' => [
        'title' => 'コアをアップデート',
        'message' => 'コアを v:current から v:available にアップデートします。よろしいですか？',
    ],

    // リリースノート（GitHub Releases の body を Markdown として
    // 各更新項目の下に展開表示する）
    'release_notes' => [
        'heading' => 'リリースノート',
        'show' => 'リリースノートを表示',
        'hide' => 'リリースノートを隠す',
        'empty' => 'このリリースにはリリースノートが添付されていません。',
    ],
];
