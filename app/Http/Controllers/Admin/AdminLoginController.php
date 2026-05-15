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

namespace App\Http\Controllers\Admin;

use App\Enums\LoginIdentifierMode;
use App\Models\Member;
use App\Repositories\SiteSettingRepository;

/**
 * Admin panel login controller
 *
 * Provides login processing and authentication-related settings
 */
class AdminLoginController extends AdminController
{
    use \App\Traits\AccountVerificationTrait;
    use \App\Traits\LoginTrait;

    protected SiteSettingRepository $baseSettingRepository;

    public function __construct(SiteSettingRepository $baseSettingRepository)
    {
        parent::__construct();
        $this->baseSettingRepository = $baseSettingRepository;
    }

    /**
     * Get login route name
     */
    protected function getLoginRoute(): string
    {
        return 'admin.login';
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
     * Get user model class name
     */
    protected function getUserModelClass(): string
    {
        return Member::class;
    }

    /**
     * Get authentication guard name
     */
    protected function getGuardName(): string
    {
        return config('auth.defaults.guard', 'member');
    }

    /**
     * Get context
     */
    protected function getContext(): string
    {
        return 'admin';
    }

    /**
     * Get two-factor authentication route prefix
     */
    protected function getTwoFaRoutePrefix(): string
    {
        return 'admin';
    }

    /**
     * Get administrator email address settings key
     */
    protected function getAdminEmailSettingKey(): string
    {
        return 'system_admin_email';
    }

    /**
     * Get notification email address settings key
     */
    protected function getNotificationEmailSettingKey(): string
    {
        return 'notification_email';
    }

    /**
     * Get redirect destination after logout
     */
    protected function getLogoutRedirectRoute(): string
    {
        return 'admin.login';
    }

    /**
     * Get settings model class name
     */
    protected function getSettingModelClass(): string
    {
        return \App\Models\SecuritySetting::class;
    }

    /**
     * Get login attempt model class name
     */
    protected function getLoginAttemptModelClass(): string
    {
        return \App\Models\MemberLoginAttempt::class;
    }

    /**
     * Get lockout service class name
     */
    protected function getLockoutServiceClass(): string
    {
        return \App\Services\AdminLoginLockoutService::class;
    }

    /**
     * Get login notification service class name
     */
    protected function getLoginNotificationServiceClass(): string
    {
        return \App\Services\AdminLoginNotificationService::class;
    }

    /**
     * Get login view name
     */
    protected function getLoginViewName(): string
    {
        return 'admin::login';
    }

    /**
     * Get CAPTCHA action name
     */
    protected function getCaptchaAction(): string
    {
        return 'admin_login';
    }

    /**
     * Get whether password reset feature is enabled
     */
    protected function isPasswordResetEnabled(): bool
    {
        return (bool) \App\Models\SecuritySetting::getValue('password_reset_enabled', false);
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
     * Whether to support login with account name
     */
    protected function supportsAccountNameLogin(): bool
    {
        return $this->getLoginIdentifierMode()->supportsAccountName();
    }

    /**
     * Whether to support login with email address
     */
    protected function supportsEmailLogin(): bool
    {
        return $this->getLoginIdentifierMode()->supportsEmail();
    }

    /**
     * Whether to support login with pending_email
     */
    protected function supportsPendingEmailLogin(): bool
    {
        return false;
    }

    /**
     * Get the route name for the recovery code screen
     */
    protected function getRecoveryCodeRoute(): string
    {
        return 'admin.two-fa.recovery-code.show';
    }
}
