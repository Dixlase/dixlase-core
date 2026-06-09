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

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Interface for users with two-factor authentication functionality
 */
interface TwoFaInterface
{
    /**
     * Get user ID
     */
    public function getId(): int;

    /**
     * Get email address
     */
    public function getEmail(): string;

    /**
     * Get display name
     */
    public function getDisplayName(): string;

    /**
     * Get account name
     */
    public function getAccountName(): ?string;

    /**
     * Get two-factor authentication mode
     */
    public function getTwoFaMode(): int;

    /**
     * Whether passkey is enabled
     */
    public function isTwoFaPasskeyEnabled(): bool;

    /**
     * Get default two-factor authentication method
     */
    public function getTwoFaDefaultMethod(): int;

    /**
     * Passkey devices relation
     */
    public function twoFaPasskeys(): HasMany;

    /**
     * Recovery codes relation
     */
    public function twoFaRecoveryCodes(): HasMany;

    /**
     * Two-factor authentication attempts relation
     */
    public function twoFaAttempts(): HasMany;

    /**
     * Two-factor authentication tokens relation
     */
    public function twoFaTokens(): HasMany;
}
