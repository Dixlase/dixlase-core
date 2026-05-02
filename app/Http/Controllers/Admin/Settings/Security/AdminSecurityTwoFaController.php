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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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
use App\Enums\AuthenticationMode;
use App\Helpers\AdminModeHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Security\AdminSecurityTwoFaUpdateRequest;
use App\Models\SiteSetting;

class AdminSecurityTwoFaController extends AdminLoggedInController
{
    protected const SETTING_KEYS = [
        'two_fa_mode',
        'two_fa_passkey_mode',
        'two_fa_passkey_max_devices',
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
     * 二段階認証設定画面
     */
    public function index()
    {
        // 二段階認証基本設定（セキュリティ設定から取得）
        $twoFaMode = (int) $this->securitySettingRepository->get('two_fa_mode', AuthenticationMode::Disabled->value);
        if (old('two_fa_mode') !== null) {
            $twoFaMode = (int) old('two_fa_mode');
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

        // パスキーモード設定（セキュリティ設定から取得）
        // 0=無効, 1=有効（デフォルト: 有効）
        $twoFaPasskeyMode = (int) $this->securitySettingRepository->get('two_fa_passkey_mode', '1');
        if (old('two_fa_passkey_mode') !== null) {
            $twoFaPasskeyMode = (int) old('two_fa_passkey_mode');
        }

        // パスキーデバイス最大登録数（セキュリティ設定から取得）
        $twoFaPasskeyMaxDevices = (int) $this->securitySettingRepository->get('two_fa_passkey_max_devices', '5');
        if (old('two_fa_passkey_max_devices') !== null) {
            $twoFaPasskeyMaxDevices = (int) old('two_fa_passkey_max_devices');
        }

        // 二段階認証詳細設定（セキュリティ設定から取得）
        $twoFaExpireMinutes = (int) $this->securitySettingRepository->get('two_fa_expire_minutes', '5');
        $twoFaResendIntervalSeconds = (int) $this->securitySettingRepository->get('two_fa_resend_interval_seconds', '60');
        $twoFaMaxAttempts = (int) $this->securitySettingRepository->get('two_fa_max_attempts', '5');
        $twoFaAttemptWindow = (int) $this->securitySettingRepository->get('two_fa_attempt_window', '15');
        $twoFaLockoutDuration = (int) $this->securitySettingRepository->get('two_fa_lockout_duration', '30');
        $twoFaLockoutNotificationEnabled = (bool) $this->securitySettingRepository->get('two_fa_lockout_notification_enabled', '1');
        $twoFaRecoveryCodesCount = (int) $this->securitySettingRepository->get('two_fa_recovery_codes_count', '10');
        $twoFaRecoveryCodeRegenerateInterval = (int) $this->securitySettingRepository->get('two_fa_recovery_code_regenerate_interval', '90');

        // メールサーバー設定状態
        $isMailServerTested = $this->isMailServerTested();
        $mailConnectionTestDate = SiteSetting::getValue('mail_connection_test_date');

        $this->viewParams['twoFaMode'] = $twoFaMode;
        $this->viewParams['twoFaEnabled'] = $twoFaMode !== AuthenticationMode::Disabled->value;
        $this->viewParams['twoFaGlobalOptions'] = $twoFactorGlobalOptions;
        $this->viewParams['twoFaPasskeyMode'] = $twoFaPasskeyMode;
        $this->viewParams['twoFaPasskeyMaxDevices'] = $twoFaPasskeyMaxDevices;
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
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.security.two-fa');

        return view('admin.settings.security.two-fa', $this->viewParams);
    }

    /**
     * 二段階認証設定更新
     */
    public function update(AdminSecurityTwoFaUpdateRequest $request)
    {
        $actor = new \App\Actors\MemberActor(\App\Helpers\AdminHelper::getMember());

        \App\Actions\Settings\UpdateSettingsAction::make(
            repository: $this->securitySettingRepository,
            settingsPage: 'security.two_fa',
            settingKeys: static::SETTING_KEYS,
            writeCallback: function ($repo, $data) {
                foreach (static::SETTING_KEYS as $key) {
                    if (array_key_exists($key, $data)) {
                        $value = $data[$key];
                        if ($key === 'two_fa_mode') {
                            $value = (int) $value;
                        } elseif ($key === 'two_fa_lockout_notification_enabled') {
                            $value = $value ? '1' : '0';
                        } else {
                            $value = (string) $value;
                        }
                        $repo->set($key, $value);
                    }
                }
            },
            permission: \App\Enums\Permission::SETTINGS_SECURITY,
        )->execute($actor, $request->validated());

        return redirect()->back()
            ->with('success', __('admin/settings/security/two-fa.updated'));
    }

    protected function isMailServerTested(): bool
    {
        $connectionTested = (bool) SiteSetting::getValue('mail_connection_tested', false);
        $sendTested = (bool) SiteSetting::getValue('mail_send_tested', false);
        $receiveTested = (bool) SiteSetting::getValue('mail_receive_tested', false);

        return $connectionTested && $sendTested && $receiveTested;
    }
}
