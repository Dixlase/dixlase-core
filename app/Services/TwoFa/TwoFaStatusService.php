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

namespace App\Services\TwoFa;

use App\Enums\AuthenticationMode;
use App\Models\SecuritySetting;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * Two-factor authentication status determination service
 *
 * Determines the actual state of two-factor authentication considering global settings and profile settings
 */
class TwoFaStatusService
{
    /**
     * Get the actual two-factor authentication mode
     *
     * @param  \App\Models\Member  $user
     * @return int Actual two-factor authentication mode
     */
    public function getActualTwoFaMode($user): int
    {
        // Get global settings
        $twoFaForceMode = (int) SecuritySetting::getValue('two_fa_mode', AuthenticationMode::UseProfileSetting->value);
        $profileTwoFaMode = is_int($user->two_fa_mode) ? $user->two_fa_mode : $user->two_fa_mode->value;

        // Determine the actual two-factor authentication mode
        if ($twoFaForceMode === AuthenticationMode::UseProfileSetting->value) {
            // Use profile value when following profile settings
            return $profileTwoFaMode;
        } else {
            // Otherwise use global settings
            return $twoFaForceMode;
        }
    }

    /**
     * Determine whether two-factor authentication is enabled
     *
     * @param  \App\Models\Member  $user
     * @return bool True if two-factor authentication is enabled
     */
    public function isTwoFaEnabled($user): bool
    {
        $actualTwoFaMode = $this->getActualTwoFaMode($user);

        return $actualTwoFaMode === AuthenticationMode::Always->value ||
                $actualTwoFaMode === AuthenticationMode::DifferentDevice->value;
    }

    /**
     * Get the actual passkey enabled state
     *
     * @param  \App\Models\Member  $user
     * @return bool True if passkey is enabled
     */
    public function isPasskeyEnabled($user): bool
    {
        // Get passkey mode from global settings
        $twoFaPasskeyMode = (int) SecuritySetting::getValue('two_fa_passkey_mode', '2');

        // Determine the actual passkey enabled state
        if ($twoFaPasskeyMode === 0) {
            // Disabled in global settings
            return false;
        } elseif ($twoFaPasskeyMode === 1) {
            // Enabled in global settings
            return true;
        } else {
            // Follow profile settings
            return $user->two_fa_passkey_enabled ?? true;
        }
    }

    /**
     * Determine whether automatic generation of recovery codes is necessary
     *
     * @param  \App\Models\Member  $user
     * @return bool True if automatic generation of recovery codes is necessary
     */
    public function shouldGenerateRecoveryCodes($user, TwoFaRecoveryCodeService $recoveryCodeService): bool
    {
        return $this->isTwoFaEnabled($user) && ! $recoveryCodeService->hasRecoveryCodes($user);
    }

    /**
     * Determine whether to display the passkey registration promotion modal
     *
     * @param  \App\Models\Member  $user
     * @return bool True if the passkey registration promotion modal should be displayed
     */
    public function shouldPromptPasskeyRegistration($user, TwoFaPasskeyService $passkeyService): bool
    {
        // Do not display if the user has hidden the modal
        if ($user->passkey_prompt_dismissed ?? false) {
            return false;
        }

        if (! $this->isTwoFaEnabled($user)) {
            return false;
        }

        if (! $this->isPasskeyEnabled($user)) {
            return false;
        }

        $passkeyDevices = $passkeyService->getDevices($user);

        return $passkeyDevices->isEmpty();
    }

    /**
     * Get the two-factor authentication mode from global settings
     *
     * @return int Two-factor authentication mode from global settings
     */
    public function getGlobalTwoFaMode(): int
    {
        return (int) SecuritySetting::getValue('two_fa_mode', AuthenticationMode::UseProfileSetting->value);
    }

    /**
     * Get passkey mode from global settings
     *
     * @return int Passkey mode from global settings (0=disabled, 1=enabled, 2=follow profile)
     */
    public function getGlobalPasskeyMode(): int
    {
        return (int) SecuritySetting::getValue('two_fa_passkey_mode', '2');
    }

    /**
     * Determine whether passkey is enabled in global settings (without considering profile settings)
     *
     * @return bool True if passkey is enabled in global settings
     */
    public function isPasskeyEnabledGlobally(): bool
    {
        return $this->getGlobalPasskeyMode() > 0;
    }
}
