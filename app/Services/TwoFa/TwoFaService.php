<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\Services\TwoFa;

use App\Enums\TwoFaMethod;
use App\Helpers\TwoFaHelper;
use App\Services\EmailAuthenticationService;
use Illuminate\Support\Facades\Log;

/**
 * Two-factor authentication service
 *
 * Provides common two-factor authentication functionality for admin panel and user plugin
 * Uses a combination of Core generic services (TwoFaHelper, EmailAuthenticationService, etc.)
 * Settings model class and context can be specified in constructor
 */
class TwoFaService
{
    protected TwoFaHelper $helper;

    protected EmailAuthenticationService $emailAuth;

    protected TwoFaPasskeyService $passkeyAuth;

    protected TwoFaRecoveryCodeService $recoveryCode;

    protected TwoFaAttemptService $attemptService;

    protected string $settingModelClass;

    protected string $context;

    public function __construct(
        TwoFaHelper $helper,
        EmailAuthenticationService $emailAuth,
        TwoFaPasskeyService $passkeyAuth,
        TwoFaRecoveryCodeService $recoveryCode,
        TwoFaAttemptService $attemptService,
        string $settingModelClass,
        string $context
    ) {
        $this->helper = $helper;
        $this->emailAuth = $emailAuth;
        $this->passkeyAuth = $passkeyAuth;
        $this->recoveryCode = $recoveryCode;
        $this->attemptService = $attemptService;
        $this->settingModelClass = $settingModelClass;
        $this->context = $context;
    }

    /**
     * Generate and send two-factor authentication code via email
     *
     * @param  mixed  $user  User model
     * @param  int|null  $method  Authentication method (auto-detected if null)
     * @return string|array Generated code or challenge data
     */
    public function generate($user, ?int $method = null)
    {
        $effectiveMethod = $method ?? $this->helper->getEffectiveAuthMethod($user, $this->settingModelClass);

        return match ($effectiveMethod) {
            TwoFaMethod::EMAIL->value => $this->generateEmailCode($user),
            TwoFaMethod::PASSKEY->value => $this->generatePasskeyChallenge($user),
            default => $this->generateEmailCode($user),
        };
    }

    /**
     * Verify two-factor authentication
     *
     * @param  mixed  $user  User model
     * @param  string|array  $input  Input code or authentication data
     * @param  int|null  $method  Authentication method (auto-detected if null)
     * @return bool Verification result
     */
    public function validate($user, $input, ?int $method = null): bool
    {
        $effectiveMethod = $method ?? $this->helper->getEffectiveAuthMethod($user, $this->settingModelClass);

        return match ($effectiveMethod) {
            TwoFaMethod::EMAIL->value => $this->validateEmailCode($user, $input),
            TwoFaMethod::PASSKEY->value => $this->validatePasskeyAuth($user, $input),
            default => $this->validateEmailCode($user, $input),
        };
    }

    /**
     * Determine whether two-factor authentication is required
     *
     * @param  mixed  $member  User model
     * @return bool Whether 2FA is required
     */
    public function has($member): bool
    {
        return $this->helper->isTwoFaEnabled($member, $this->settingModelClass);
    }

    /**
     * Determine whether access is from a different environment
     *
     * @param  mixed  $member  User model
     * @return bool Whether it's a different environment
     */
    public function isDifferentEnvironment($member): bool
    {
        return $this->helper->isDifferentEnvironment($member);
    }

    /**
     * Get system settings
     */
    public function getSystemSettings(): array
    {
        return $this->helper->getTwoFaSettings($this->settingModelClass);
    }

    /**
     * Get authentication method to use
     *
     * @param  mixed  $user  User model
     * @return int Authentication method
     */
    public function getEffectiveAuthMethod($user): int
    {
        return $this->helper->getEffectiveAuthMethod($user, $this->settingModelClass);
    }

