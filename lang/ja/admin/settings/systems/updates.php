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
        'current_version' => '現在のバージョン: v:version',
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
