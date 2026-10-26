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
    'label' => 'タグ',
    'heading' => 'タグ管理',
    'description' => 'メディアライブラリのタグを追加・名前変更・削除します。',
    'empty' => 'タグはまだありません。ここで追加するか、ファイルのページや選択モードで新しい名前を入力すると作られます。',
    'files_count' => ':count件',

    'filter' => [
        'all' => 'すべて',
        'manage' => 'タグを管理',
        'option' => ':name（:count）',
    ],

    'list' => [
        'name' => 'タグ名',
        'files' => 'ファイル数',
        'actions' => '操作',
        'show_files' => ':name のファイルを表示',
    ],

    'create' => [
        'field' => '新しいタグ',
        'placeholder' => 'タグ名を入力',
        'submit' => '追加',
    ],

    'input' => [
        'placeholder' => 'タグを追加…',
        'create' => '「:name」を新しいタグとして作成',
        'remove' => ':name を外す',
        'no_match' => '一致するタグがありません。',
    ],

    'rename' => [
        'action' => ':name の名前を変更',
        'title' => 'タグの名前を変更',
        'field' => 'タグ名',
        'confirm' => '変更',
    ],

    'delete' => [
        'action' => ':name を削除',
        'title' => 'タグを削除',
        'message' => 'タグ「:name」を削除しますか？<br>:count件のファイルからこのタグが外れます。ファイル自体は残ります。',
    ],

    'bulk' => [
        'add' => 'タグを付ける',
        'remove' => 'タグを外す',
        'add_title' => '選択したファイルにタグを付ける',
        'remove_title' => '選択したファイルからタグを外す',
        'add_message' => '選択した<strong>:count</strong>件のファイルにタグを付けます。',
        'remove_message' => '選択した<strong>:count</strong>件のファイルからタグを外します。',
        'apply' => '適用',
    ],

    'success' => [
        'bulk_updated' => ':count件のファイルのタグを更新しました。',
        'created' => 'タグを追加しました。',
        'renamed' => 'タグの名前を変更しました。',
        'deleted' => 'タグを削除しました。',
    ],

    'error' => [
        'name_required' => 'タグ名を入力してください。',
        'name_taken' => '「:name」というタグはすでにあります。',
        'nothing_to_change' => 'タグを1つ以上選んでください。',
        'bulk_partial' => ':count件のファイルのタグを更新できませんでした。',
    ],
];
