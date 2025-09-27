<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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
    // ページネーション関連
    'pagination' => [
        'navigation' => 'ページナビゲーション',
        'page' => ':current ページ / 全 :total ページ',
        'previous' => '前へ',
        'next' => '次へ',
        'first' => '最初',
        'last' => '最後',
        'showing' => ':first から :last を表示（全 :total 件）',
        'per_page' => '表示件数',
        'per_page_label' => '1ページあたりの表示件数',
        'total_items' => '全 :count 件',
        'total_pages' => '全 :count ページ',
        'no_results' => '該当するデータがありません',
    ],


    // フォーム関連
    'forms' => [
        'required' => '必須',
        'optional' => '任意',
        'placeholder' => [
            'search' => '検索キーワードを入力...',
            'email' => 'メールアドレスを入力',
            'password' => 'パスワードを入力',
            'name' => '名前を入力',
            'title' => 'タイトルを入力',
            'description' => '説明を入力',
        ],
        'validation' => [
            'required' => 'この項目は必須です',
            'email' => '有効なメールアドレスを入力してください',
            'min_length' => '最低 :min 文字以上で入力してください',
            'max_length' => '最大 :max 文字以内で入力してください',
        ],
    ],

    // ステータス関連
    'status' => [
        'active' => 'アクティブ',
        'inactive' => '非アクティブ',
        'enabled' => '有効',
        'disabled' => '無効',
        'published' => '公開',
        'draft' => '下書き',
        'scheduled' => '予約投稿',
        'pending' => '保留中',
        'approved' => '承認済み',
        'rejected' => '却下',
        'cancelled' => 'キャンセル',
    ],

    // メッセージ関連
    'messages' => [
        'success' => '操作が正常に完了しました',
        'error' => 'エラーが発生しました',
        'warning' => '警告',
        'info' => '情報',
        'loading' => '読み込み中...',
        'no_data' => 'データがありません',
        'confirm_delete' => '本当に削除しますか？',
        'unsaved_changes' => '保存されていない変更があります',
    ],

    // テーブル関連
    'table' => [
        'actions' => '操作',
        'no_data' => 'データがありません',
        'select_all' => 'すべて選択',
        'selected_count' => ':count 件選択中',
        'sort_asc' => '昇順でソート',
        'sort_desc' => '降順でソート',
    ],

    // モーダル関連
    'modal' => [
        'close' => '閉じる',
        'confirm' => '確認',
        'cancel' => 'キャンセル',
        'save' => '保存',
        'delete_title' => '削除の確認',
        'delete_message' => 'この操作は取り消せません。本当に削除しますか？',
    ],
];
