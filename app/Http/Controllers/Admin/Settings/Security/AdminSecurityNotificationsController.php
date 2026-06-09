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
use App\Enums\LogLevel;
use App\Helpers\AdminModeHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\SiteSetting;

class AdminSecurityNotificationsController extends AdminLoggedInController
{
    protected const SETTING_KEYS = [
        'notification_enabled',
        'notification_log_levels',
    ];

    protected SecuritySettingRepositoryInterface $securitySettingRepository;

    public function __construct(SecuritySettingRepositoryInterface $securitySettingRepository)
    {
        parent::__construct();
        $this->securitySettingRepository = $securitySettingRepository;
    }

    /**
     * Notification settings page
     */
    public function index()
    {
        $settings = [
            'notification_enabled' => filter_var($this->securitySettingRepository->get('notification_enabled', true), FILTER_VALIDATE_BOOLEAN),
            'notification_log_levels' => array_map('intval', array_filter(explode(',', $this->securitySettingRepository->get('notification_log_levels', implode(',', LogLevel::getDefaultNotificationLevels()))))),
        ];

        // Get email test status
        $sessionTestResults = session('mail_test_results', []);
        $mailConnectionTested = (bool) ($sessionTestResults['mail_connection_tested'] ?? SiteSetting::getValue('mail_connection_tested', false));
        $mailSendTested = (bool) ($sessionTestResults['mail_send_tested'] ?? SiteSetting::getValue('mail_send_tested', false));
        $mailReceiveTested = (bool) ($sessionTestResults['mail_receive_tested'] ?? SiteSetting::getValue('mail_receive_tested', false));

        // Check system administrator email address settings status
        $systemAdminEmail = SiteSetting::getValue('system_admin_email', '');
        $hasSystemAdminEmail = ! empty($systemAdminEmail);

        $defaultNotificationLevels = LogLevel::getDefaultNotificationLevels();

        $logLevelOptions = [];
        foreach (LogLevel::getNotificationLevels() as $level) {
            $levelString = LogLevel::from($level)->toString();
            $logLevelOptions[$level] = 'admin/settings/security/notifications.log_level_options.'.$levelString;
        }

        $this->viewParams['settings'] = $settings;
        $this->viewParams['defaultNotificationLevels'] = $defaultNotificationLevels;
        $this->viewParams['logLevelOptions'] = $logLevelOptions;
        $this->viewParams['mailConnectionTested'] = $mailConnectionTested;
        $this->viewParams['mailSendTested'] = $mailSendTested;
        $this->viewParams['mailReceiveTested'] = $mailReceiveTested;
        $this->viewParams['hasSystemAdminEmail'] = $hasSystemAdminEmail;
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.security.notifications');

        return view('admin.settings.security.notifications', $this->viewParams);
    }

    /**
     * Update notification settings
     */
    public function update(\App\Http\Requests\Admin\Settings\Security\AdminSecurityNotificationsUpdateRequest $request)
    {
        $actor = new \App\Actors\MemberActor(\App\Helpers\AdminHelper::getMember());

        \App\Actions\Settings\UpdateSettingsAction::make(
            repository: $this->securitySettingRepository,
            settingsPage: 'security.notifications',
            settingKeys: static::SETTING_KEYS,
            writeCallback: function ($repo, $data) {
                $repo->set('notification_enabled', $data['notification_enabled'] ?? false);

                $logLevels = $data['notification_log_levels'] ?? LogLevel::getDefaultNotificationLevels();
                $logLevels = array_values(array_unique($logLevels));
                $repo->set('notification_log_levels', implode(',', $logLevels));
            },
            permission: \App\Enums\Permission::SETTINGS_SECURITY,
        )->execute($actor, $request->validated());

        return redirect()->route('admin.settings.security.notifications')
            ->with('success', __('admin/settings/security/notifications.settings_updated'));
    }
}
