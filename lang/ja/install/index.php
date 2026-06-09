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
    'welcome' => 'インストールへようこそ',
    'description' => 'インストールを開始する前に、サーバー要件を確認してください。',
    'server_requirements' => 'サーバー要件',
    'required_section' => '必須項目',
    'recommended_section' => '推奨・オプション',
    'category' => [
        'extensions' => '拡張機能',
        'permissions' => 'パーミッション',
        'other' => 'その他',
    ],
    'permissions' => [
        'storage' => 'storageディレクトリが書き込み可能',
        'cache' => 'bootstrap/cacheディレクトリが書き込み可能',
        'writable_required' => '書き込み権限が必要',
    ],
    'php_settings' => [
        'required' => '必要',
        'unlimited' => '無制限',
    ],
    'theme_check' => [
        'label' => 'テーマ',
        'not_found' => 'themes/ ディレクトリにテーマが見つかりません',
    ],
    'theme_download' => [
        'heading' => 'テーマをダウンロード',
        'description' => 'このリリースにはテーマが同梱されていません。下のボタンから公式テーマをダウンロードして続行してください。',
        'button' => ':label をダウンロード',
        'progress_title' => 'テーマをダウンロード中...',
        'progress_message' => 'このページを閉じないでください。<br>しばらくお待ちください。',
        'success' => 'テーマのダウンロードが完了しました。',
        'failed' => 'テーマのダウンロードに失敗しました: :error',
        'invalid' => '指定されたテーマはダウンロード対象として登録されていません。',
    ],
    'start_button' => 'インストールを開始',
];
