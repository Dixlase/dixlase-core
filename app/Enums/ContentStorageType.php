<?php

/**
 * This file is part of Dixlase.
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

namespace App\Enums;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * コンテンツ保存方法の列挙型
 *
 * ページコンテンツやフロントページのデザインなど、
 * 編集可能なコンテンツの保存方法を定義します。
 */
enum ContentStorageType: int
{
    /**
     * データベースに保存
     * - DBのcontentカラムなどに保存
     * - 管理画面から直接編集
     * - バックアップはDB経由
     */
    case DATABASE = 0;

    /**
     * ファイルとして保存
     * - storage/app/pages/{slug}.blade.php などに保存
     * - ローカルエディタで直接編集可能
     * - Gitでバージョン管理可能
     */
    case FILE = 1;

    /**
     * 旧文字列識別子（スラッグ）を取得
     *
     * JS/Alpine.jsとの互換性維持に使用。
     * フォームの値やJSに渡す場合はこのメソッドを使用する。
     */
    public function slug(): string
    {
        return match ($this) {
            self::DATABASE => 'database',
            self::FILE => 'file',
        };
    }

    /**
     * スラッグ文字列からEnumインスタンスを取得
     *
     * @throws \ValueError スラッグが見つからない場合
     */
    public static function fromSlug(string $slug): self
    {
        foreach (self::cases() as $case) {
            if ($case->slug() === $slug) {
                return $case;
            }
        }

        throw new \ValueError("\"$slug\" is not a valid slug for ".self::class);
    }

    /**
     * スラッグ文字列からEnumインスタンスを取得（失敗時はnull）
     */
    public static function tryFromSlug(string $slug): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->slug() === $slug) {
                return $case;
            }
        }

        return null;
    }

    /**
     * 翻訳キーを取得
     */
    public function translationKey(): string
    {
        return match ($this) {
            self::DATABASE => 'common.content_storage.database',
            self::FILE => 'common.content_storage.file',
        };
    }

    /**
     * 説明の翻訳キーを取得
     */
    public function descriptionKey(): string
    {
        return match ($this) {
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
            self::DATABASE->slug() => __('common.content_storage.database'),
            self::FILE->slug() => __('common.content_storage.file'),
        ];
    }

    /**
     * すべての選択肢を説明付きで取得
     */
    public static function optionsWithDescription(): array
    {
        return [
            self::DATABASE->slug() => [
                'label' => __('common.content_storage.database'),
                'description' => __('common.content_storage.database_description'),
            ],
            self::FILE->slug() => [
                'label' => __('common.content_storage.file'),
                'description' => __('common.content_storage.file_description'),
            ],
        ];
    }
}
