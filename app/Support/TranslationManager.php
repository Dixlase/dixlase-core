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

namespace App\Support;

use App\Contracts\TranslationResolver;
use App\Events\DixlaseEvents;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;

/**
 * Translation Manager
 *
 * Provides a facade-like interface for managing translations.
 * Plugins can use this to register custom translation resolvers
 * and manage locale settings.
 *
 * Usage:
 * ```php
 * // Register a custom resolver
 * TranslationManager::resolveUsing(MyTranslationResolver::class);
 *
 * // Or with a closure
 * TranslationManager::resolveUsing(function ($model, $field, $locale) {
 *     return MyTranslation::get($model, $field, $locale);
 * });
 *
 * // Change locale with event firing
 * TranslationManager::setLocale('ja');
 *
 * // Get available locales
 * $locales = TranslationManager::getAvailableLocales();
 * ```
 *
 * @see docs/translation-api-spec.md
 */
class TranslationManager
{
    /**
     * Available locales (can be extended by plugins)
     */
    protected static array $availableLocales = [];

    /**
     * Locale names for display
     */
    protected static array $localeNames = [];

    /**
     * Register a custom translation resolver
     *
     * @param  string|callable  $resolver  Class name or closure
     */
    public static function resolveUsing(string|callable $resolver): void
    {
        if (is_string($resolver)) {
            App::singleton(TranslationResolver::class, $resolver);
        } else {
            App::singleton(TranslationResolver::class, function () use ($resolver) {
                return new class($resolver) implements TranslationResolver
                {
                    public function __construct(
                        protected $resolver
                    ) {}

                    public function resolve($model, string $field, string $locale): mixed
                    {
                        return ($this->resolver)($model, $field, $locale);
                    }

                    public function store($model, string $field, mixed $value, string $locale): void
                    {
                        // Closure-based resolver doesn't support store
                    }

                    public function all($model, string $field): array
                    {
                        return [];
                    }

                    public function exists($model, string $field, string $locale): bool
                    {
                        return ($this->resolver)($model, $field, $locale) !== null;
                    }

                    public function delete($model, string $field, ?string $locale = null): void
                    {
                        // Closure-based resolver doesn't support delete
                    }

                    public function getAvailableLocales($model): array
                    {
                        return [];
                    }

                    public function copy($source, $target, ?array $fields = null, ?array $locales = null): void
                    {
                        // Closure-based resolver doesn't support copy
                    }
                };
            });
        }
    }

    /**
     * Check if a translation resolver is registered
     */
    public static function hasResolver(): bool
    {
        return App::bound(TranslationResolver::class);
    }

    /**
     * Get the registered translation resolver
     */
    public static function getResolver(): ?TranslationResolver
    {
        if (self::hasResolver()) {
            return App::make(TranslationResolver::class);
        }

        return null;
    }

    /**
     * Set the current locale with event firing
     */
    public static function setLocale(string $locale): void
    {
        $previous = App::getLocale();

        if ($previous !== $locale) {
            App::setLocale($locale);

            Event::dispatch(DixlaseEvents::LOCALE_CHANGED, [
                'locale' => $locale,
                'previous' => $previous,
            ]);
        }
    }

    /**
     * Get the current locale
     */
    public static function getLocale(): string
    {
        return App::getLocale();
    }

    /**
     * Get the fallback locale
     */
    public static function getFallbackLocale(): string
    {
        return config('app.fallback_locale', 'en');
    }

    /**
     * Register available locales
     *
     * @param  array  $locales  ['en' => 'English', 'ja' => '日本語', ...]
     */
    public static function registerLocales(array $locales): void
    {
        foreach ($locales as $code => $name) {
            self::$availableLocales[] = $code;
            self::$localeNames[$code] = $name;
        }

        self::$availableLocales = array_unique(self::$availableLocales);
    }

    /**
     * Get all available locales
     */
    public static function getAvailableLocales(): array
    {
        if (empty(self::$availableLocales)) {
            // Default locales
            return [
                config('app.locale', 'en'),
                config('app.fallback_locale', 'en'),
            ];
        }

        return array_unique(self::$availableLocales);
    }

    /**
     * Get locale display name
     */
    public static function getLocaleName(string $locale): string
    {
        return self::$localeNames[$locale] ?? $locale;
    }

    /**
     * Get all locale names
     *
     * @return array ['en' => 'English', 'ja' => '日本語', ...]
     */
    public static function getLocaleNames(): array
    {
        return self::$localeNames;
    }

    /**
     * Check if a locale is available
     */
    public static function isLocaleAvailable(string $locale): bool
    {
        return in_array($locale, self::getAvailableLocales(), true);
    }

    /**
     * Get the default locale for new content
     */
    public static function getDefaultLocale(): string
    {
        return config('app.locale', 'en');
    }

    /**
     * Reset the manager state (useful for testing)
     */
    public static function reset(): void
    {
        self::$availableLocales = [];
        self::$localeNames = [];
    }
}
