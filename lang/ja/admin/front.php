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
    // パンくず用（ナビゲーションラベルと同じ）
    'heading' => 'フロントページ管理',
    'description' => 'フロントページのコンテンツを管理します。',

    'index' => [
        'heading' => 'フロントページマスター',
        'description' => 'フロントページのコンテンツを管理します。作成・編集・リセットが行えます。',

        'content_exists_title' => 'フロントページコンテンツ',
        'language' => '言語',
        'editor_type' => 'エディタータイプ',
        'storage_type' => '保存方法',
        'last_updated' => '最終更新',
        'status' => 'ステータス',

        'edit_button' => 'コンテンツを編集',
        'reset_button' => 'リセット',
        'reset_confirm_title' => 'フロントページのリセット',
        'reset_confirm' => 'フロントページのコンテンツをリセットしますか？すべてのリビジョン履歴も削除されます。この操作は元に戻せません。',

        'no_content_title' => 'コンテンツがありません',
        'no_content_description' => 'フロントページのコンテンツはまだ作成されていません。下のボタンから作成してください。',
        'create_button' => 'フロントページを作成',

        'reset_success' => 'フロントページのコンテンツをリセットしました。',
    ],

    'create' => [
        'heading' => 'フロントページ作成',
        'description' => '言語とエディタータイプを選んでフロントページのコンテンツを作成します。',

        'lang_label' => '言語',
        'editor_type_label' => 'エディタータイプ',
        'editor_type_help' => 'エディタータイプは新規作成時のみ選択でき、保存後は変更できません。',
        'content_label' => 'コンテンツ',
        'content_placeholder' => 'フロントページのコンテンツを入力...',

        'custom_css_placeholder' => 'カスタムCSSスタイルを入力...',
        'custom_js_placeholder' => 'カスタムJavaScriptを入力...',

        'confirm_title' => 'フロントページを作成',
        'confirm_message' => '選択した設定でフロントページのコンテンツを作成しますか？',

        'create_success' => 'フロントページのコンテンツを作成しました。',

        'sidebar_open' => 'サイドバーを開く',
        'sidebar_close' => 'サイドバーを閉じる',

        'validation' => [
            'lang_required' => '言語を選択してください。',
            'lang_in' => '選択された言語はサポートされていません。',
            'editor_type_required' => 'エディタータイプを選択してください。',
            'editor_type_in' => '選択されたエディタータイプは無効です。',
            'storage_type_required' => '保存方法を選択してください。',
            'storage_type_in' => '選択された保存方法は無効です。',
            'content_max' => 'コンテンツは500,000文字以内で入力してください。',
            'custom_js_max' => 'JavaScriptは500,000文字以内で入力してください。',
            'custom_css_max' => 'CSSは500,000文字以内で入力してください。',
        ],
    ],

    'edit' => [
        'heading' => 'フロントページ編集',
        'description' => 'フロントページのコンテンツを編集します。',

        'meta_section' => 'メタ情報',
        'revisions_section' => 'リビジョン',
        'revisions_button' => 'リビジョン履歴',
        'storage_locked_help' => '保存形式は初回作成時のみ選択できます。変更するにはリセットして再作成してください。',
        'storage_file_path' => 'ファイルパス:',
        'editor_type_label' => 'エディタータイプ',
        'lang_label' => '言語',
        'content_label' => 'コンテンツ',
        'content_placeholder' => 'フロントページのコンテンツを入力...',

        'custom_css_placeholder' => 'カスタムCSSスタイルを入力...',
        'custom_js_placeholder' => 'カスタムJavaScriptを入力...',

        'confirm_title' => '変更を保存',
        'confirm_message' => 'フロントページのコンテンツへの変更を保存しますか？',

        'save_success' => 'フロントページのコンテンツを更新しました。',

        'sidebar_open' => 'サイドバーを開く',
        'sidebar_close' => 'サイドバーを閉じる',

        'preview_title' => 'プレビュー',
        'preview_show' => 'プレビューを表示',
        'preview_hide' => 'プレビューを非表示',
        'scroll_to_editor' => 'エディタに移動',
        'scroll_to_preview' => 'プレビューに移動',
        'device_mobile' => 'モバイル',
        'device_tablet' => 'タブレット',
        'device_desktop' => 'デスクトップ',
        'device_free' => 'フリーサイズ',
        'preview_width' => '幅',
        'preview_height' => '高さ',

        'reset_section_title' => '危険な操作',
        'reset_description' => 'フロントページのコンテンツをリセットします。この操作は元に戻せません。',
        'reset_button' => 'リセット',
        'reset_confirm_title' => 'フロントページのリセット',
        'reset_confirm' => 'フロントページのコンテンツをリセットしますか？すべてのコンテンツ、CSS、JavaScript、およびリビジョン履歴が完全に削除されます。',

        'validation' => [
            'content_max' => 'コンテンツは500,000文字以内で入力してください。',
            'custom_js_max' => 'JavaScriptは500,000文字以内で入力してください。',
            'custom_css_max' => 'CSSは500,000文字以内で入力してください。',
        ],
    ],

    'settings' => [
        'heading' => 'フロントページ設定',
        'description' => 'フロントページの設定を行います。',

        'no_settings' => '現在、追加の設定項目はありません。',

        'settings_updated' => 'フロントページの設定を更新しました。',

        'confirm_title' => '設定を保存',
        'confirm_message' => 'フロントページの設定を保存しますか？',
    ],

];
