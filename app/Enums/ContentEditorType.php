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
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * コンテンツエディタータイプの列挙型
 *
 * ページコンテンツやフロントページのデザインなど、
 * 編集可能なコンテンツの編集方法を定義します。
 */
enum ContentEditorType: string
{
    /**
     * GUIエディタ（将来実装）
     * - ブロックエディタやWYSIWYGエディタ
     * - ドラッグ&ドロップでレイアウト構築
     * - 技術知識不要
     */
    case GUI = 'gui';

    /**
     * Markdown形式
     * - Markdown記法で記述
     * - プレビュー機能付き
     * - シンプルで学習コスト低
     */
    case MARKDOWN = 'markdown';

    /**
     * HTML直接編集
     * - HTMLタグを直接記述
     * - 完全な制御が可能
     * - 技術知識必要
     */
    case HTML = 'html';

    /**
     * Bladeテンプレート（FILE保存時のみ）
     * - Laravel Blade記法で記述
     * - 動的コンテンツ対応
     * - 最も柔軟だが技術知識必須
     */
    case BLADE = 'blade';

    /**
     * 翻訳キーを取得
     */
    public function translationKey(): string
    {
        return match ($this) {
            self::GUI => 'common.content_editor.gui',
            self::MARKDOWN => 'common.content_editor.markdown',
            self::HTML => 'common.content_editor.html',
            self::BLADE => 'common.content_editor.blade',
        };
    }

    /**
     * 説明の翻訳キーを取得
     */
    public function descriptionKey(): string
    {
        return match ($this) {
            self::GUI => 'common.content_editor.gui_description',
            self::MARKDOWN => 'common.content_editor.markdown_description',
            self::HTML => 'common.content_editor.html_description',
            self::BLADE => 'common.content_editor.blade_description',
        };
    }

    /**
     * 指定された保存方法で利用可能なエディタータイプを取得
     *
     * DATABASE: GUI, Markdown, HTML
     * FILE: Blade, Markdown, HTML
     */
    public static function availableFor(ContentStorageType $storageType): array
    {
        return match ($storageType) {
            ContentStorageType::DATABASE => [
                self::GUI,      // GUIはDATABASEのみ（JSON形式で保存）
                self::MARKDOWN, // MarkdownはDATABASEまたはFILE
                self::HTML,     // HTMLはDATABASEまたはFILE
            ],
            ContentStorageType::FILE => [
                self::BLADE,    // BladeはFILEのみ（.blade.phpファイルが必要）
                self::MARKDOWN, // MarkdownはDATABASEまたはFILE
                self::HTML,     // HTMLはDATABASEまたはFILE
            ],
        };
    }

    /**
     * 指定されたエディタータイプで利用可能な保存方法を取得
     */
    public static function availableStorageTypes(self $editorType): array
    {
        return match ($editorType) {
            self::GUI => [ContentStorageType::DATABASE],           // GUIはDATABASEのみ
            self::BLADE => [ContentStorageType::FILE],             // BladeはFILEのみ
            self::MARKDOWN, self::HTML => [                        // Markdown/HTMLは両方OK
                ContentStorageType::DATABASE,
                ContentStorageType::FILE,
            ],
        };
    }

    /**
     * すべての選択肢を取得
     */
    public static function options(): array
    {
        return [
            self::GUI->value => __('common.content_editor.gui'),
            self::MARKDOWN->value => __('common.content_editor.markdown'),
            self::HTML->value => __('common.content_editor.html'),
            self::BLADE->value => __('common.content_editor.blade'),
        ];
    }

    /**
     * 指定された保存方法で利用可能な選択肢を取得
     */
    public static function optionsFor(ContentStorageType $storageType): array
    {
        $available = self::availableFor($storageType);
        $options = [];

        foreach ($available as $type) {
            $options[$type->value] = __($type->translationKey());
        }

        return $options;
    }

    /**
     * 指定された保存方法で利用可能な選択肢を説明付きで取得
     */
    public static function optionsWithDescriptionFor(ContentStorageType $storageType): array
    {
        $available = self::availableFor($storageType);
        $options = [];

        foreach ($available as $type) {
            $options[$type->value] = [
                'label' => __($type->translationKey()),
                'description' => __($type->descriptionKey()),
            ];
        }

        return $options;
    }

    /**
     * ファイル拡張子を取得
     */
    public function fileExtension(): string
    {
        return match ($this) {
            self::GUI => 'json',
            self::MARKDOWN => 'md',
            self::HTML => 'html',
            self::BLADE => 'blade.php',
        };
    }
}
