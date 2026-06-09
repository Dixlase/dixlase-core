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
 * Authentication mode (common to two-factor authentication and notification settings)
 *
 * Can be used for both members and users
 * Can be used for both two-factor authentication and notification settings
 */
enum AuthenticationMode: int
{
    case Disabled = 0;              // Disabled
    case DifferentDevice = 1;       // Only on login from different device or IP
    case Always = 2;                // Always enabled
    case UseProfileSetting = 3;     // Follow profile settings (for global settings only)

    /**
     * Get label (for two-factor authentication)
     */
    public function twoFactorLabel(): string
    {
        return match ($this) {
            self::Disabled => __('components/security/two-fa-general-settings.authentication_mode.disabled'),
            self::DifferentDevice => __('components/security/two-fa-general-settings.authentication_mode.different_device'),
            self::Always => __('components/security/two-fa-general-settings.authentication_mode.always'),
            self::UseProfileSetting => __('components/security/two-fa-general-settings.authentication_mode.use_profile_setting'),
        };
    }

    /**
     * Get label (for notification settings)
     */
    public function notificationLabel(): string
    {
        return match ($this) {
            self::Disabled => __('auth.authentication_mode.notification.disabled'),
            self::DifferentDevice => __('auth.authentication_mode.notification.different_device'),
            self::Always => __('auth.authentication_mode.notification.always'),
            self::UseProfileSetting => __('auth.authentication_mode.notification.use_profile_setting'),
        };
    }

    /**
     * Get translation key (for two-factor authentication)
     */
    public function twoFactorTranslationKey(): string
    {
        return match ($this) {
            self::Disabled => 'components/security/two-fa-general-settings.authentication_mode.disabled',
            self::DifferentDevice => 'components/security/two-fa-general-settings.authentication_mode.different_device',
            self::Always => 'components/security/two-fa-general-settings.authentication_mode.always',
            self::UseProfileSetting => 'components/security/two-fa-general-settings.authentication_mode.use_profile_setting',
        };
    }

    /**
     * Get translation key (for notification settings)
     */
    public function notificationTranslationKey(): string
    {
        return match ($this) {
            self::Disabled => 'auth.authentication_mode.notification.disabled',
            self::DifferentDevice => 'auth.authentication_mode.notification.different_device',
            self::Always => 'auth.authentication_mode.notification.always',
            self::UseProfileSetting => 'auth.authentication_mode.notification.use_profile_setting',
        };
    }

    /**
     * Get options array for two-factor authentication
     */
    public static function twoFactorOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->twoFactorLabel();
        }

        return $options;
    }

    /**
     * Get translation key array for two-factor authentication
     */
    public static function twoFactorTranslationOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->twoFactorTranslationKey();
        }

        return $options;
    }

    /**
     * Get options array for notification settings
     */
    public static function notificationOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->notificationLabel();
        }

        return $options;
    }

    /**
     * Get translation key array for notification settings
     */
    public static function notificationTranslationOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->notificationTranslationKey();
        }

        return $options;
    }

    /**
     * For profile settings (excluding UseProfileSetting)
     */
    public static function forProfile(): array
    {
        return array_filter(self::cases(), fn (self $case) => $case !== self::UseProfileSetting);
    }

    /**
     * Two-factor authentication options for profile settings
     */
    public static function twoFactorProfileOptions(): array
    {
        $options = [];
        foreach (self::forProfile() as $case) {
            $options[$case->value] = $case->twoFactorLabel();
        }

        return $options;
    }

    /**
     * Notification options for profile settings
     */
    public static function notificationProfileOptions(): array
    {
        $options = [];
        foreach (self::forProfile() as $case) {
            $options[$case->value] = $case->notificationLabel();
        }

        return $options;
    }

    /**
     * For backward compatibility (former TwoFactorMode)
     */
    public function label(): string
    {
        return $this->twoFactorLabel();
    }

    /**
     * For backward compatibility (former TwoFactorMode)
     */
    public function translationKey(): string
    {
        return $this->twoFactorTranslationKey();
    }

    /**
     * For backward compatibility (former TwoFactorMode)
     */
    public static function options(): array
    {
        return self::twoFactorOptions();
    }

    /**
     * For backward compatibility (former TwoFactorMode)
     */
    public static function translationOptions(): array
    {
        return self::twoFactorTranslationOptions();
    }
}
