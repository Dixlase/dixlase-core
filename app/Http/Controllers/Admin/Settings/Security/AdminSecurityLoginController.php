<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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
     * ログイン試行制限設定ページ
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

        // ログイン識別子モード設定
        $loginIdentifierMode = (int) $this->securitySettingRepository->get('login_identifier_mode', LoginIdentifierMode::EmailOrAccountName->value);
        $this->viewParams['loginIdentifierMode'] = $loginIdentifierMode;

        // ログイン通知設定
        $loginNotificationMode = (int) $this->securitySettingRepository->get('login_notification_mode', 3);
        $loginNotificationSendToSystem = (bool) $this->securitySettingRepository->get('login_notification_send_to_system', false);
        $loginNotificationSystemEmail = (string) $this->securitySettingRepository->get('login_notification_system_email', '');

        // 二段階認証の詳細設定
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
     * ログイン試行制限設定の更新
     */
    public function update(AdminSecurityLoginUpdateRequest $request)
    {
        $validated = $request->validated();
        $before = $this->securitySettingRepository->getMultiple(static::SETTING_KEYS);

        if (array_key_exists('login_attempt_limit_enabled', $validated)) {
            $this->securitySettingRepository->set('login_attempt_limit_enabled', $validated['login_attempt_limit_enabled'] ?? false);
        }
        if (array_key_exists('login_attempt_max_attempts', $validated)) {
            $this->securitySettingRepository->set('login_attempt_max_attempts', (int) $validated['login_attempt_max_attempts']);
        }
        if (array_key_exists('login_attempt_max_attempts_ip', $validated)) {
            $this->securitySettingRepository->set('login_attempt_max_attempts_ip', (int) $validated['login_attempt_max_attempts_ip']);
        }
        if (array_key_exists('login_attempt_time_window', $validated)) {
            $this->securitySettingRepository->set('login_attempt_time_window', (int) $validated['login_attempt_time_window']);
        }
        if (array_key_exists('login_attempt_lockout_duration', $validated)) {
            $this->securitySettingRepository->set('login_attempt_lockout_duration', (int) $validated['login_attempt_lockout_duration']);
        }
        if (array_key_exists('login_attempt_lockout_notification_enabled', $validated)) {
            $this->securitySettingRepository->set('login_attempt_lockout_notification_enabled', $validated['login_attempt_lockout_notification_enabled'] ?? false);
        }

        // ログイン識別子モード設定
        if (array_key_exists('login_identifier_mode', $validated)) {
            $this->securitySettingRepository->set('login_identifier_mode', (int) $validated['login_identifier_mode']);
        }

        // ログイン通知設定
        if (array_key_exists('login_notification_mode', $validated)) {
            $this->securitySettingRepository->set('login_notification_mode', (int) $validated['login_notification_mode']);
        }
        if (array_key_exists('login_notification_send_to_system', $validated)) {
            $this->securitySettingRepository->set('login_notification_send_to_system', $validated['login_notification_send_to_system'] ?? false);
        }
        if (array_key_exists('login_notification_system_email', $validated)) {
            $this->securitySettingRepository->set('login_notification_system_email', (string) $validated['login_notification_system_email']);
        }

        // 二段階認証の詳細設定
        if (array_key_exists('two_fa_expire_minutes', $validated)) {
            $this->securitySettingRepository->set('two_fa_expire_minutes', (int) $validated['two_fa_expire_minutes']);
        }
        if (array_key_exists('two_fa_resend_interval_seconds', $validated)) {
            $this->securitySettingRepository->set('two_fa_resend_interval_seconds', (int) $validated['two_fa_resend_interval_seconds']);
        }
        if (array_key_exists('two_fa_max_attempts', $validated)) {
            $this->securitySettingRepository->set('two_fa_max_attempts', (int) $validated['two_fa_max_attempts']);
        }
        if (array_key_exists('two_fa_attempt_window', $validated)) {
            $this->securitySettingRepository->set('two_fa_attempt_window', (int) $validated['two_fa_attempt_window']);
        }
        if (array_key_exists('two_fa_lockout_duration', $validated)) {
            $this->securitySettingRepository->set('two_fa_lockout_duration', (int) $validated['two_fa_lockout_duration']);
        }
        if (array_key_exists('two_fa_lockout_notification_enabled', $validated)) {
            $this->securitySettingRepository->set('two_fa_lockout_notification_enabled', $validated['two_fa_lockout_notification_enabled'] ?? false);
        }
        if (array_key_exists('two_fa_recovery_codes_count', $validated)) {
            $this->securitySettingRepository->set('two_fa_recovery_codes_count', (int) $validated['two_fa_recovery_codes_count']);
        }
        if (array_key_exists('two_fa_recovery_code_regenerate_interval', $validated)) {
            $this->securitySettingRepository->set('two_fa_recovery_code_regenerate_interval', (int) $validated['two_fa_recovery_code_regenerate_interval']);
        }

        $after = $this->securitySettingRepository->getMultiple(static::SETTING_KEYS);
        \App\Facades\Audit::logBulkSettingsChange('security.login', $before, $after, auth()->user());

        return redirect()->back()
            ->with('success', __('admin/settings/security/login.updated'));
    }
}
