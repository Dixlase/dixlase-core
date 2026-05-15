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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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
 * Passkey mode definition
 */
enum PasskeyMode: int
{
    case Disabled = 0;           // Disabled
    case Enabled = 1;            // Enabled
    case UseProfileSetting = 2;  // Follow profile settings

    /**
     * Get label
     */
    public function label(): string
    {
        return match ($this) {
            self::Disabled => __('common.passkey_mode.options.disabled'),
            self::Enabled => __('common.passkey_mode.options.enabled'),
            self::UseProfileSetting => __('common.passkey_mode.options.use_profile_setting'),
        };
    }

    /**
     * Get description
     */
    public function description(): string
    {
        return match ($this) {
            self::Disabled => __('common.passkey_mode.descriptions.disabled'),
            self::Enabled => __('common.passkey_mode.descriptions.enabled'),
            self::UseProfileSetting => __('common.passkey_mode.descriptions.use_profile_setting'),
        };
    }

    /**
     * Get icon
     */
    public function icon(): string
    {
        return match ($this) {
            self::Disabled => 'fas fa-ban',
            self::Enabled => 'fas fa-check-circle',
            self::UseProfileSetting => 'fas fa-user-cog',
        };
    }

    /**
     * Get options array for global settings screen (for radio cards)
     */
    public static function getGlobalOptions(): array
    {
        return [
            [
                'value' => (string) self::Disabled->value,
                'label' => self::Disabled->label(),
                'description' => self::Disabled->description(),
                'icon' => self::Disabled->icon(),
            ],
            [
                'value' => (string) self::Enabled->value,
                'label' => self::Enabled->label(),
                'description' => self::Enabled->description(),
                'icon' => self::Enabled->icon(),
            ],
            [
                'value' => (string) self::UseProfileSetting->value,
                'label' => self::UseProfileSetting->label(),
                'description' => self::UseProfileSetting->description(),
                'icon' => self::UseProfileSetting->icon(),
            ],
        ];
    }

    /**
     * Determine if passkey is enabled
     *
     * @param  int|null  $globalSetting  Global settings value
     * @param  int|null  $userSetting  User settings value
     */
    public static function isEnabled(?int $globalSetting, ?int $userSetting = null): bool
    {
        // Always disabled when global settings are disabled
        if ($globalSetting === self::Disabled->value) {
            return false;
        }

        // Always enabled when global settings are enabled
        if ($globalSetting === self::Enabled->value) {
            return true;
        }

        // Check user settings when global settings follow profile settings
        if ($globalSetting === self::UseProfileSetting->value) {
            return $userSetting === self::Enabled->value;
        }

        return false;
    }

    /**
     * Determine if configurable in profile
     *
     * @param  int|null  $globalSetting  Global settings value
     */
    public static function isProfileEditable(?int $globalSetting): bool
    {
        // Editable only when global settings are set to "Follow profile settings"
        return $globalSetting === self::UseProfileSetting->value;
    }

    /**
     * Get forced value in profile (when not editable)
     *
     * @param  int|null  $globalSetting  Global settings value
     * @return bool|null Forced value (null = editable)
     */
    public static function getForcedProfileValue(?int $globalSetting): ?bool
    {
        if ($globalSetting === self::Disabled->value) {
            return false; // Forced to disabled
        }

        if ($globalSetting === self::Enabled->value) {
            return true; // Forced to enabled
        }

        return null; // Editable
    }
}
