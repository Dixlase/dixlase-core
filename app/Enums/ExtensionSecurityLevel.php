<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
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
 * @internal Core only. Do not reference from plugins/themes
 *
 * Health level for extensions (plugins/themes)
 *
 * Health level indicates the scope of impact an extension has on the system.
 * By using the term "health" instead of "risk",
 * we provide more positive and constructive feedback to developers.
 *
 * Preset modes:
 * - Strict: Signature required, permission definition required, only "Good" health allowed
 * - Balanced: OK if signed or from trusted source, allow up to "Caution"
 * - Development: Unsigned or undefined can be installed (with warnings)
 */
enum ExtensionSecurityLevel: int
{
    /**
     * Good only - Extensions using only basic features
     */
    case Healthy = 0;

    /**
     * Allow up to Caution - Uses some extension features
     */
    case Warning = 1;

    /**
     * Allow up to Review Required - Uses more features
     */
    case NeedsAttention = 2;

    /**
     * Allow Unverified - Allows all extensions
     */
    case NotVerified = 3;

    /**
     * Get translation key
     */
    public function translationKey(): string
    {
        return match ($this) {
            self::Healthy => 'admin/settings/security/extensions.security.health_level.healthy',
            self::Warning => 'admin/settings/security/extensions.security.health_level.warning',
            self::NeedsAttention => 'admin/settings/security/extensions.security.health_level.needs_attention',
            self::NotVerified => 'admin/settings/security/extensions.security.health_level.not_verified',
        };
    }

    /**
     * Get label
     */
    public function label(): string
    {
        return __($this->translationKey());
    }

    /**
     * Get short label (for Range)
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Healthy => __('admin/settings/security/extensions.security.health_level_short.healthy'),
            self::Warning => __('admin/settings/security/extensions.security.health_level_short.warning'),
            self::NeedsAttention => __('admin/settings/security/extensions.security.health_level_short.needs_attention'),
            self::NotVerified => __('admin/settings/security/extensions.security.health_level_short.not_verified'),
        };
    }

    /**
     * Get description
     */
    public function description(): string
    {
        return match ($this) {
            self::Healthy => __('admin/settings/security/extensions.security.health_level_description.healthy'),
            self::Warning => __('admin/settings/security/extensions.security.health_level_description.warning'),
            self::NeedsAttention => __('admin/settings/security/extensions.security.health_level_description.needs_attention'),
            self::NotVerified => __('admin/settings/security/extensions.security.health_level_description.not_verified'),
        };
    }

    /**
     * Get CSS class (for color coding)
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::Healthy => 'text-green-600 dark:text-green-400',
            self::Warning => 'text-yellow-600 dark:text-yellow-400',
            self::NeedsAttention => 'text-orange-600 dark:text-orange-400',
            self::NotVerified => 'text-gray-600 dark:text-gray-400',
        };
    }

    /**
     * Get badge class
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Healthy => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            self::Warning => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
            self::NeedsAttention => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
            self::NotVerified => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        };
    }

    /**
     * Determine if the specified health level is at or below this level
     */
    public function allows(self $healthLevel): bool
    {
        return $healthLevel->value <= $this->value;
    }

    /**
     * Get all levels
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * Get label array for Range
     */
    public static function getRangeLabels(): array
    {
        $labels = [];
        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->translationKey();
        }

        return $labels;
    }

    /**
     * Get label color array for Range
     * Good→green, Caution→yellow, Needs Review→orange, Unconfirmed→red
     */
    public static function getRangeLabelColors(): array
    {
        return [
            self::Healthy->value => 'green',
            self::Warning->value => 'yellow',
            self::NeedsAttention->value => 'orange',
            self::NotVerified->value => 'red',
        ];
    }

    /**
     * Get default value (Balance mode = Warning)
     */
    public static function default(): self
    {
        return self::Warning;
    }
}
