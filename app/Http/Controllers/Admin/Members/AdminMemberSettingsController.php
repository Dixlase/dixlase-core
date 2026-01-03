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

namespace App\Http\Controllers\Admin\Members;

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\BaseSetting;
use App\Models\SecuritySetting;
use App\Enums\TwoFactorMode;
use App\Enums\LoginNotificationMode;
use App\Contracts\Repositories\MemberSettingRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminMemberSettingsController extends AdminLoggedInController
{
    protected MemberSettingRepositoryInterface $memberSettingRepository;

    public function __construct(MemberSettingRepositoryInterface $memberSettingRepository)
    {
        parent::__construct();
        $this->memberSettingRepository = $memberSettingRepository;
        
        // index()メソッドが呼ばれる前にloadViewParams()を自動実行
        $this->middleware(function ($request, $next) {
            if ($request->route()->getActionMethod() === 'index') {
                $this->loadViewParams();
            }
            return $next($request);
        });
    }

    /**
     * メンバー設定概要画面
     */
    public function index()
    {
        return view('admin.members.settings.index', $this->viewParams);
    }

    /**
     * 全メンバー強制ログアウト
     */
    public function forceLogoutAll()
    {
        $currentUserId = Auth::guard('member')->id();
        $sessionTable = config('session.table', 'sessions');
        
        if ($sessionTable && DB::getSchemaBuilder()->hasTable($sessionTable)) {
            $deletedCount = DB::table($sessionTable)
                ->where('user_id', '!=', $currentUserId)
                ->whereNotNull('user_id')
                ->delete();
            
            return redirect()->route('admin.members.settings')
                ->with('success', __('admin/members/force_logout_all_success', ['count' => $deletedCount]));
        }

        return redirect()->route('admin.members.settings')
            ->with('error', __('admin/members/force_logout_all_error'));
    }

    /**
     * ビューパラメータを読み込む
     */
    protected function loadViewParams(): void
    {
        $passwordMinLength = (int) $this->memberSettingRepository->get('password_min_length', 8);
        $passwordRequireUppercase = (bool) $this->memberSettingRepository->get('password_require_uppercase', true);
        $passwordRequireLowercase = (bool) $this->memberSettingRepository->get('password_require_lowercase', true);
        $passwordRequireNumber = (bool) $this->memberSettingRepository->get('password_require_number', true);
        $passwordRequireSymbol = (bool) $this->memberSettingRepository->get('password_require_symbol', true);
        
        $minLengthOptions = collect(__('admin/members/settings/password.min_length_options'))
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
        $this->viewParams['passwordRequireLowercase'] = $passwordRequireLowercase;
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
        $captchaPasswordResetEnabled = (bool) $this->memberSettingRepository->get('captcha_password_reset_enabled', false);
        $captchaAvailable = $captchaEnabled && $captchaAuthenticationResult;
        $this->viewParams['captchaEnabled'] = $captchaEnabled;
        $this->viewParams['captchaAuthenticationResult'] = $captchaAuthenticationResult;
        $this->viewParams['captchaAvailable'] = $captchaAvailable;
        $this->viewParams['captchaAdminLoginEnabled'] = $captchaAdminLoginEnabled;
        $this->viewParams['captchaPasswordResetEnabled'] = $captchaPasswordResetEnabled;
    }

    protected function isMailServerTested(): bool
    {
        $connectionTested = (bool) BaseSetting::getValue('mail_connection_tested', false);
        $sendTested = (bool) BaseSetting::getValue('mail_send_tested', false);
        $receiveTested = (bool) BaseSetting::getValue('mail_receive_tested', false);
        
        return $connectionTested && $sendTested && $receiveTested;
    }
}
