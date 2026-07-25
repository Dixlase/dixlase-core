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
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * CSP (Content Security Policy) mode
 */
enum CspMode: int
{
    /**
     * Development mode
     * - Relaxed policy
     * - Inline scripts allowed
     * - For development/test environments
     */
    case Development = 0;

    /**
     * Standard mode (recommended)
     * - Balanced policy
     * - Meets general security requirements
     * - For production environments
     */
    case Standard = 1;

    /*
     * Strict mode (not implemented in initial version)
     * - Strictest policy
     * - Inline scripts prohibited
     * - For high security requirements
     *
    case Strict = 2;
     */

    /**
     * Get translation key
     */
    public function translationKey(): string
    {
        return 'admin/settings/security/csp.mode_'.$this->toString();
    }

    /**
     * Get label
     */
    public function label(): string
    {
        return __($this->translationKey());
    }

    /**
     * Get description
     */
    public function description(): string
    {
        return __($this->translationKey().'_desc');
    }

    /**
     * Get string representation
     */
    public function toString(): string
    {
        return match ($this) {
            self::Development => 'development',
            self::Standard => 'standard',
            // self::Strict => 'strict', // Not implemented in initial version
        };
    }

    /**
     * Get Enum from string
     */
    public static function fromString(string $value): ?self
    {
        return match ($value) {
            'development' => self::Development,
            'standard' => self::Standard,
            // 'strict' => self::Strict, // Not implemented in initial version
            default => null,
        };
    }

    /**
     * Get Enum from numeric value
     */
    public static function fromValue(int|string $value): ?self
    {
        $intValue = (int) $value;

        return match ($intValue) {
            0 => self::Development,
            1 => self::Standard,
            // 2 => self::Strict, // Not implemented in initial version
            default => null,
        };
    }

    /**
     * Get CSS class (for color coding)
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::Development => 'text-yellow-600 dark:text-yellow-400',
            self::Standard => 'text-green-600 dark:text-green-400',
            // self::Strict => 'text-red-600 dark:text-red-400', // Not implemented in initial version
        };
    }

    /**
     * Get color name (for radio-card-group)
     */
    public function colorName(): string
    {
        return match ($this) {
            self::Development => 'yellow',
            self::Standard => 'blue',
            // self::Strict => 'red', // Not implemented in initial version
        };
    }

    /**
     * Get icon class
     */
    public function iconClass(): string
    {
        return match ($this) {
            self::Development => 'fas fa-code',
            self::Standard => 'fas fa-shield-alt',
            // self::Strict => 'fas fa-lock', // Not implemented in initial version
        };
    }

    /**
     * Whether recommended for production environment
     */
    public function isProductionRecommended(): bool
    {
        return match ($this) {
            self::Development => false,
            self::Standard => true,
            // self::Strict => true, // Not implemented in initial version
        };
    }

    /**
     * Get default value
     */
    public static function default(): self
    {
        return self::Standard;
    }

    /**
     * Get all modes
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * Get all string values
     */
    public static function getAllStrings(): array
    {
        return array_map(fn ($case) => $case->toString(), self::cases());
    }

    /**
     * Get string for validation rule (numeric version)
     */
    public static function validationRule(): string
    {
        return 'in:'.implode(',', self::getAllValues());
    }

    /**
     * Get all numeric values
     */
    public static function getAllValues(): array
    {
        return array_map(fn ($case) => (string) $case->value, self::cases());
    }

    /**
     * Get feature list
     */
    public function features(): array
    {
        $baseKey = $this->translationKey();
        $features = [
            __($baseKey.'_feature1'),
            __($baseKey.'_feature2'),
            __($baseKey.'_feature3'),
        ];

        // Add if feature4 exists
        $feature4Key = $baseKey.'_feature4';
        if (__($feature4Key) !== $feature4Key) {
            $features[] = __($feature4Key);
        }

        return $features;
    }

    /**
     * Get options array for radio-card-group component
     */
    public static function getRadioCardOptions(): array
    {
        $options = [];
        foreach (self::cases() as $mode) {
            $option = [
                'value' => (string) $mode->value,
                'label' => $mode->translationKey(),
                'description' => $mode->translationKey().'_desc',
                'icon' => $mode->iconClass(),
                'color' => $mode->colorName(),
                'features' => $mode->features(),
            ];

            // Add recommended badge to standard mode
            if ($mode === self::Standard) {
                $option['badge'] = 'admin/settings/security/csp.recommended';
                $option['badgeColor'] = 'blue';
            }

            $options[] = $option;
        }

        // Strict mode (disabled card to be implemented in the future)
        $options[] = [
            'value' => '2',
            'label' => 'admin/settings/security/csp.mode_strict',
            'description' => 'admin/settings/security/csp.mode_strict_desc',
            'icon' => 'fas fa-lock',
            'color' => 'purple',
            'disabled' => true,
            'badge' => 'admin/settings/security/csp.coming_soon',
            'badgeColor' => 'gray',
            'features' => [
                __('admin/settings/security/csp.mode_strict_feature1'),
                __('admin/settings/security/csp.mode_strict_feature2'),
                __('admin/settings/security/csp.mode_strict_feature3'),
            ],
        ];

        return $options;
    }
}
