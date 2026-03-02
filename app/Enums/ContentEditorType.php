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
 * コンテンツエディタータイプの列挙型
 *
 * ページコンテンツやフロントページのデザインなど、
 * 編集可能なコンテンツの編集方法を定義します。
 */
enum ContentEditorType: int
{
    /**
     * GUIエディタ（将来実装）
     * - ブロックエディタやWYSIWYGエディタ
     * - ドラッグ&ドロップでレイアウト構築
     * - 技術知識不要
     */
    case GUI = 1;

    /**
     * Markdown形式
     * - Markdown記法で記述
     * - プレビュー機能付き
     * - シンプルで学習コスト低
     */
    case MARKDOWN = 2;

    /**
     * HTML直接編集
     * - HTMLタグを直接記述
     * - 完全な制御が可能
     * - 技術知識必要
     */
    case HTML = 3;

    /**
     * Bladeテンプレート（FILE保存時のみ）
     * - Laravel Blade記法で記述
     * - 動的コンテンツ対応
     * - 最も柔軟だが技術知識必須
     */
    case BLADE = 4;

    /**
     * 旧文字列識別子（スラッグ）を取得
     *
     * JS/Alpine.jsとの互換性維持に使用。
     * フォームの値やJSに渡す場合はこのメソッドを使用する。
     */
    public function slug(): string
    {
        return match ($this) {
            self::GUI => 'gui',
            self::MARKDOWN => 'markdown',
            self::HTML => 'html',
            self::BLADE => 'blade',
        };
    }

    /**
     * スラッグ文字列からEnumインスタンスを取得
     *
     * フォーム送信値やJSから送られる文字列をEnumに変換する。
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
     * Font Awesome アイコンクラスを取得
     */
    public function iconClass(): string
    {
        return match ($this) {
            self::GUI => 'fas fa-paint-brush',
            self::HTML => 'fas fa-code',
            self::MARKDOWN => 'fab fa-markdown',
            self::BLADE => 'fas fa-file-code',
        };
    }

    /**
     * アイコンのカラーを取得
     */
    public function iconColor(): string
    {
        return match ($this) {
            self::GUI => '#9333ea',
            self::MARKDOWN => '#2563eb',
            self::HTML => '#ea580c',
            self::BLADE => '#16a34a',
        };
    }

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
                self::HTML,     // HTMLはDATABASEまたはFILE
                self::MARKDOWN, // MarkdownはDATABASEまたはFILE
            ],
            ContentStorageType::FILE => [
                self::BLADE,    // BladeはFILEのみ（.blade.phpファイルが必要）
                self::HTML,     // HTMLはDATABASEまたはFILE
                self::MARKDOWN, // MarkdownはDATABASEまたはFILE
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
            self::GUI->slug() => __('common.content_editor.gui'),
            self::MARKDOWN->slug() => __('common.content_editor.markdown'),
            self::HTML->slug() => __('common.content_editor.html'),
            self::BLADE->slug() => __('common.content_editor.blade'),
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
            $options[$type->slug()] = __($type->translationKey());
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
            $options[$type->slug()] = [
                'label' => __($type->translationKey()),
                'description' => __($type->descriptionKey()),
            ];
        }

        return $options;
    }

    /**
     * ラジオカードグループ用のオプション配列を取得
     *
     * <x-form-radio-card-group> コンポーネントに直接渡せる形式で返す。
     * GUIエディタは将来実装のため disabled + Coming Soon バッジ付き。
     *
     * @param  ContentStorageType|null  $storageType  保存方法でフィルタ（nullの場合は全て）
     * @param  array<string>  $exclude  除外するエディタータイプ値
     * @return array<int, array{value: string, label: string, icon: string, description: string, disabled?: bool, badge?: string, badgeColor?: string}>
     */
    public static function radioCardOptions(?ContentStorageType $storageType = null, array $exclude = []): array
    {
        $types = $storageType ? self::availableFor($storageType) : self::cases();
        $options = [];

        foreach ($types as $type) {
            if (in_array($type->slug(), $exclude, true)) {
                continue;
            }

            $option = [
                'value' => $type->slug(),
                'label' => __($type->translationKey()),
                'icon' => $type->iconClass(),
                'description' => __($type->descriptionKey()),
            ];

            // GUIは将来実装のため無効化
            if ($type === self::GUI) {
                $option['disabled'] = true;
                $option['badge'] = __('common.content_editor.coming_soon_badge');
                $option['badgeColor'] = 'gray';
            }

            $options[] = $option;
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
