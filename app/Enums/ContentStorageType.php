<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

namespace App\Enums;

/**
 * コンテンツ保存方法の列挙型
 * 
 * ページコンテンツやフロントページのデザインなど、
 * 編集可能なコンテンツの保存方法を定義します。
 */
enum ContentStorageType: string
{
    /**
     * データベースに保存
     * - DBのcontentカラムなどに保存
     * - 管理画面から直接編集
     * - バックアップはDB経由
     */
    case DATABASE = 'database';

    /**
     * ファイルとして保存
     * - storage/app/pages/{slug}.blade.php などに保存
     * - ローカルエディタで直接編集可能
     * - Gitでバージョン管理可能
     */
    case FILE = 'file';

    /**
     * 翻訳キーを取得
     */
    public function translationKey(): string
    {
        return match($this) {
            self::DATABASE => 'common.content_storage.database',
            self::FILE => 'common.content_storage.file',
        };
    }

    /**
     * 説明の翻訳キーを取得
     */
    public function descriptionKey(): string
    {
        return match($this) {
            self::DATABASE => 'common.content_storage.database_description',
            self::FILE => 'common.content_storage.file_description',
        };
    }

    /**
     * すべての選択肢を取得
     */
    public static function options(): array
    {
        return [
            self::DATABASE->value => __('common.content_storage.database'),
            self::FILE->value => __('common.content_storage.file'),
        ];
    }

    /**
     * すべての選択肢を説明付きで取得
     */
    public static function optionsWithDescription(): array
    {
        return [
            self::DATABASE->value => [
                'label' => __('common.content_storage.database'),
                'description' => __('common.content_storage.database_description'),
            ],
            self::FILE->value => [
                'label' => __('common.content_storage.file'),
                'description' => __('common.content_storage.file_description'),
            ],
        ];
    }
}
