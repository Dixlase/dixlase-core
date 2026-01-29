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

namespace App\Http\Controllers\Admin\Settings\Security;

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Security\AdminSecurityAuthenticationUpdateRequest;
use App\Models\BaseSetting;
use App\Models\SecuritySetting;
use App\Enums\AuthenticationMode;
use App\Contracts\Repositories\MemberSettingRepositoryInterface;

class AdminSecurityAuthenticationController extends AdminLoggedInController
{
    protected MemberSettingRepositoryInterface $memberSettingRepository;

    public function __construct(MemberSettingRepositoryInterface $memberSettingRepository)
    {
        parent::__construct();
        $this->memberSettingRepository = $memberSettingRepository;
    }

    /**
     * 認証設定画面
     */
    public function index()
    {
        // 二段階認証基本設定
        $twoFaForceMode = (int) $this->memberSettingRepository->get('two_fa_mode', AuthenticationMode::Disabled->value);
        if (old('two_fa_force_mode') !== null) {
            $twoFaForceMode = (int) old('two_fa_force_mode');
        }
        
        $twoFactorGlobalOptions = collect(config('admin.global_two_factor_mode'))
            ->map(function ($value) {
                $mode = AuthenticationMode::tryFrom($value);
                return [
                    'value' => (string) $value,
                    'label' => $mode ? $mode->twoFactorLabel() : '',
                ];
            })
            ->values()
            ->toArray();
        
        // パスキーモード設定
        $twoFaPasskeyMode = (int) $this->memberSettingRepository->get('two_fa_passkey_mode', '2');
        if (old('two_fa_passkey_mode') !== null) {
            $twoFaPasskeyMode = (int) old('two_fa_passkey_mode');
        }

        // デフォルトの二段階認証方法
        $twoFaDefaultMethod = (int) $this->memberSettingRepository->get('two_fa_default_method', '0');
        if (old('two_fa_default_method') !== null) {
            $twoFaDefaultMethod = (int) old('two_fa_default_method');
        }

        // 二段階認証詳細設定
        $twoFaExpireMinutes = (int) $this->memberSettingRepository->get('two_fa_expire_minutes', '10');
        $twoFaResendIntervalSeconds = (int) $this->memberSettingRepository->get('two_fa_resend_interval_seconds', '60');
        $twoFaMaxAttempts = (int) $this->memberSettingRepository->get('two_fa_max_attempts', '5');
        $twoFaAttemptWindow = (int) $this->memberSettingRepository->get('two_fa_attempt_window', '15');
        $twoFaLockoutDuration = (int) $this->memberSettingRepository->get('two_fa_lockout_duration', '30');
        $twoFaLockoutNotificationEnabled = (bool) $this->memberSettingRepository->get('two_fa_lockout_notification_enabled', '1');
        $twoFaRecoveryCodesCount = (int) $this->memberSettingRepository->get('two_fa_recovery_codes_count', '10');
        $twoFaRecoveryCodeRegenerateInterval = (int) $this->memberSettingRepository->get('two_fa_recovery_code_regenerate_interval', '90');

        // メールサーバー設定状態
        $isMailServerTested = $this->isMailServerTested();
        $mailConnectionTestDate = BaseSetting::getValue('mail_connection_test_date');

        $this->viewParams['twoFaForceMode'] = $twoFaForceMode;
        $this->viewParams['twoFaGlobalOptions'] = $twoFactorGlobalOptions;
        $this->viewParams['twoFaPasskeyMode'] = $twoFaPasskeyMode;
        $this->viewParams['twoFaDefaultMethod'] = $twoFaDefaultMethod;
        $this->viewParams['twoFaExpireMinutes'] = $twoFaExpireMinutes;
        $this->viewParams['twoFaResendIntervalSeconds'] = $twoFaResendIntervalSeconds;
        $this->viewParams['twoFaMaxAttempts'] = $twoFaMaxAttempts;
        $this->viewParams['twoFaAttemptWindow'] = $twoFaAttemptWindow;
        $this->viewParams['twoFaLockoutDuration'] = $twoFaLockoutDuration;
        $this->viewParams['twoFaLockoutNotificationEnabled'] = $twoFaLockoutNotificationEnabled;
        $this->viewParams['twoFaRecoveryCodesCount'] = $twoFaRecoveryCodesCount;
        $this->viewParams['twoFaRecoveryCodeRegenerateInterval'] = $twoFaRecoveryCodeRegenerateInterval;
        $this->viewParams['isMailServerTested'] = $isMailServerTested;
        $this->viewParams['mailConnectionTestDate'] = $mailConnectionTestDate;

        return view('admin.settings.security.authentication', $this->viewParams);
    }

    /**
     * 認証設定更新
     */
    public function update(AdminSecurityAuthenticationUpdateRequest $request)
    {
        $validated = $request->validated();

        // 二段階認証基本設定
        if (array_key_exists('two_fa_force_mode', $validated)) {
            $this->memberSettingRepository->set('two_fa_mode', (int) $validated['two_fa_force_mode']);
        }
        if (array_key_exists('two_fa_passkey_mode', $validated)) {
            $this->memberSettingRepository->set('two_fa_passkey_mode', (string) $validated['two_fa_passkey_mode']);
        }
        if (array_key_exists('two_fa_default_method', $validated)) {
            $this->memberSettingRepository->set('two_fa_default_method', (string) $validated['two_fa_default_method']);
        }

        // 二段階認証詳細設定
        if (array_key_exists('two_fa_expire_minutes', $validated)) {
            $this->memberSettingRepository->set('two_fa_expire_minutes', (string) $validated['two_fa_expire_minutes']);
        }
        if (array_key_exists('two_fa_resend_interval_seconds', $validated)) {
            $this->memberSettingRepository->set('two_fa_resend_interval_seconds', (string) $validated['two_fa_resend_interval_seconds']);
        }
        if (array_key_exists('two_fa_max_attempts', $validated)) {
            $this->memberSettingRepository->set('two_fa_max_attempts', (string) $validated['two_fa_max_attempts']);
        }
        if (array_key_exists('two_fa_attempt_window', $validated)) {
            $this->memberSettingRepository->set('two_fa_attempt_window', (string) $validated['two_fa_attempt_window']);
        }
        if (array_key_exists('two_fa_lockout_duration', $validated)) {
            $this->memberSettingRepository->set('two_fa_lockout_duration', (string) $validated['two_fa_lockout_duration']);
        }
        if (array_key_exists('two_fa_lockout_notification_enabled', $validated)) {
            $this->memberSettingRepository->set('two_fa_lockout_notification_enabled', $validated['two_fa_lockout_notification_enabled'] ? '1' : '0');
        }
        if (array_key_exists('two_fa_recovery_codes_count', $validated)) {
            $this->memberSettingRepository->set('two_fa_recovery_codes_count', (string) $validated['two_fa_recovery_codes_count']);
        }
        if (array_key_exists('two_fa_recovery_code_regenerate_interval', $validated)) {
            $this->memberSettingRepository->set('two_fa_recovery_code_regenerate_interval', (string) $validated['two_fa_recovery_code_regenerate_interval']);
        }

        return redirect()->back()
            ->with('success', __('admin/settings/security/authentication.updated'));
    }

    protected function isMailServerTested(): bool
    {
        $connectionTested = (bool) BaseSetting::getValue('mail_connection_tested', false);
        $sendTested = (bool) BaseSetting::getValue('mail_send_tested', false);
        $receiveTested = (bool) BaseSetting::getValue('mail_receive_tested', false);
        
        return $connectionTested && $sendTested && $receiveTested;
    }
}
