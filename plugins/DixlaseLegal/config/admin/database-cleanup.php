<?php

/**
 * This file is part of Dixlase Legal.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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

/*
|--------------------------------------------------------------------------
| データベースクリーンアップ設定
|--------------------------------------------------------------------------
|
| プラグインのデータベーステーブルのクリーンアップ設定を定義します。
| 管理画面からクリーンアップ対象テーブルと保持期間を管理できます。
| このファイルが不要な場合は削除してください。
|
*/

return [
    // 'example_logs' => [
    //     // 必須: データベーステーブル名
    //     'table' => 'dixlase-legal_logs',
    //
    //     // 必須: 日付比較に使用するカラム
    //     'date_column' => 'created_at',
    //
    //     // 必須: デフォルトの保持期間（日数、nullの場合は期限切れのみ）
    //     'default_days' => 30,
    //
    //     // 必須: 説明（翻訳キーまたはロケールキー配列）
    //     'description' => [
    //         'ja' => 'プラグインのログ記録',
    //         'en' => 'Plugin log records',
    //     ],
    //
    //     // オプション: このクリーンアップタイプの有効/無効
    //     'enabled' => true,
    //
    //     // オプション: 日付カラムの型 ('datetime' or 'timestamp')
    //     'date_column_type' => 'datetime',
    // ],

    // 'example_cache' => [
    //     'table' => 'dixlase-legal_cache',
    //     'date_column' => 'expires_at',
    //     'default_days' => null,
    //     'description' => [
    //         'ja' => 'プラグインのキャッシュデータ',
    //         'en' => 'Plugin cache data',
    //     ],
    //     'enabled' => true,
    //     'date_column_type' => 'timestamp',
    // ],

    'soft_deleted_legal_pages' => [
        'table' => 'plg_dixlase_legal_pages',
        'date_column' => 'deleted_at',
        'default_days' => 90,
        'description' => [
            'ja' => '削除済み法務ページコンテンツ',
            'en' => 'Soft-deleted legal page contents',
        ],
        'enabled' => true,
        'date_column_type' => 'datetime',
    ],
];