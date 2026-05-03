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

namespace App\Enums;

/**
 * Login identifier mode
 *
 * Controls which identifiers (email address / account name) are accepted for admin panel login
 */
enum LoginIdentifierMode: int
{
    case EmailOnly = 0;            // Email address only
    case EmailOrAccountName = 1;   // Email address or account name
    case AccountNameOnly = 2;      // Account name only

    /**
     * Whether login with email address is supported
     */
    public function supportsEmail(): bool
    {
        return match ($this) {
            self::EmailOnly, self::EmailOrAccountName => true,
            self::AccountNameOnly => false,
        };
    }

    /**
     * Whether login with account name is supported
     */
    public function supportsAccountName(): bool
    {
        return match ($this) {
            self::AccountNameOnly, self::EmailOrAccountName => true,
            self::EmailOnly => false,
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
     * Get translation key
     */
    public function translationKey(): string
    {
        return match ($this) {
            self::EmailOnly => 'common.login_identifier_mode.email_only',
            self::EmailOrAccountName => 'common.login_identifier_mode.email_or_account_name',
            self::AccountNameOnly => 'common.login_identifier_mode.account_name_only',
        };
    }

    /**
     * Get description translation key
     */
    public function descriptionKey(): string
    {
        return match ($this) {
            self::EmailOnly => 'common.login_identifier_mode.email_only_description',
            self::EmailOrAccountName => 'common.login_identifier_mode.email_or_account_name_description',
            self::AccountNameOnly => 'common.login_identifier_mode.account_name_only_description',
        };
    }

    /**
     * Get icon class
     */
    public function iconClass(): string
    {
        return match ($this) {
            self::EmailOnly => 'fas fa-envelope',
            self::EmailOrAccountName => 'fas fa-users',
            self::AccountNameOnly => 'fas fa-user',
        };
    }

    /**
     * Get options array for radio card group
     *
     * @return array<int, array{label: string, description: string, icon: string}>
     */
    public static function radioCardOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[] = [
                'value' => (string) $case->value,
                'label' => $case->label(),
                'description' => __($case->descriptionKey()),
                'icon' => $case->iconClass(),
            ];
        }

        return $options;
    }
}
