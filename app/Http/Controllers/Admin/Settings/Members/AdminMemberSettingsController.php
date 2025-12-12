<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

namespace App\Http\Controllers\Admin\Settings\Members;

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Members\AdminSettingsMemberSettingsRequest;
use App\Models\BaseSetting;
use App\Models\SecuritySetting;
use App\Enums\TwoFactorMode;
use App\Enums\LoginNotificationMode;
use App\Contracts\Repositories\MemberSettingRepositoryInterface;

class AdminMemberSettingsController extends AdminLoggedInController
{
    protected MemberSettingRepositoryInterface $memberSettingRepository;

    public function __construct(MemberSettingRepositoryInterface $memberSettingRepository)
    {
        parent::__construct();
        $this->memberSettingRepository = $memberSettingRepository;
    }

    /**
     * メンバー設定画面
     */
    public function index()
    {
        $passwordMinLength = (int) $this->memberSettingRepository->get('password_min_length', 8);
        $passwordRequireUppercase = (bool) $this->memberSettingRepository->get('password_require_uppercase', true);
        $passwordRequireNumber = (bool) $this->memberSettingRepository->get('password_require_number', true);
        $passwordRequireSymbol = (bool) $this->memberSettingRepository->get('password_require_symbol', true);
        
        $minLengthOptions = collect(__('admin.members.settings.password.min_length_options'))
            ->map(fn($label, $key) => ['value' => (string) $key, 'label' => $label])
            ->values()
            ->toArray();

        $loginNotification = (int) $this->memberSettingRepository->get('login_notification_mode', LoginNotificationMode::UseProfileSetting->value);
        $loginNotificationGlobalOptions = collect(config('admin.global_login_notification_mail_mode'))
            ->map(fn ($value) => [
                'value' => (string) $value,
                'label' => __('common.login_notification_mode.options.' . $value),
            ])
            ->values()
            ->toArray();

        $force2fa = (int) $this->memberSettingRepository->get('force_2fa', TwoFactorMode::Disabled->value);
        
        if (old('force_2fa') !== null) {
            $force2fa = (int) old('force_2fa');
        }
        
        $twoFactorGlobalOptions = collect(config('admin.global_two_factor_mode'))
            ->map(fn ($value) => [
                'value' => (string) $value,
                'label' => str_replace(':account_type', __('common.account_types.member'), __('common.two_factor_mode.options.' . $value)),
            ])
            ->values()
            ->toArray();
        
        $passkeyDbValue = $this->memberSettingRepository->get('enabled_2fa_passkey', '0');
        $passkeyEnabled = $passkeyDbValue === '1';
        
        if (old('enabled_2fa_passkey') !== null) {
            $passkeyEnabled = (bool) old('enabled_2fa_passkey');
        }

        $passwordResetEnabled = (bool) $this->memberSettingRepository->get('password_reset_enabled', true);
        $pwnedPasswordCheckEnabled = (bool) $this->memberSettingRepository->get('pwned_password_check_enabled', false);
        $loginAttemptLimitEnabled = (bool) $this->memberSettingRepository->get('login_attempt_limit_enabled', false);
        $loginAttemptMaxAttempts = (int) $this->memberSettingRepository->get('login_attempt_max_attempts', 5);
        $loginAttemptTimeWindow = (int) $this->memberSettingRepository->get('login_attempt_time_window', 15);
        $loginAttemptLockoutDuration = (int) $this->memberSettingRepository->get('login_attempt_lockout_duration', 30);
        $lockoutNotificationEnabled = (bool) $this->memberSettingRepository->get('lockout_notification_enabled', true);

        $membersSessionLifetimeEnabled = (bool) $this->memberSettingRepository->get('members_session_lifetime_enabled', false);
        $membersSessionLifetime = (int) $this->memberSettingRepository->get('members_session_lifetime', 120);

        $twoFactorExpireMinutes = (int) $this->memberSettingRepository->get('two_factor_expire_minutes', config('two-factor.code_expiration', 5));
        $twoFactorResendIntervalSeconds = (int) $this->memberSettingRepository->get('two_factor_resend_interval_seconds', config('two-factor.resend_interval', 60));

        $twoFaMaxAttempts = (int) $this->memberSettingRepository->get('2fa_max_attempts', 5);
        $twoFaAttemptWindow = (int) $this->memberSettingRepository->get('2fa_attempt_window', 15);
        $twoFaLockoutDuration = (int) $this->memberSettingRepository->get('2fa_lockout_duration', 30);
        $twoFaLockoutNotificationEnabled = (bool) $this->memberSettingRepository->get('2fa_lockout_notification_enabled', true);

        $recoveryCodesCount = (int) $this->memberSettingRepository->get('recovery_codes_count', 5);
        $recoveryCodeRegenerateInterval = (int) $this->memberSettingRepository->get('recovery_code_regenerate_interval', 24);

        $isMailServerTested = $this->isMailServerTested();
        $mailConnectionTestDate = BaseSetting::getValue('mail_connection_test_date');

        $this->viewParams['passwordMinLength'] = $passwordMinLength;
        $this->viewParams['passwordRequireUppercase'] = $passwordRequireUppercase;
        $this->viewParams['passwordRequireNumber'] = $passwordRequireNumber;
        $this->viewParams['passwordRequireSymbol'] = $passwordRequireSymbol;
        $this->viewParams['minLengthOptions'] = $minLengthOptions;
        $this->viewParams['loginNotification'] = $loginNotification;
        $this->viewParams['loginNotificationGlobalOptions'] = $loginNotificationGlobalOptions;
        $this->viewParams['force2fa'] = $force2fa;
        $this->viewParams['twoFactorGlobalOptions'] = $twoFactorGlobalOptions;
        $this->viewParams['passkeyEnabled'] = $passkeyEnabled;
        $this->viewParams['passwordResetEnabled'] = $passwordResetEnabled;
        $this->viewParams['pwnedPasswordCheckEnabled'] = $pwnedPasswordCheckEnabled;
        $this->viewParams['loginAttemptLimitEnabled'] = $loginAttemptLimitEnabled;
        $this->viewParams['loginAttemptMaxAttempts'] = $loginAttemptMaxAttempts;
        $this->viewParams['loginAttemptTimeWindow'] = $loginAttemptTimeWindow;
        $this->viewParams['loginAttemptLockoutDuration'] = $loginAttemptLockoutDuration;
        $this->viewParams['lockoutNotificationEnabled'] = $lockoutNotificationEnabled;
        $this->viewParams['membersSessionLifetimeEnabled'] = $membersSessionLifetimeEnabled;
        $this->viewParams['membersSessionLifetime'] = $membersSessionLifetime;
        $this->viewParams['twoFactorExpireMinutes'] = $twoFactorExpireMinutes;
        $this->viewParams['twoFactorResendIntervalSeconds'] = $twoFactorResendIntervalSeconds;
        $this->viewParams['twoFaMaxAttempts'] = $twoFaMaxAttempts;
        $this->viewParams['twoFaAttemptWindow'] = $twoFaAttemptWindow;
        $this->viewParams['twoFaLockoutDuration'] = $twoFaLockoutDuration;
        $this->viewParams['twoFaLockoutNotificationEnabled'] = $twoFaLockoutNotificationEnabled;
        $this->viewParams['recoveryCodesCount'] = $recoveryCodesCount;
        $this->viewParams['recoveryCodeRegenerateInterval'] = $recoveryCodeRegenerateInterval;
        $this->viewParams['isMailServerTested'] = $isMailServerTested;
        $this->viewParams['mailConnectionTestDate'] = $mailConnectionTestDate;

        $captchaEnabled = filter_var(SecuritySetting::get('captcha_enabled', false), FILTER_VALIDATE_BOOLEAN);
        $captchaAuthenticationResult = filter_var(SecuritySetting::get('captcha_authentication_result', false), FILTER_VALIDATE_BOOLEAN);
        $captchaAdminLoginEnabled = (bool) $this->memberSettingRepository->get('captcha_admin_login_enabled', false);
        $captchaAvailable = $captchaEnabled && $captchaAuthenticationResult;
        $this->viewParams['captchaEnabled'] = $captchaEnabled;
        $this->viewParams['captchaAuthenticationResult'] = $captchaAuthenticationResult;
        $this->viewParams['captchaAvailable'] = $captchaAvailable;
        $this->viewParams['captchaAdminLoginEnabled'] = $captchaAdminLoginEnabled;

        $settingsSection = request('settings_section');

        if (request()->routeIs('admin.members.settings.password')) {
            $settingsSection = 'password';
        } elseif (request()->routeIs('admin.members.settings.session')) {
            $settingsSection = 'session';
        } elseif (request()->routeIs('admin.members.settings.auth')) {
            $settingsSection = 'auth';
        }

        $viewName = match ($settingsSection) {
            'password' => 'admin.members.settings.password',
            'session' => 'admin.members.settings.session',
            'auth' => 'admin.members.settings.auth',
            default => 'admin.members.settings.index',
        };

        return view($viewName, $this->viewParams);
    }

