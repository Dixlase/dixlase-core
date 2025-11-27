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

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 多言語対応トレイト
 * 
 * モデルに翻訳機能を追加します。
 * ページ作成プラグインやフロントページで共通使用します。
 */
trait HasTranslations
{
    /**
     * 翻訳リレーション
     * 
     * 継承先のモデルで translations() メソッドを定義する必要があります
     */
    abstract public function translations(): HasMany;

    /**
     * 指定された言語の翻訳を取得
     * 
     * @param string|null $locale 言語コード（nullの場合は現在の言語）
     * @return mixed
     */
    public function translate(?string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        
        return $this->translations()
            ->where('locale', $locale)
            ->first();
    }

    /**
     * 指定された言語の翻訳を取得（フォールバック付き）
     * 
     * @param string|null $locale 言語コード
     * @return mixed
     */
    public function translateOrFallback(?string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        
        // 指定された言語の翻訳を取得
        $translation = $this->translate($locale);
        
        if ($translation) {
            return $translation;
        }
        
        // フォールバック: 入力済みの言語から取得（優先順位: ja > en > その他）
        return $this->translations()
            ->orderByRaw("FIELD(locale, 'ja', 'en')")
            ->first();
    }

    /**
     * 翻訳が存在するかチェック
     * 
     * @param string $locale 言語コード
     * @return bool
     */
    public function hasTranslation(string $locale): bool
    {
        return $this->translations()
            ->where('locale', $locale)
            ->exists();
    }

    /**
     * 利用可能な言語一覧を取得
     * 
     * @return array
     */
    public function availableLocales(): array
    {
        return $this->translations()
            ->pluck('locale')
            ->toArray();
    }

    /**
     * すべての翻訳を言語コードをキーとした配列で取得
     * 
     * @return array
     */
    public function translationsArray(): array
    {
        return $this->translations()
            ->get()
            ->keyBy('locale')
            ->toArray();
    }

    /**
     * 翻訳を作成または更新
     * 
     * @param string $locale 言語コード
     * @param array $data 翻訳データ
     * @return mixed
     */
    public function setTranslation(string $locale, array $data)
    {
        return $this->translations()->updateOrCreate(
            ['locale' => $locale],
            $data
        );
    }

    /**
     * 複数の翻訳を一括作成または更新
     * 
     * @param array $translations ['ja' => ['title' => '...'], 'en' => ['title' => '...']]
     * @return void
     */
    public function setTranslations(array $translations): void
    {
        foreach ($translations as $locale => $data) {
            if (!empty($data) && is_array($data)) {
                $this->setTranslation($locale, $data);
            }
        }
    }

    /**
     * 指定された言語の翻訳を削除
     * 
     * @param string $locale 言語コード
     * @return bool
     */
    public function deleteTranslation(string $locale): bool
    {
        return $this->translations()
            ->where('locale', $locale)
            ->delete() > 0;
    }

    /**
     * 翻訳された属性を取得（フォールバック付き）
     * 
     * @param string $attribute 属性名
     * @param string|null $locale 言語コード
     * @return mixed
     */
    public function getTranslatedAttribute(string $attribute, ?string $locale = null)
    {
        $translation = $this->translateOrFallback($locale);
        
        return $translation->{$attribute} ?? null;
    }

    /**
     * 翻訳された属性を複数取得
     * 
     * @param array $attributes 属性名の配列
     * @param string|null $locale 言語コード
     * @return array
     */
    public function getTranslatedAttributes(array $attributes, ?string $locale = null): array
    {
        $translation = $this->translateOrFallback($locale);
        $result = [];
        
        foreach ($attributes as $attribute) {
            $result[$attribute] = $translation->{$attribute} ?? null;
        }
        
        return $result;
    }

    /**
     * 翻訳が完全かチェック（指定された属性がすべて入力されているか）
     * 
     * @param string $locale 言語コード
     * @param array $requiredAttributes 必須属性の配列
     * @return bool
     */
    public function isTranslationComplete(string $locale, array $requiredAttributes = ['title', 'content']): bool
    {
        $translation = $this->translate($locale);
        
        if (!$translation) {
            return false;
        }
        
        foreach ($requiredAttributes as $attribute) {
            if (empty($translation->{$attribute})) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * 翻訳の完成度を取得（0-100%）
     * 
     * @param string $locale 言語コード
     * @param array $attributes チェックする属性の配列
     * @return int
     */
    public function translationCompleteness(string $locale, array $attributes = ['title', 'content']): int
    {
        $translation = $this->translate($locale);
        
        if (!$translation) {
            return 0;
        }
        
        $filledCount = 0;
        foreach ($attributes as $attribute) {
            if (!empty($translation->{$attribute})) {
                $filledCount++;
            }
        }
        
        return (int) round(($filledCount / count($attributes)) * 100);
    }
}
