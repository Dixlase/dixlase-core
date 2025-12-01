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

namespace App\Services;

use App\Contracts\Multilingual;
use App\Contracts\TranslatableModel;
use Illuminate\Database\Eloquent\Model;

/**
 * ダミー多言語サービス（単一言語用）
 * 
 * 多言語プラグインが無効な場合に使用されるデフォルト実装。
 * 単一言語モードとして動作し、モデルの元の属性値をそのまま返します。
 * 
 * 多言語プラグインが有効化されると、このサービスは
 * プラグインの実装に自動的に差し替えられます。
 */
class DummyMultilingualService implements Multilingual
{
    /**
     * 多言語機能が有効かどうかを確認
     * 
     * ダミー実装では常にfalseを返す
     * 
     * @return bool
     */
    public function isEnabled(): bool
    {
        return false;
    }

    /**
     * 利用可能な言語一覧を取得
     * 
     * ダミー実装ではデフォルト言語のみを返す
     * 
     * @return array
     */
    public function getLocales(): array
    {
        return [$this->getDefaultLocale()];
    }

    /**
     * デフォルト言語を取得
     * 
     * @return string
     */
    public function getDefaultLocale(): string
    {
        return config('app.locale', 'ja');
    }

    /**
     * 現在の言語を取得
     * 
     * @return string
     */
    public function getCurrentLocale(): string
    {
        return app()->getLocale();
    }

    /**
     * 現在の言語を設定
     * 
     * @param string $locale
     * @return void
     */
    public function setCurrentLocale(string $locale): void
    {
        app()->setLocale($locale);
    }

    /**
     * 指定された言語がサポートされているかチェック
     * 
     * ダミー実装ではデフォルト言語のみサポート
     * 
     * @param string $locale
     * @return bool
     */
    public function isSupported(string $locale): bool
    {
        return $locale === $this->getDefaultLocale();
    }

    /**
     * 指定された言語が現在の言語かチェック
     * 
     * @param string $locale
     * @return bool
     */
    public function isCurrentLocale(string $locale): bool
    {
        return $locale === $this->getCurrentLocale();
    }

    /**
     * モデルの翻訳された属性値を取得
     * 
     * ダミー実装ではモデルの元の属性値をそのまま返す。
     * TranslatableModelを実装している場合は翻訳リレーションから取得を試みる。
     * 
     * @param Model $model
     * @param string $field
     * @param string|null $locale
     * @return mixed
     */
    public function getTranslated(Model $model, string $field, ?string $locale = null): mixed
    {
        // TranslatableModelを実装している場合
        if ($model instanceof TranslatableModel) {
            // HasTranslationsトレイトのメソッドがあれば使用
            if (method_exists($model, 'getTranslatedAttribute')) {
                return $model->getTranslatedAttribute($field, $locale);
            }
        }

        // 通常の属性として取得
        return $model->{$field} ?? null;
    }

    /**
     * モデルの複数の翻訳された属性値を取得
     * 
     * @param Model $model
     * @param array $fields
     * @param string|null $locale
     * @return array
     */
    public function getTranslatedFields(Model $model, array $fields, ?string $locale = null): array
    {
        $result = [];
        foreach ($fields as $field) {
            $result[$field] = $this->getTranslated($model, $field, $locale);
        }
        return $result;
    }

    /**
     * 言語切替用のURLを生成
     * 
     * ダミー実装では現在のURLをそのまま返す
     * 
     * @param string $locale
     * @param string|null $path
     * @return string
     */
    public function switchUrl(string $locale, ?string $path = null): string
    {
        return $path ?? url()->current();
    }

    /**
     * 言語名を取得
     * 
     * @param string $locale
     * @param bool $native
     * @return string
     */
    public function getLocaleName(string $locale, bool $native = true): string
    {
        if ($native) {
            return match($locale) {
                'ja' => '日本語',
                'en' => 'English',
                default => $locale,
            };
        }

        return __("common.languages.{$locale}");
    }

    /**
     * 言語フラグ（絵文字）を取得
     * 
     * @param string $locale
     * @return string
     */
    public function getLocaleFlag(string $locale): string
    {
        return match($locale) {
            'ja' => '🇯🇵',
            'en' => '🇬🇧',
            default => '🌐',
        };
    }

    /**
     * 翻訳データを保存
     * 
     * ダミー実装では何もしない（単一言語モード）
     * 
     * @param Model $model
     * @param array $translations
     * @return void
     */
    public function saveTranslations(Model $model, array $translations): void
    {
        // 単一言語モードでは何もしない
        // 多言語プラグインが有効な場合のみ翻訳データを保存
    }

    /**
     * 翻訳キャッシュをクリア
     * 
     * ダミー実装では何もしない
     * 
     * @return void
     */
    public function clearCache(): void
    {
        // 単一言語モードではキャッシュなし
    }
}
