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

namespace App\Http\Controllers\Admin\Settings\Security;

use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\Enums\LoginIdentifierMode;
use App\Helpers\AdminModeHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Security\AdminSecurityLoginUpdateRequest;

class AdminSecurityLoginController extends AdminLoggedInController
{
    protected const SETTING_KEYS = [
        'login_attempt_limit_enabled',
        'login_attempt_max_attempts',
        'login_attempt_max_attempts_ip',
        'login_attempt_time_window',
        'login_attempt_lockout_duration',
        'login_attempt_lockout_notification_enabled',
        'login_identifier_mode',
        'login_notification_mode',
        'login_notification_send_to_system',
        'login_notification_system_email',
        'two_fa_expire_minutes',
        'two_fa_resend_interval_seconds',
        'two_fa_max_attempts',
        'two_fa_attempt_window',
        'two_fa_lockout_duration',
        'two_fa_lockout_notification_enabled',
        'two_fa_recovery_codes_count',
        'two_fa_recovery_code_regenerate_interval',
    ];

    protected SecuritySettingRepositoryInterface $securitySettingRepository;

    public function __construct(
        SecuritySettingRepositoryInterface $securitySettingRepository
    ) {
        parent::__construct();
        $this->securitySettingRepository = $securitySettingRepository;
    }

    /**
     * Login attempt restriction settings page
     */
    public function index()
    {
        $settings = [
            'login_attempt_limit_enabled' => filter_var($this->securitySettingRepository->get('login_attempt_limit_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'login_attempt_max_attempts' => (int) $this->securitySettingRepository->get('login_attempt_max_attempts', 5),
            'login_attempt_max_attempts_ip' => (int) $this->securitySettingRepository->get('login_attempt_max_attempts_ip', 10),
            'login_attempt_time_window' => (int) $this->securitySettingRepository->get('login_attempt_time_window', 15),
            'login_attempt_lockout_duration' => (int) $this->securitySettingRepository->get('login_attempt_lockout_duration', 30),
            'login_attempt_lockout_notification_enabled' => filter_var($this->securitySettingRepository->get('login_attempt_lockout_notification_enabled', true), FILTER_VALIDATE_BOOLEAN),
        ];

        // Login identifier mode settings
        $loginIdentifierMode = (int) $this->securitySettingRepository->get('login_identifier_mode', LoginIdentifierMode::EmailOrAccountName->value);
        $this->viewParams['loginIdentifierMode'] = $loginIdentifierMode;

        // Login notification settings
        $loginNotificationMode = (int) $this->securitySettingRepository->get('login_notification_mode', 3);
        $loginNotificationSendToSystem = (bool) $this->securitySettingRepository->get('login_notification_send_to_system', false);
        $loginNotificationSystemEmail = (string) $this->securitySettingRepository->get('login_notification_system_email', '');

        // Two-factor authentication detailed settings
        $twoFaExpireMinutes = (int) $this->securitySettingRepository->get('two_fa_expire_minutes', config('two-fa.code_expiration', 5));
        $twoFaResendIntervalSeconds = (int) $this->securitySettingRepository->get('two_fa_resend_interval_seconds', config('two-fa.resend_interval', 60));
        $twoFaMaxAttempts = (int) $this->securitySettingRepository->get('two_fa_max_attempts', 5);
        $twoFaAttemptWindow = (int) $this->securitySettingRepository->get('two_fa_attempt_window', 15);
        $twoFaLockoutDuration = (int) $this->securitySettingRepository->get('two_fa_lockout_duration', 30);
        $twoFaLockoutNotificationEnabled = (bool) $this->securitySettingRepository->get('two_fa_lockout_notification_enabled', true);
        $twoFaRecoveryCodesCount = (int) $this->securitySettingRepository->get('two_fa_recovery_codes_count', 5);
        $twoFaRecoveryCodeRegenerateInterval = (int) $this->securitySettingRepository->get('two_fa_recovery_code_regenerate_interval', 24);

        $this->viewParams['settings'] = $settings;
        $this->viewParams['loginNotificationMode'] = $loginNotificationMode;
        $this->viewParams['loginNotificationSendToSystem'] = $loginNotificationSendToSystem;
        $this->viewParams['loginNotificationSystemEmail'] = $loginNotificationSystemEmail;
        $this->viewParams['twoFaExpireMinutes'] = $twoFaExpireMinutes;
        $this->viewParams['twoFaResendIntervalSeconds'] = $twoFaResendIntervalSeconds;
        $this->viewParams['twoFaMaxAttempts'] = $twoFaMaxAttempts;
        $this->viewParams['twoFaAttemptWindow'] = $twoFaAttemptWindow;
        $this->viewParams['twoFaLockoutDuration'] = $twoFaLockoutDuration;
        $this->viewParams['twoFaLockoutNotificationEnabled'] = $twoFaLockoutNotificationEnabled;
        $this->viewParams['twoFaRecoveryCodesCount'] = $twoFaRecoveryCodesCount;
        $this->viewParams['twoFaRecoveryCodeRegenerateInterval'] = $twoFaRecoveryCodeRegenerateInterval;
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.security.login');

        return view('admin.settings.security.login', $this->viewParams);
    }

    /**
     * Update login attempt restriction settings
     */
    public function update(AdminSecurityLoginUpdateRequest $request)
    {
        $actor = new \App\Actors\MemberActor(\App\Helpers\AdminHelper::getMember());

        // Classify boolean and int type keys
        $booleanKeys = ['login_attempt_limit_enabled', 'login_attempt_lockout_notification_enabled',
            'login_notification_send_to_system', 'two_fa_lockout_notification_enabled'];
        $stringKeys = ['login_notification_system_email'];

        \App\Actions\Settings\UpdateSettingsAction::make(
            repository: $this->securitySettingRepository,
            settingsPage: 'security.login',
            settingKeys: static::SETTING_KEYS,
            writeCallback: function ($repo, $data) use ($booleanKeys, $stringKeys) {
                foreach (static::SETTING_KEYS as $key) {
                    if (array_key_exists($key, $data)) {
                        if (in_array($key, $booleanKeys)) {
                            $repo->set($key, $data[$key] ?? false);
                        } elseif (in_array($key, $stringKeys)) {
                            $repo->set($key, (string) $data[$key]);
                        } else {
                            $repo->set($key, (int) $data[$key]);
                        }
                    }
                }
            },
            permission: \App\Enums\Permission::SETTINGS_SECURITY,
        )->execute($actor, $request->validated());

        return redirect()->back()
            ->with('success', __('admin/settings/security/login.updated'));
    }
}
