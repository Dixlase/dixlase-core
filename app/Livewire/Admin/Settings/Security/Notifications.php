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

use App\Livewire\Admin\Settings\SecuritySettingsComponent;
use App\Enums\LogLevel;
use App\Models\BaseSetting;

class Notifications extends SecuritySettingsComponent
{
    // 動的な値（ユーザー操作で変更される値）
    public $notificationEnabled;
    public $notificationLogLevels = [];
    public $showSaveConfirmation = false;
    
    // 静的な値（コントローラーから渡される値）
    public $initialNotificationEnabled;
    public $initialNotificationLogLevels;
    public $hasSystemAdminEmail;
    public $mailConnectionTested;
    public $mailSendTested;
    public $mailReceiveTested;

    /**
     * コンポーネント初期化
     * 静的な値（初期表示データ）をコントローラーから受け取る
     */
    protected function mountComponent()
    {
        // @livewire()で渡されたパラメータは既にパブリックプロパティに設定されている
        // 動的な値を初期化
        $this->notificationEnabled = $this->initialNotificationEnabled;
        $this->notificationLogLevels = $this->initialNotificationLogLevels;
        
        // デバッグ用ログ
        \Log::info('[Debug] Livewire mountComponent - hasSystemAdminEmail: ' . ($this->hasSystemAdminEmail ? 'true' : 'false'));
        \Log::info('[Debug] Livewire mountComponent - mailConnectionTested: ' . ($this->mailConnectionTested ? 'true' : 'false'));
        \Log::info('[Debug] Livewire mountComponent - mailSendTested: ' . ($this->mailSendTested ? 'true' : 'false'));
        \Log::info('[Debug] Livewire mountComponent - mailReceiveTested: ' . ($this->mailReceiveTested ? 'true' : 'false'));
    }

    public function save()
    {
        \Log::info('[Debug] save() called');
        \Log::info('[Debug] notificationEnabled: ' . ($this->notificationEnabled ? 'true' : 'false'));
        \Log::info('[Debug] notificationLogLevels: ' . json_encode($this->notificationLogLevels));
        
        // 通知設定を更新
        $this->setSecuritySettings([
            'notification_enabled' => $this->notificationEnabled ?? false,
            'notification_log_levels' => implode(',', $this->notificationLogLevels ?? LogLevel::getDefaultNotificationLevels()),
        ]);

        \Log::info('[Debug] Settings saved successfully');

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
        ]);
    }
}
