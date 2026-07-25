<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\Http\Controllers\Admin;

use App\Enums\LoginIdentifierMode;
use App\Helpers\TwoFaHelper;
use App\Models\Member;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Traits\PasskeyLoginTrait;
use Illuminate\Http\Request;

/**
 * Passkey login controller
 *
 * Login processing using passkey authentication with WebAuthn
 */
class AdminPasskeyLoginController extends AdminController
{
    use PasskeyLoginTrait {
        getChallenge as traitGetChallenge;
    }

    public function __construct(TwoFaPasskeyService $passkeyService)
    {
        parent::__construct();
        $this->passkeyService = $passkeyService;
    }

    /**
     * Get passkey authentication challenge (override)
     *
     * Add mail server settings check
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChallenge(Request $request)
    {
        // Mail server settings check
        $twoFaHelper = app(TwoFaHelper::class);
        if (! $twoFaHelper->isMailConfigured()) {
            return response()->json([
                'success' => false,
                'error' => __($this->getTranslationPrefix().'.two_fa_disabled'),
            ], 422);
        }

        // Call trait method
        return $this->traitGetChallenge($request);
    }

    /**
     * Get user model class name
     */
    protected function getUserModelClass(): string
    {
        return Member::class;
    }

    /**
     * Get settings model class name
     */
    protected function getSettingModelClass(): string
    {
        return \App\Models\SecuritySetting::class;
    }

    /**
     * Get authentication guard name
     */
    protected function getGuardName(): string
    {
        return 'member';
    }

    /**
     * Get dashboard route name
     */
    protected function getDashboardRoute(): string
    {
        return 'admin.dashboard';
    }

    /**
     * Get session key prefix
     */
    protected function getSessionPrefix(): string
    {
        return 'login';
    }

    /**
     * Get login notification service class name
     */
    protected function getLoginNotificationServiceClass(): string
    {
        return \App\Services\AdminLoginNotificationService::class;
    }

    /**
     * Get translation prefix
     */
    protected function getTranslationPrefix(): string
    {
        return 'auth';
    }

    /**
     * Get login identifier mode
     */
    protected function getLoginIdentifierMode(): LoginIdentifierMode
    {
        $value = (int) \App\Models\SecuritySetting::getValue('login_identifier_mode', LoginIdentifierMode::EmailOrAccountName->value);

        return LoginIdentifierMode::tryFrom($value) ?? LoginIdentifierMode::EmailOrAccountName;
    }

    /**
     * Whether login with email address is supported
     */
    protected function supportsEmailLogin(): bool
    {
        return $this->getLoginIdentifierMode()->supportsEmail();
    }

    /**
     * Whether login with account name is supported
     */
    protected function supportsAccountNameLogin(): bool
    {
        return $this->getLoginIdentifierMode()->supportsAccountName();
    }
}
