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
use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\Enums\LogLevel;
use App\Models\BaseSetting;
use Illuminate\Http\Request;

class AdminSecurityNotificationsController extends AdminLoggedInController
{
    protected SecuritySettingRepositoryInterface $securitySettingRepository;

    public function __construct(SecuritySettingRepositoryInterface $securitySettingRepository)
    {
        parent::__construct();
        $this->securitySettingRepository = $securitySettingRepository;
    }

    /**
     * 通知設定ページ
     */
    public function index()
    {
        
        $settings = [
            'notification_enabled' => filter_var($this->securitySettingRepository->get('notification_enabled', true), FILTER_VALIDATE_BOOLEAN),
            'notification_log_levels' => array_map('intval', array_filter(explode(',', $this->securitySettingRepository->get('notification_log_levels', implode(',', LogLevel::getDefaultNotificationLevels()))))),
        ];

        // メールテスト状態を取得
        $sessionTestResults = session('mail_test_results', []);
        $mailConnectionTested = (bool) ($sessionTestResults['mail_connection_tested'] ?? BaseSetting::getValue('mail_connection_tested', false));
        $mailSendTested = (bool) ($sessionTestResults['mail_send_tested'] ?? BaseSetting::getValue('mail_send_tested', false));
        $mailReceiveTested = (bool) ($sessionTestResults['mail_receive_tested'] ?? BaseSetting::getValue('mail_receive_tested', false));

        $this->viewParams['settings'] = $settings;
        $this->viewParams['mailConnectionTested'] = $mailConnectionTested;
        $this->viewParams['mailSendTested'] = $mailSendTested;
        $this->viewParams['mailReceiveTested'] = $mailReceiveTested;

        return view('admin.settings.security.notifications', $this->viewParams);
    }

    /**
     * 通知設定の更新
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'notification_enabled' => 'boolean',
            'notification_log_levels' => 'nullable|array',
            'notification_log_levels.*' => 'integer|min:1|max:8',
        ]);

        // 通知設定を更新
        $this->securitySettingRepository->set('notification_enabled', $validated['notification_enabled'] ?? false);
        
        $logLevels = $validated['notification_log_levels'] ?? LogLevel::getDefaultNotificationLevels();
        $this->securitySettingRepository->set('notification_log_levels', implode(',', $logLevels));

        return redirect()->route('admin.settings.security.notifications')
            ->with('success', __('admin/settings/security/notifications.settings_updated'));
    }
}
