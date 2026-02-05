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

namespace App\Livewire\Admin\Settings\Security;

use App\Livewire\Admin\AdminLoggedInComponent;
use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\Enums\LogLevel;
use App\Models\BaseSetting;

class Notifications extends AdminLoggedInComponent
{
    public $notificationEnabled;
    public $notificationLogLevels = [];
    public $hasSystemAdminEmail;
    public $mailConnectionTested;
    public $mailSendTested;
    public $mailReceiveTested;
    public $showSaveConfirmation = false;

    protected function mountComponent()
    {
        \Log::info('[Livewire Debug] mountComponent() called');
        
        // ページ情報設定
        $this->heading = __('admin/settings/security/notifications.title');
        $this->description = __('admin/settings/security/notifications.description');

        // リポジトリを取得
        $securitySettingRepository = app(SecuritySettingRepositoryInterface::class);

        // 通知設定を取得
        $this->notificationEnabled = filter_var(
            $securitySettingRepository->get('notification_enabled', true), 
            FILTER_VALIDATE_BOOLEAN
        );
        
        \Log::info('[Livewire Debug] notificationEnabled set to: ' . ($this->notificationEnabled ? 'true' : 'false'));
        
        $this->notificationLogLevels = array_map(
            'intval', 
            array_filter(
                explode(',', $securitySettingRepository->get(
                    'notification_log_levels', 
                    implode(',', LogLevel::getDefaultNotificationLevels())
                ))
            )
        );
        
        // メールテスト状態を取得（セッション優先）
        $sessionTestResults = session('mail_test_results', []);
        $this->mailConnectionTested = (bool) ($sessionTestResults['mail_connection_tested'] ?? BaseSetting::getValue('mail_connection_tested', false));
        $this->mailSendTested = (bool) ($sessionTestResults['mail_send_tested'] ?? BaseSetting::getValue('mail_send_tested', false));
        $this->mailReceiveTested = (bool) ($sessionTestResults['mail_receive_tested'] ?? BaseSetting::getValue('mail_receive_tested', false));

        // システム管理者メールアドレスの設定状態を確認
        $systemAdminEmail = BaseSetting::getValue('system_admin_email', '');
        $this->hasSystemAdminEmail = !empty($systemAdminEmail);
    }

    public function save()
    {
        // リポジトリを取得
        $securitySettingRepository = app(SecuritySettingRepositoryInterface::class);

        // 通知設定を更新
        $securitySettingRepository->set('notification_enabled', $this->notificationEnabled ?? false);
        
        $logLevels = $this->notificationLogLevels ?? LogLevel::getDefaultNotificationLevels();
        $securitySettingRepository->set('notification_log_levels', implode(',', $logLevels));

        session()->flash('success', __('admin/settings/security/notifications.settings_updated'));
        
        return redirect()->route('admin.settings.security.notifications');
    }

    public function render()
    {
        return view('livewire.admin.settings.security.notifications', [
            'notificationEnabled' => $this->notificationEnabled,
            'notificationLogLevels' => $this->notificationLogLevels,
            'hasSystemAdminEmail' => $this->hasSystemAdminEmail,
            'mailConnectionTested' => $this->mailConnectionTested,
            'mailSendTested' => $this->mailSendTested,
            'mailReceiveTested' => $this->mailReceiveTested,
            'showSaveConfirmation' => $this->showSaveConfirmation,
        ])->layout('layouts.admin', $this->getLayoutData());
    }
}
