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

use Illuminate\Database\Eloquent\Model;

/**
 * 多言語サービスインターフェイス
 * 
 * コアとプラグインで共通の多言語機能を提供するためのContract。
 * 多言語プラグインが無効な場合はダミー実装が使用され、
 * 有効な場合はプラグインの実装に差し替えられます。
 * 
 * テーマやプラグインは常にこのContractを通じて多言語機能にアクセスします。
 * これにより、多言語プラグインの有無に関わらず同じコードで動作します。
 * 
 * @example
 * // ヘルパー関数経由で使用
 * multilingual()->isEnabled();
 * multilingual()->getTranslated($page, 'title');
 * 
 * // DIで使用
 * public function __construct(Multilingual $ml) {
 *     $this->ml = $ml;
 * }
 */
interface Multilingual
{
    /**
     * 多言語機能が有効かどうかを確認
     * 
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * 利用可能な言語一覧を取得
     * 
     * @return array ['ja', 'en', ...]
     */
    public function getLocales(): array;

    /**
     * デフォルト言語を取得
     * 
     * @return string 'ja' など
     */
    public function getDefaultLocale(): string;

    /**
     * 現在の言語を取得
     * 
     * @return string
     */
    public function getCurrentLocale(): string;

    /**
     * 現在の言語を設定
     * 
     * @param string $locale
     * @return void
     */
    public function setCurrentLocale(string $locale): void;

    /**
     * 指定された言語がサポートされているかチェック
     * 
     * @param string $locale
     * @return bool
     */
    public function isSupported(string $locale): bool;

    /**
     * 指定された言語が現在の言語かチェック
     * 
     * @param string $locale
     * @return bool
     */
    public function isCurrentLocale(string $locale): bool;

    /**
     * モデルの翻訳された属性値を取得
     * 
     * 多言語プラグインが有効な場合は現在の言語の翻訳を返し、
     * 無効な場合はモデルの元の属性値を返します。
     * 
     * @param Model $model 翻訳対象のモデル
     * @param string $field 属性名
     * @param string|null $locale 言語コード（nullの場合は現在の言語）
     * @return mixed
     */
    public function getTranslated(Model $model, string $field, ?string $locale = null): mixed;

    /**
     * モデルの複数の翻訳された属性値を取得
     * 
     * @param Model $model 翻訳対象のモデル
     * @param array $fields 属性名の配列
     * @param string|null $locale 言語コード
     * @return array
     */
    public function getTranslatedFields(Model $model, array $fields, ?string $locale = null): array;

    /**
     * 言語切替用のURLを生成
     * 
     * 現在のURLを指定された言語用のURLに変換します。
     * 
     * @param string $locale 切り替え先の言語
     * @param string|null $path パス（nullの場合は現在のパス）
     * @return string
     */
    public function switchUrl(string $locale, ?string $path = null): string;

    /**
     * 言語名を取得
     * 
     * @param string $locale 言語コード
     * @param bool $native ネイティブ表記で取得するか
     * @return string
     */
    public function getLocaleName(string $locale, bool $native = true): string;

    /**
     * 言語フラグ（絵文字）を取得
     * 
     * @param string $locale 言語コード
     * @return string
     */
    public function getLocaleFlag(string $locale): string;

    /**
     * 翻訳データを保存
     * 
     * @param Model $model 翻訳対象のモデル
     * @param array $translations ['ja' => ['title' => '...'], 'en' => ['title' => '...']]
     * @return void
     */
    public function saveTranslations(Model $model, array $translations): void;

    /**
     * 翻訳キャッシュをクリア
     * 
     * @return void
     */
    public function clearCache(): void;
}
