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
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
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

use App\Contracts\Site\SiteContextInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request as RequestFacade;

/**
 * Locale helper.
 *
 * Manages admin locale settings and fallback logic.
 */
class LocaleHelper
{
    /**
     * Cookie name used by the language switcher to persist the visitor's
     * explicit locale choice.
     */
    public const COOKIE_NAME = 'dixlase_locale';

    /**
     * Cookie lifetime in minutes (1 year).
     */
    public const COOKIE_LIFETIME_MINUTES = 60 * 24 * 365;

    /**
     * List of supported locales.
     */
    protected static array $supportedLocales = ['ja', 'en'];

    /**
     * Default locale.
     */
    protected static string $defaultLocale = 'en';

    /**
     * Locale fallback priority order.
     */
    protected static array $fallbackPriority = ['en', 'ja'];

    /**
     * Get the list of supported locales.
     */
    public static function supportedLocales(): array
    {
        return self::$supportedLocales;
    }

    /**
     * Get supported locales as a select option array.
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
     * Check whether the given locale is supported.
     */
    public static function isSupported(string $locale): bool
    {
        return in_array($locale, self::$supportedLocales);
    }

    /**
     * Get the preferred locale of the authenticated user.
     */
    public static function getUserPreferredLocale(): string
    {
        if (Auth::check() && Auth::user()->locale) {
            $userLocale = Auth::user()->locale;
            // Convert Enum cases to their string value.
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
     * Get the current locale.
     */
    public static function getCurrentLocale(): string
    {
        $locale = app()->getLocale();

        return self::isSupported($locale) ? $locale : self::$defaultLocale;
    }

    /**
     * Get the default locale.
     */
    public static function getDefaultLocale(): string
    {
        return self::$defaultLocale;
    }

    /**
     * Get the fallback locale.
     *
     * @param  string  $preferredLocale  Preferred locale.
     * @param  array  $availableLocales  List of available locales.
     */
    public static function getFallbackLocale(string $preferredLocale, array $availableLocales): ?string
    {
        // Return the preferred locale if it is available.
        if (in_array($preferredLocale, $availableLocales)) {
            return $preferredLocale;
        }

        // Search according to the fallback priority order.
        foreach (self::$fallbackPriority as $locale) {
            if (in_array($locale, $availableLocales)) {
                return $locale;
            }
        }

        // If nothing matches, return the first available locale.
        return $availableLocales[0] ?? null;
    }

    /**
     * Get the locale display name.
     *
     * @param  bool  $native  Whether to return the native script.
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
     * Get all locale display names.
     *
     * @param  bool  $native  Whether to return the native script.
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
     * Change the active locale.
     */
    public static function setLocale(string $locale): void
    {
        if (self::isSupported($locale)) {
            app()->setLocale($locale);
            session(['locale' => $locale]);
        }
    }

    /**
     * Get the locale from the session.
     */
    public static function getSessionLocale(): ?string
    {
        return session('locale');
    }

    /**
     * Get the locale stored in the language-switcher cookie.
     *
     * Returns null when the cookie is missing or its value is not a
     * supported locale.
     */
    public static function getCookieLocale(): ?string
    {
        $value = RequestFacade::cookie(self::COOKIE_NAME);
        if (! is_string($value) || $value === '') {
            return null;
        }

        return self::isSupported($value) ? $value : null;
    }

    /**
     * Get the current site's primary locale, falling back to the
     * application's configured fallback locale.
     *
     * Resolves through SiteContext so multisite installs return the
     * correct value for the request's resolved site.
     */
    public static function getSiteDefaultLocale(): string
    {
        $site = app(SiteContextInterface::class)->currentSite();
        $locale = $site->primary_locale ?? null;

        if (is_string($locale) && self::isSupported($locale)) {
            return $locale;
        }

        $fallback = config('app.fallback_locale', self::$defaultLocale);

        return self::isSupported((string) $fallback) ? (string) $fallback : self::$defaultLocale;
    }

    /**
     * Build the URL for the same request under a different locale.
     *
     * Replaces the locale segment in the current URL path while
     * preserving the query string. Used by language switcher links.
     */
    public static function switchLocaleUrl(string $target): string
    {
        if (! self::isSupported($target)) {
            return RequestFacade::fullUrl();
        }

        $request = RequestFacade::instance();
        $segments = explode('/', trim($request->path(), '/'));

        if (isset($segments[0]) && self::isSupported($segments[0])) {
            $segments[0] = $target;
        } else {
            array_unshift($segments, $target);
        }

        $path = '/'.implode('/', array_filter($segments, static fn ($s) => $s !== ''));
        $query = $request->getQueryString();

        return $request->getSchemeAndHttpHost().$path.($query !== null ? '?'.$query : '');
    }
}
