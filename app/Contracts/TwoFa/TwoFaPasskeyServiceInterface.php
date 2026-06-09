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

namespace App\Contracts\TwoFa;

use App\Contracts\TwoFaInterface;
use App\Models\Member;

/**
 * Contract for Passkey (WebAuthn) authentication service
 *
 * Provides passkey registration, authentication, trusted device management, etc.
 */
interface TwoFaPasskeyServiceInterface
{
    /**
     * Whether Passkey is available
     */
    public function isAvailable(): bool;

    /**
     * Whether the user has Passkey credentials
     */
    public function hasCredentials(TwoFaInterface $user): bool;

    /**
     * Generate Passkey challenge (for 2FA)
     */
    public function generatePasskeyChallenge($user): array;

    /**
     * Generate passkey challenge for login
     */
    public function generateLoginChallenge(TwoFaInterface $user): array;

    /**
     * Verify passkey authentication for login
     */
    public function verifyLoginChallenge(TwoFaInterface $user, array $data, ?string $challengeId = null): bool;

    /**
     * Verify Passkey authentication
     */
    public function validatePasskeyAuth($user, $input): bool;

    /**
     * Get all Passkey devices for the user
     *
     * @deprecated Use getCredentials() instead.
     */
    public function getDevices(TwoFaInterface $user);

    /**
     * Register WebAuthn credentials
     */
    public function registerCredential(TwoFaInterface $user, array $credentialData, ?string $deviceName = null);

    /**
     * Verify WebAuthn authentication
     */
    public function verifyAssertion(TwoFaInterface $user, array $assertionData): bool;

    /**
     * Get WebAuthn credentials list
     */
    public function getCredentials(TwoFaInterface $user);

    /**
     * Delete WebAuthn credentials
     */
    public function revokeCredential(TwoFaInterface $user, string $credentialId): bool;

    /**
     * Delete all WebAuthn credentials
     */
    public function revokeAllCredentials(TwoFaInterface $user): int;

    /**
     * Generate WebAuthn registration challenge
     */
    public function generateRegistrationChallenge(TwoFaInterface $user): array;

    /**
     * Generate WebAuthn authentication challenge
     */
    public function generateAuthenticationChallenge(TwoFaInterface $user): array;

    /**
     * Check if the current device is trusted
     */
    public function isTrustedDevice(Member $member): bool;

    /**
     * Delete trusted device
     */
    public function revokeDevice(Member $member, int $deviceId): bool;

    /**
     * Get trusted device list
     */
    public function getTrustedDevices(Member $member);

    /**
     * Delete all trusted devices
     */
    public function revokeAllTrustedDevices(Member $member): int;
}
