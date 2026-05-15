<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Traits\TwoFa;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Two-factor authentication enable condition check functionality
 *
 * This Trait provides functionality to check whether two-factor authentication can be safely enabled.
 * Shared between the Core Member model and the user plugin User model.
 *
 * Usage:
 * - Use in models: canEnableTwoFa(), getTwoFaEnableBlockReasons()
 * - Use in request classes: isMailServerConfigured()
 */
trait TwoFactorEnableCheck
{
    /**
     * Check if two-factor authentication can be enabled
     *
     * Safety rule: Must meet at least one of the following conditions
     * - Mail server is configured
     * - At least one passkey is registered
     * - Recovery codes are generated
     */
    public function canEnableTwoFa(): bool
    {
        // Check if mail server is configured
        $mailConfigured = \App\Services\MailServerValidatorService::isMailServerTested();

        // Check if passkey is registered
        $hasPasskey = $this->twoFaPasskeys()->exists();

        // Check if recovery codes are generated
        $hasRecoveryCode = $this->twoFaRecoveryCodes()->where('used_at', null)->exists();

        return $mailConfigured || $hasPasskey || $hasRecoveryCode;
    }

    /**
     * Get reasons why two-factor authentication cannot be enabled
     *
     * @return array List of reasons
     */
    public function getTwoFaEnableBlockReasons(): array
    {
        $reasons = [];

        $mailConfigured = \App\Services\MailServerValidatorService::isMailServerTested();
        $hasPasskey = $this->twoFaPasskeys()->exists();
        $hasRecoveryCode = $this->twoFaRecoveryCodes()->where('used_at', null)->exists();

        if (! $mailConfigured && ! $hasPasskey && ! $hasRecoveryCode) {
            $reasons[] = 'no_backup_method';
        }

        return $reasons;
    }
}
