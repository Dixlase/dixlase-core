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
    // パンくず用
    'heading' => 'リビジョン',
    'description' => 'フロントページの編集履歴を確認し、過去のバージョンへ復元できます。',

    // 一覧ページ
    'index' => [
        'heading' => 'リビジョン履歴',
        'description' => 'フロントページの編集履歴を確認し、過去のバージョンへ復元できます。',
    ],

    // 詳細ページ
    'show' => [
        'heading' => 'リビジョン詳細',
        'description' => '選択したリビジョンと現在の内容の差分を表示します。',
    ],

    'back_to_edit' => '編集画面に戻る',
    'no_revisions' => 'リビジョンはまだ記録されていません。',
    'created_at' => '作成日時',
    'type' => '種別',
    'creator' => '作成者',
    'note' => 'メモ',
    'note_placeholder' => '例: デザイン変更前のバージョン',
    'note_help' => 'このリビジョンに任意の説明を追加できます（500文字以内）。',
    'note_save' => 'メモを保存',
    'note_updated' => 'メモを更新しました。',
    'note_confirm_title' => 'メモを保存',
    'note_confirm_message' => 'このリビジョンのメモを更新しますか？',

    'protect' => '保護',
    'protect_label_on' => '保護中',
    'protect_label_off' => '保護',
    'protect_enable' => '保護する',
    'protect_disable' => '保護を解除',
    'protect_enabled' => 'リビジョンを保護しました。自動削除の対象外になります。',
    'protect_disabled' => 'リビジョンの保護を解除しました。',
    'protect_count_summary' => '保護中 :protected 件 / 保持上限 :retention 件',
    'protect_count_summary_over' => '保護中 :protected 件（保持上限 :retention 件を超過中）',
    'protect_help' => '保護されたリビジョンは、保持件数を超えても自動削除の対象外となります。',
    'actions' => '操作',
    'view_diff' => '差分を見る',
    'restore' => 'このバージョンに戻す',
    'restore_confirm_title' => 'リビジョンから復元',
    'restore_confirm_message' => '選択したリビジョンの内容でフロントページを上書きします。現在の内容は自動的にバックアップされます。続行しますか？',
    'restore_success' => 'リビジョンから復元しました。',

    'type_auto' => '自動保存',
    'type_manual' => '手動保存',
    'type_restore_backup' => '復元前バックアップ',

    'diff_heading' => 'このリビジョンと現在の内容の差分',
    'diff_field_title' => 'タイトル',
    'diff_field_content' => '本文',
    'diff_field_custom_js' => 'カスタム JavaScript',
    'diff_field_custom_css' => 'カスタム CSS',
    'diff_no_changes' => 'このリビジョンと現在の内容に差分はありません。',
    'diff_meta_heading' => 'メタデータの変更',
    'diff_field_storage_type' => '保存形式',
    'diff_field_editor_type' => 'エディタータイプ',
    'diff_field_status' => '公開状態',
    'diff_left_label' => 'このリビジョン',
    'diff_right_label' => '現在',
    'unknown_user' => '不明',
];
