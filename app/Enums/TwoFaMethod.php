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
 * Two-factor authentication method definition
 */
enum TwoFaMethod: int
{
    case EMAIL = 0;
    case PASSKEY = 1;

    public function label(): string
    {
        return match ($this) {
            self::EMAIL => __('two_fa.method.email'),
            self::PASSKEY => __('two_fa.method.passkey'),
        };
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    public static function translationOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->translationKey();
        }

        return $options;
    }

    public function translationKey(): string
    {
        return match ($this) {
            self::EMAIL => 'two_fa.method.email',
            self::PASSKEY => 'two_fa.method.passkey',
        };
    }

    /**
     * Get security level (rated on a scale of 5)
     */
    public function securityLevel(): int
    {
        return match ($this) {
            self::PASSKEY => 5,  // Most secure
            self::EMAIL => 3,    // Medium security
        };
    }

    /**
     * Security level label
     */
    public function securityLevelLabel(): string
    {
        return match ($this) {
            self::PASSKEY => __('two_fa.security.level.very_high'),
            self::EMAIL => __('two_fa.security.level.medium'),
        };
    }

    /**
     * Security description
     */
    public function securityDescription(): string
    {
        return match ($this) {
            self::PASSKEY => __('two_fa.security.description.passkey'),
            self::EMAIL => __('two_fa.security.description.email'),
        };
    }

    /**
     * Whether this is a recommended authentication method
     */
    public function isRecommended(): bool
    {
        return match ($this) {
            self::PASSKEY => true,
            self::EMAIL => false,
        };
    }

    public static function forGlobalSettings(): array
    {
        return self::cases();
    }
}
