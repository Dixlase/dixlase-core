<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

/**
 * 言語設定ヘルパー
 *
 * 管理画面の言語設定とフォールバックロジックを管理します。
 */
class LocaleHelper
{
    /**
     * サポートされている言語一覧
     */
    protected static array $supportedLocales = ['ja', 'en'];

    /**
     * デフォルト言語
     */
    protected static string $defaultLocale = 'ja';

    /**
     * 言語のフォールバック優先順位
     */
    protected static array $fallbackPriority = ['ja', 'en'];

    /**
     * サポートされている言語一覧を取得
     */
    public static function supportedLocales(): array
    {
        return self::$supportedLocales;
    }

    /**
     * サポートされている言語を選択肢として取得
     *
     * @return array ['ja' => '日本語', 'en' => 'English']
     */
    public static function supportedLocaleOptions(): array
    {
        return [
            'ja' => __('common.languages.ja'),
            'en' => __('common.languages.en'),
        ];
    }

    /**
     * 言語がサポートされているかチェック
     */
    public static function isSupported(string $locale): bool
    {
        return in_array($locale, self::$supportedLocales);
    }

    /**
     * ログイン中のユーザーの優先言語を取得
     */
    public static function getUserPreferredLocale(): string
    {
        if (Auth::check() && Auth::user()->locale) {
            $userLocale = Auth::user()->locale;
            // Enumの場合は文字列に変換
            if ($userLocale instanceof \App\Enums\Locale) {
                $userLocale = $userLocale->value;
            }
            if (self::isSupported($userLocale)) {
                return $userLocale;
            }
        }

        return self::getCurrentLocale();
    }

    /**
     * 現在の言語を取得
     */
    public static function getCurrentLocale(): string
    {
        $locale = app()->getLocale();

        return self::isSupported($locale) ? $locale : self::$defaultLocale;
    }

    /**
     * デフォルト言語を取得
     */
    public static function getDefaultLocale(): string
    {
        return self::$defaultLocale;
    }

    /**
     * フォールバック言語を取得
     *
     * @param  string  $preferredLocale  優先言語
     * @param  array  $availableLocales  利用可能な言語一覧
     */
    public static function getFallbackLocale(string $preferredLocale, array $availableLocales): ?string
    {
        // 優先言語が利用可能ならそれを返す
        if (in_array($preferredLocale, $availableLocales)) {
            return $preferredLocale;
        }

        // フォールバック優先順位に従って検索
        foreach (self::$fallbackPriority as $locale) {
            if (in_array($locale, $availableLocales)) {
                return $locale;
            }
        }

        // どれもなければ最初の利用可能な言語
        return $availableLocales[0] ?? null;
    }

    /**
     * 言語名を取得
     *
     * @param  bool  $native  ネイティブ表記で取得するか
     */
    public static function getLocaleName(string $locale, bool $native = true): string
    {
        if ($native) {
            return match ($locale) {
                'ja' => '日本語',
                'en' => 'English',
                default => $locale,
            };
        }

        return __("common.languages.{$locale}");
    }

    /**
     * すべての言語名を取得
     *
     * @param  bool  $native  ネイティブ表記で取得するか
     */
    public static function getAllLocaleNames(bool $native = true): array
    {
        $names = [];
        foreach (self::$supportedLocales as $locale) {
            $names[$locale] = self::getLocaleName($locale, $native);
        }

        return $names;
    }

    /**
     * 言語設定を変更
     */
    public static function setLocale(string $locale): void
    {
        if (self::isSupported($locale)) {
            app()->setLocale($locale);
            session(['locale' => $locale]);
        }
    }

    /**
     * セッションから言語を取得
     */
    public static function getSessionLocale(): ?string
    {
        return session('locale');
    }
}
