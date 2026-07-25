<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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

namespace App\Enums;

/**
 * Locale definition
 */
enum Locale: string
{
    case JAPANESE = 'ja';
    case ENGLISH = 'en';

    /**
     * Get display label
     */
    public function label(): string
    {
        return match ($this) {
            self::JAPANESE => '日本語',
            self::ENGLISH => 'English',
        };
    }

    /**
     * Get all language options
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(function ($case) {
            return [$case->value => $case->label()];
        })->toArray();
    }

    /**
     * Get array of available language codes
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get default language
     */
    public static function default(): self
    {
        return self::JAPANESE;
    }

    /**
     * Get available languages from settings file
     */
    public static function availableOptions(): array
    {
        $available = config('admin.locale.available', []);
        $options = [];

        foreach (self::cases() as $case) {
            if (isset($available[$case->value])) {
                $options[$case->value] = $available[$case->value]['name'] ?? $case->label();
            }
        }

        return $options;
    }

    /**
     * Check if language code is valid
     */
    public static function isValid(?string $locale): bool
    {
        if ($locale === null) {
            return true; // null is valid (uses system default)
        }

        return in_array($locale, self::values());
    }
}
