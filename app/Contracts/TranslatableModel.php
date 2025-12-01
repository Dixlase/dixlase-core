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

namespace App\Contracts;

/**
 * 翻訳対象モデルインターフェイス
 * 
 * 多言語対応が必要なモデルはこのインターフェイスを実装します。
 * これにより、多言語プラグインがどのフィールドを翻訳対象とするかを
 * 自動的に検出できます。
 * 
 * @example
 * class Page extends Model implements TranslatableModel
 * {
 *     use HasTranslations;
 * 
 *     public function getTranslatableAttributes(): array
 *     {
 *         return ['title', 'content', 'meta_description'];
 *     }
 * 
 *     public function getTranslationLabelAttribute(): string
 *     {
 *         return 'title';
 *     }
 * }
 */
interface TranslatableModel
{
    /**
     * 翻訳対象の属性名一覧を取得
     * 
     * @return array ['title', 'content', 'meta_description', ...]
     */
    public function getTranslatableAttributes(): array;

    /**
     * 翻訳管理UIで表示するラベル用の属性名を取得
     * 
     * @return string 'title' など
     */
    public function getTranslationLabelAttribute(): string;

    /**
     * 翻訳リレーションを取得
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function translations(): \Illuminate\Database\Eloquent\Relations\HasMany;

    /**
     * 指定された言語の翻訳が存在するかチェック
     * 
     * @param string $locale
     * @return bool
     */
    public function hasTranslation(string $locale): bool;

    /**
     * 利用可能な言語一覧を取得
     * 
     * @return array
     */
    public function availableLocales(): array;
}
