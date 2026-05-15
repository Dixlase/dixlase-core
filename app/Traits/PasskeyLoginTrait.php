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

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Common trait for passkey login
 *
 * Login process using passkey authentication with WebAuthn
 */
trait PasskeyLoginTrait
{
    protected $passkeyService;

    /**
     * Get user model class name (implemented in child class)
     *
     * @return string User model class name
     */
    abstract protected function getUserModelClass(): string;

    /**
     * Get settings model class name (implemented in child class)
     *
     * @return string Settings model class name
     */
    abstract protected function getSettingModelClass(): string;

    /**
     * Get authentication guard name (implemented in child class)
     *
     * @return string Guard name (e.g., 'member', 'user')
     */
    abstract protected function getGuardName(): string;

    /**
     * Get dashboard route name (implemented in child class)
     *
     * @return string Route name
     */
    abstract protected function getDashboardRoute(): string;

    /**
     * Get session key prefix (implemented in child class)
     *
     * @return string Prefix (e.g., 'login', 'user_login')
     */
    abstract protected function getSessionPrefix(): string;

    /**
     * Get login notification service class name (implemented in child class)
     *
     * @return string Login notification service class name
     */
    abstract protected function getLoginNotificationServiceClass(): string;

    /**
     * Get translation prefix (implemented in child class)
     *
     * @return string Translation prefix (e.g., 'auth', 'dixlase-users::auth')
     */
    abstract protected function getTranslationPrefix(): string;

    /**
     * Whether login with email address is supported (implemented in child class)
     */
    abstract protected function supportsEmailLogin(): bool;

    /**
     * Whether login with account name is supported (implemented in child class)
     */
    abstract protected function supportsAccountNameLogin(): bool;

    /**
     * Get passkey authentication challenge
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChallenge(Request $request)
    {
        $request->validate([
            'login' => 'required|string|max:255',
        ]);

        $login = $request->input('login');
        $userModelClass = $this->getUserModelClass();

        // Search user
        $user = $this->findUserByLogin($login, $userModelClass);

        if (! $user) {
            $errorMessage = __($this->getTranslationPrefix().'.failed');

            return response()->json([
                'success' => false,
                'error' => $errorMessage,
            ], 422);
        }

        // Check if passkey is registered
        if (! $user->webauthnCredentials()->exists()) {
            $errorMessage = __($this->getTranslationPrefix().'.no_passkey_registered');

            return response()->json([
                'success' => false,
                'error' => $errorMessage,
            ], 422);
        }

        // Check if two-factor authentication is enabled
        $settingModelClass = $this->getSettingModelClass();
        $globalTwoFaMode = $settingModelClass::getValue('two_fa_mode', 0);

        // Check individual settings only when global settings are disabled (0)
        if ($globalTwoFaMode == 0) {
            if ($user->getTwoFaMode() === 0) {
                $errorMessage = __($this->getTranslationPrefix().'.two_fa_disabled');

                return response()->json([
                    'success' => false,
                    'error' => $errorMessage,
                ], 422);
            }
        }

        try {
            // Generate WebAuthn challenge
            $challengeData = $this->passkeyService->generateLoginChallenge($user);

            // Save user ID and challenge ID to session
            session([
                'passkey_login_'.$this->getSessionPrefix().'_id' => $user->id,
                'passkey_challenge_id' => $challengeData['id'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'challenge' => $challengeData['publicKey'],
            ]);
        } catch (\Exception $e) {
            Log::error('Passkey challenge generation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => __('common.error_occurred'),
            ], 500);
        }
    }

    /**
     * Verify passkey authentication and login
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request)
    {
        $sessionKey = 'passkey_login_'.$this->getSessionPrefix().'_id';
        $userId = session($sessionKey);

        if (! $userId) {
            return response()->json([
                'error' => __($this->getTranslationPrefix().'.failed'),
            ], 422);
        }

        $userModelClass = $this->getUserModelClass();
        $user = $userModelClass::find($userId);

        if (! $user) {
            session()->forget($sessionKey);

            return response()->json([
                'error' => __($this->getTranslationPrefix().'.failed'),
            ], 422);
        }

        try {
            // Verify WebAuthn authentication
            $challengeId = session('passkey_challenge_id');
            $verified = $this->passkeyService->verifyLoginChallenge($user, $request->all(), $challengeId);

            if (! $verified) {
                return response()->json([
                    'error' => __($this->getTranslationPrefix().'.failed'),
                ], 422);
            }

            // Login successful
            $guardName = $this->getGuardName();
            Auth::guard($guardName)->login($user, true);
            session()->forget([$sessionKey, 'passkey_challenge_id']);

            // Record passkey authentication
            session([$this->getSessionPrefix().'.auth_method' => 'passkey']);

            // Record to file log
            Log::info('Passkey login successful', [
                'user_id' => $user->id,
                'guard' => $guardName,
                'ip' => $request->ip(),
            ]);

            // Login notification
            if ($user->login_notification_mode !== 0) {
                try {
                    $notificationServiceClass = $this->getLoginNotificationServiceClass();
                    $notificationService = app($notificationServiceClass);
                    $notificationService->handle($user, $request);
                } catch (\Exception $e) {
                    Log::warning('Login notification failed', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'redirect' => route($this->getDashboardRoute()),
            ]);
        } catch (\Exception $e) {
            Log::error('Passkey verification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => __($this->getTranslationPrefix().'.failed'),
            ], 422);
        }
    }

    /**
     * Find user from login input value
     *
     * Delegation pattern: search based on supportsEmailLogin() / supportsAccountNameLogin() results
     *
     * @param  string  $login  Login input value
     * @param  string  $userModelClass  User model class name
     * @return mixed User model or null
     */
    protected function findUserByLogin(string $login, string $userModelClass)
    {
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL) !== false;

        if ($isEmail) {
            if (! $this->supportsEmailLogin()) {
                return;
            }

            return $userModelClass::where('email', $login)->first();
        }

        if ($this->supportsAccountNameLogin()) {
            return $userModelClass::where('account_name', $login)->first();
        }
    }
}
