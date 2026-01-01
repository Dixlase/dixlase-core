<?php

/**
 * This file is part of Dixlase.
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
        'per_page_label' => '表示件数',
        'total_count' => '全:total件',
        'total_pages' => '全 :count ページ',
        'no_results' => '該当するデータがありません',
        'items_suffix' => '件',
        'sort_by' => '並び替え',
        'asc' => '昇順',
        'desc' => '降順',
        'ascending' => '昇順（小→大、古→新）',
        'descending' => '降順（大→小、新→古）',
    ],


    // フォーム関連
    'forms' => [
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
            'unique' => 'この値は既に存在しています',
            'min_length' => '最低 :min 文字以上で入力してください',
            'max_length' => '最大 :max 文字以内で入力してください',
            'confirmed' => 'パスワード確認が一致しません',
        ],
    ],

    // フィルター関連
    'filters' => [
        'search_keyword' => 'キーワード',
        'role_filter' => '権限フィルター',
        'status_filter' => 'ステータスフィルター',
        'clear_button' => 'クリア',
    ],

    // ステータス関連
    'status' => [
        'active' => '有効',
        'inactive' => '無効',
        'enabled' => '有効',
        'disabled' => '無効',
        'draft' => '下書き',
        'published' => '公開',
        'scheduled' => '日付指定',
        'pending' => '保留中',
        'approved' => '承認済み',
        'rejected' => '却下',
        'cancelled' => 'キャンセル',
        // 説明
        'draft_description' => '下書き状態です。公開されません。',
        'published_description' => '即座に公開されます。',
        'scheduled_description' => '指定した日時に公開されます。',
    ],

    // メッセージ関連
    'messages' => [
        'success' => '操作が正常に完了しました',
        'loading' => '読み込み中...',
        'no_data' => 'データがありません',
        'confirm_delete' => '本当に削除しますか？',
        'unsaved_changes' => '保存されていない変更があります',
    ],

    // テーブル関連
    'table' => [
        'no_data' => 'データがありません',
        'select_all' => 'すべて選択',
        'selected_count' => ':count 件選択中',
        'sort_asc' => '昇順でソート',
        'sort_desc' => '降順でソート',
        'caption' => 'データ一覧',
        'unknown_role' => '不明なロール',
    ],

    // モーダル関連
    'modal' => [
        'delete_title' => '削除の確認',
        'delete_message' => 'この操作は取り消せません。本当に削除しますか？',
    ],

    // パスワードツール関連
    'password_messages' => [
        'strength' => [
            'error' => 'パスワードが条件を満たしていません',
            'normal' => '普通の強度',
            'strong' => '強いパスワード',
        ],
        'tooltip' => [
            'generate' => '自動生成',
            'toggle' => '表示切替',
        ],
        'copied' => 'パスワードがコピーされました！',
        'requirements' => [
            // 表示用（固定文言）
            'length' => '8文字以上',
            'lowercase' => '小文字を1文字以上含む',
            'number' => '数字を1文字以上含む',
            'uppercase' => '大文字を1文字以上含む',
            'symbol' => '記号（!@#$%^&* など）を1文字以上含む',

            // 可変メッセージ（パラメータ付き）
            'length_full' => ':min文字以上（推奨 :recommended 文字以上）',
            'length_simple' => ':min文字以上',

            // 任意の場合の特別メッセージ
            'lowercase_optional_note' => '小文字を含む',
            'number_optional_note' => '数字を含む',
            'uppercase_optional_note' => '大文字を含む',
            'symbol_optional_note' => '記号（!@#$%^&*-_=+など）',

            // 強度ラベル
            'weak' => '弱い',
            'normal' => '普通',
            'strong' => '強い',
            'very_strong' => '非常に強い',
        ],
        'error' => 'パスワードが条件を満たしていません。',
    ],
];