    /**
     * Get available authentication methods
     *
     * @param  mixed  $user  User model
     * @return array Available authentication methods
     */
    public function getAvailableMethods($user): array
    {
        $systemSettings = $this->getSystemSettings();
        $methods = [];

        // Exclude passkey after password login
        $authMethod = session('login.auth_method');
        $isPasswordLogin = $authMethod === 'password';

        foreach ($systemSettings['enabled_methods'] as $method) {
            // Passkey cannot be used after password login
            if ($isPasswordLogin && $method === TwoFaMethod::PASSKEY->value) {
                continue;
            }

            $available = match ($method) {
                TwoFaMethod::EMAIL->value => true,
                TwoFaMethod::PASSKEY->value => $this->passkeyAuth->isAvailable(),
                default => false,
            };

            if ($available) {
                $methods[] = [
                    'value' => $method,
                    'label' => TwoFaMethod::from($method)->label(),
                    'setup_required' => $this->isSetupRequired($user, $method),
                ];
            }
        }

        return $methods;
    }

    /**
     * Determine if authentication method setup is required
     *
     * @param  mixed  $user  User model
     * @param  int  $method  Authentication method
     * @return bool Whether setup is required
     */
    private function isSetupRequired($user, int $method): bool
    {
        return match ($method) {
            TwoFaMethod::EMAIL->value => false, // Email authentication is always available
            TwoFaMethod::PASSKEY->value => ! $this->passkeyAuth->hasCredentials($user),
            default => true,
        };
    }

    /**
     * Generate email authentication code
     */
    private function generateEmailCode($user): string
    {
        return $this->emailAuth->generateAndSendCode($user, $this->context);
    }

    /**
     * Generate passkey challenge
     */
    private function generatePasskeyChallenge($user): array
    {
        return $this->passkeyAuth->generatePasskeyChallenge($user);
    }

    /**
     * Verify email authentication code
     */
    private function validateEmailCode($user, string $inputCode): bool
    {
        // Lockout check
        if ($this->attemptService->isLockedOut($user)) {
            Log::warning('[2FA] Email code validation blocked - locked out', [
                'user_id' => $user->id,
                'context' => $this->context,
            ]);

            return false;
        }

        $result = $this->emailAuth->validateCode($user, $inputCode);

        // Record attempt
        $this->attemptService->recordAttempt($user, 'email', $result);

        return $result;
    }

    /**
     * Verify passkey authentication
     */
    private function validatePasskeyAuth($user, $input): bool
    {
        // Lockout check
        if ($this->attemptService->isLockedOut($user)) {
            Log::warning('[2FA] Passkey validation blocked - locked out', [
                'user_id' => $user->id,
                'context' => $this->context,
            ]);

            return false;
        }

        $result = $this->passkeyAuth->validatePasskeyAuth($user, $input);

        // Record attempt
        $this->attemptService->recordAttempt($user, 'passkey', $result);

        return $result;
    }

    /**
     * Verify recovery code
     */
    public function validateRecoveryCode($user, string $code): bool
    {
        // Lockout check
        if ($this->attemptService->isLockedOut($user)) {
            Log::warning('[2FA] Recovery code validation blocked - locked out', [
                'user_id' => $user->id,
                'context' => $this->context,
            ]);

            return false;
        }

        $result = $this->recoveryCode->validate($user, $code);

        // Record attempt
        $this->attemptService->recordAttempt($user, 'recovery_code', $result);

        if ($result) {
            // Get remaining count
            $remaining = $this->recoveryCode->getRemainingCount($user);

            Log::info('[2FA] Recovery code used', [
                'user_id' => $user->id,
                'context' => $this->context,
                'remaining_codes' => $remaining,
            ]);
        }

        return $result;
    }

    /**
     * Check lockout status
     */
    public function checkLockout($user): array
    {
        $isLockedOut = $this->attemptService->isLockedOut($user);

        if ($isLockedOut) {
            $remainingTime = $this->attemptService->getRemainingLockoutTime($user);

            return [
                'locked_out' => true,
                'remaining_minutes' => $remainingTime,
            ];
        }

        // Check attempt limit
        if ($this->attemptService->hasReachedMaxAttempts($user)) {
            return [
                'locked_out' => true,
                'remaining_minutes' => $this->attemptService->getRemainingLockoutTime($user),
            ];
        }

        return [
            'locked_out' => false,
            'remaining_attempts' => $this->attemptService->getRemainingAttempts($user),
        ];
    }
}