    /**
     * パスワード設定画面
     */
    public function password()
    {
        return $this->index();
    }

    /**
     * セッション設定画面
     */
    public function session()
    {
        return $this->index();
    }

    /**
     * 認証設定画面
     */
    public function auth()
    {
        return $this->index();
    }

    /**
     * メンバー設定更新
     */
    public function update(AdminSettingsMemberSettingsRequest $request)
    {
        $validated = $request->validated();

        if (array_key_exists('password_min_length', $validated)) {
            $this->memberSettingRepository->set('password_min_length', (int) $validated['password_min_length']);
        }
        if (array_key_exists('password_require_uppercase', $validated)) {
            $this->memberSettingRepository->set('password_require_uppercase', (int) $validated['password_require_uppercase']);
        }
        if (array_key_exists('password_require_number', $validated)) {
            $this->memberSettingRepository->set('password_require_number', (int) $validated['password_require_number']);
        }
        if (array_key_exists('password_require_symbol', $validated)) {
            $this->memberSettingRepository->set('password_require_symbol', (int) $validated['password_require_symbol']);
        }
        if (array_key_exists('password_reset_enabled', $validated)) {
            $this->memberSettingRepository->set('password_reset_enabled', (bool) $validated['password_reset_enabled']);
        }
        if (array_key_exists('pwned_password_check_enabled', $validated)) {
            $this->memberSettingRepository->set('pwned_password_check_enabled', (bool) $validated['pwned_password_check_enabled']);
        }

        if (array_key_exists('members_session_lifetime_enabled', $validated)) {
            $this->memberSettingRepository->set('members_session_lifetime_enabled', $validated['members_session_lifetime_enabled'] ? '1' : '0');
        }
        if (array_key_exists('members_session_lifetime', $validated)) {
            $this->memberSettingRepository->set('members_session_lifetime', (string) $validated['members_session_lifetime']);
        }

        if (array_key_exists('login_notification_mode', $validated)) {
            $this->memberSettingRepository->set('login_notification_mode', (int) $validated['login_notification_mode']);
        }
        if (array_key_exists('login_attempt_limit_enabled', $validated)) {
            $this->memberSettingRepository->set('login_attempt_limit_enabled', $validated['login_attempt_limit_enabled'] ? '1' : '0');
        }
        if (array_key_exists('login_attempt_max_attempts', $validated)) {
            $this->memberSettingRepository->set('login_attempt_max_attempts', (string) $validated['login_attempt_max_attempts']);
        }
        if (array_key_exists('login_attempt_time_window', $validated)) {
            $this->memberSettingRepository->set('login_attempt_time_window', (string) $validated['login_attempt_time_window']);
        }
        if (array_key_exists('login_attempt_lockout_duration', $validated)) {
            $this->memberSettingRepository->set('login_attempt_lockout_duration', (string) $validated['login_attempt_lockout_duration']);
        }
        if (array_key_exists('lockout_notification_enabled', $validated)) {
            $this->memberSettingRepository->set('lockout_notification_enabled', $validated['lockout_notification_enabled'] ? '1' : '0');
        }

        if (array_key_exists('force_2fa', $validated)) {
            $this->memberSettingRepository->set('force_2fa', (int) $validated['force_2fa']);
        }
        if (array_key_exists('two_factor_expire_minutes', $validated)) {
            $this->memberSettingRepository->set('two_factor_expire_minutes', (string) $validated['two_factor_expire_minutes']);
        }
        if (array_key_exists('two_factor_resend_interval_seconds', $validated)) {
            $this->memberSettingRepository->set('two_factor_resend_interval_seconds', (string) $validated['two_factor_resend_interval_seconds']);
        }
        if (array_key_exists('enabled_2fa_passkey', $validated)) {
            $passkeyValue = $validated['enabled_2fa_passkey'] ? '1' : '0';
            $this->memberSettingRepository->set('enabled_2fa_passkey', $passkeyValue);
        }
        if (array_key_exists('2fa_max_attempts', $validated)) {
            $this->memberSettingRepository->set('2fa_max_attempts', (string) $validated['2fa_max_attempts']);
        }
        if (array_key_exists('2fa_attempt_window', $validated)) {
            $this->memberSettingRepository->set('2fa_attempt_window', (string) $validated['2fa_attempt_window']);
        }
        if (array_key_exists('2fa_lockout_duration', $validated)) {
            $this->memberSettingRepository->set('2fa_lockout_duration', (string) $validated['2fa_lockout_duration']);
        }
        if (array_key_exists('2fa_lockout_notification_enabled', $validated)) {
            $this->memberSettingRepository->set('2fa_lockout_notification_enabled', $validated['2fa_lockout_notification_enabled'] ? '1' : '0');
        }
        if (array_key_exists('recovery_codes_count', $validated)) {
            $this->memberSettingRepository->set('recovery_codes_count', (string) $validated['recovery_codes_count']);
        }
        if (array_key_exists('recovery_code_regenerate_interval', $validated)) {
            $this->memberSettingRepository->set('recovery_code_regenerate_interval', (string) $validated['recovery_code_regenerate_interval']);
        }

        if (array_key_exists('captcha_admin_login_enabled', $validated)) {
            $this->memberSettingRepository->set('captcha_admin_login_enabled', $request->boolean('captcha_admin_login_enabled') ? '1' : '0');
        }

        return redirect()->back()
            ->with('success', __('admin.members.settings.updated'));
    }

    private function isMailServerTested(): bool
    {
        $connectionTested = (bool) BaseSetting::getValue('mail_connection_tested', false);
        $sendTested = (bool) BaseSetting::getValue('mail_send_tested', false);
        $receiveTested = (bool) BaseSetting::getValue('mail_receive_tested', false);
        
        return $connectionTested && $sendTested && $receiveTested;
    }
}
