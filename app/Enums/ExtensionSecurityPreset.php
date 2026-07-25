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
 * Extension security preset mode
 */
enum ExtensionSecurityPreset: string
{
    /**
     * Development/Testing mode
     * - Allows unsigned or undefined installations (with warning)
     * - Consider disabling this option in production
     */
    case Development = 'development';

    /**
     * Balanced mode
     * - Accepts signed or trusted marketplace sources
     * - Allows health status up to "Caution"
     */
    case Balanced = 'balanced';

    /**
     * Strict mode (recommended)
     * - Signature required
     * - Permission definition required
     * - Only allows "Good" health status
     */
    case Strict = 'strict';

    /**
     * Custom mode
     * - User configures individually
     */
    case Custom = 'custom';

    /**
     * Get translation key
     */
    public function translationKey(): string
    {
        return 'admin/settings/security/extensions.security.preset.'.$this->value;
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
        return __($this->translationKey().'_description');
    }

    /**
     * Get default settings for this preset
     */
    public function getDefaultSettings(): array
    {
        return match ($this) {
            self::Strict => [
                'require_signature' => true,
                'require_permission_definition' => true,
                'allow_undefined_permissions' => false,
                'max_health_level' => ExtensionSecurityLevel::Healthy->value,
                'plugin_max_health_level' => ExtensionSecurityLevel::Healthy->value,
                'theme_max_health_level' => ExtensionSecurityLevel::Healthy->value,
                'allow_logic_themes' => false,
            ],
            self::Balanced => [
                'require_signature' => false,
                'require_permission_definition' => false,
                'allow_undefined_permissions' => true,
                'max_health_level' => ExtensionSecurityLevel::Warning->value,
                'plugin_max_health_level' => ExtensionSecurityLevel::Warning->value,
                'theme_max_health_level' => ExtensionSecurityLevel::NeedsAttention->value,
                'allow_logic_themes' => true,
            ],
            self::Development => [
                'require_signature' => false,
                'require_permission_definition' => false,
                'allow_undefined_permissions' => true,
                'max_health_level' => ExtensionSecurityLevel::NotVerified->value,
                'plugin_max_health_level' => ExtensionSecurityLevel::NotVerified->value,
                'theme_max_health_level' => ExtensionSecurityLevel::NotVerified->value,
                'allow_logic_themes' => true,
            ],
            self::Custom => [
                // Custom mode uses user settings
                'require_signature' => false,
                'require_permission_definition' => false,
                'allow_undefined_permissions' => true,
                'max_health_level' => ExtensionSecurityLevel::Warning->value,
                'plugin_max_health_level' => ExtensionSecurityLevel::Warning->value,
                'theme_max_health_level' => ExtensionSecurityLevel::Warning->value,
                'allow_logic_themes' => true,
            ],
        };
    }

    /**
     * Get CSS class (for color coding)
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::Strict => 'text-red-600 dark:text-red-400',
            self::Balanced => 'text-green-600 dark:text-green-400',
            self::Development => 'text-yellow-600 dark:text-yellow-400',
            self::Custom => 'text-purple-600 dark:text-purple-400',
        };
    }

    /**
     * Get icon class
     */
    public function iconClass(): string
    {
        return match ($this) {
            self::Strict => 'fas fa-shield-alt',
            self::Balanced => 'fas fa-balance-scale',
            self::Development => 'fas fa-code',
            self::Custom => 'fas fa-sliders-h',
        };
    }

    /**
     * Whether it can be used in production environment
     */
    public function isProductionSafe(): bool
    {
        return match ($this) {
            self::Strict, self::Balanced, self::Custom => true,
            self::Development => false,
        };
    }

    /**
     * Get all presets
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * Get default value
     */
    public static function default(): self
    {
        return self::Balanced;
    }

    /**
     * Get only presets available for production environment
     */
    public static function productionSafe(): array
    {
        return array_filter(self::cases(), fn ($preset) => $preset->isProductionSafe());
    }

    /**
     * Get color name (for radio-card-group)
     */
    public function colorName(): string
    {
        return match ($this) {
            self::Development => 'yellow',
            self::Balanced => 'blue',
            self::Strict => 'red',
            self::Custom => 'purple',
        };
    }

    /**
     * Get options array for radio-card-group component
     */
    public static function getRadioCardOptions(): array
    {
        $options = [];
        foreach (self::cases() as $preset) {
            $option = [
                'value' => $preset->value,
                'label' => $preset->translationKey(),
                'description' => $preset->translationKey().'_description',
                'icon' => $preset->iconClass(),
                'color' => $preset->colorName(),
            ];

            // Add badge if not available in production environment
            if (! $preset->isProductionSafe()) {
                $option['badge'] = 'admin/settings/security/extensions.security.dev_only';
                $option['badgeColor'] = 'yellow';
            }

            $options[] = $option;
        }

        return $options;
    }
}
