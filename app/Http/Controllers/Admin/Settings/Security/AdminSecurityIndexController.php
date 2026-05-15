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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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
use App\Enums\CspMode;
use App\Enums\ExtensionSecurityPreset;
use App\Enums\MenuVisibility;
use App\Helpers\AdminModeHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\FileIntegrityAudit;
use App\Models\SiteSetting;
use App\Services\CaptchaTestService;
use App\Services\FileIntegrityService;

class AdminSecurityIndexController extends AdminLoggedInController
{
    protected SecuritySettingRepositoryInterface $securitySettingRepository;

    public function __construct(SecuritySettingRepositoryInterface $securitySettingRepository)
    {
        parent::__construct();
        $this->securitySettingRepository = $securitySettingRepository;
    }

    /**
     * Security settings overview page
     */
    public function index()
    {
        // Get email test status
        $sessionTestResults = session('mail_test_results', []);
        $mailConnectionTested = (bool) ($sessionTestResults['mail_connection_tested'] ?? SiteSetting::getValue('mail_connection_tested', false));
        $mailSendTested = (bool) ($sessionTestResults['mail_send_tested'] ?? SiteSetting::getValue('mail_send_tested', false));
        $mailReceiveTested = (bool) ($sessionTestResults['mail_receive_tested'] ?? SiteSetting::getValue('mail_receive_tested', false));
        $mailTestComplete = $mailConnectionTested && $mailSendTested && $mailReceiveTested;

        // Get CAPTCHA test status
        $captchaTestService = app(CaptchaTestService::class);
        $captchaEnabled = filter_var($this->securitySettingRepository->get('captcha_enabled', false), FILTER_VALIDATE_BOOLEAN);
        $captchaTestResult = $captchaTestService->getTestResult();

        // Get file integrity information
        $fileIntegrityService = app(FileIntegrityService::class);
        $latestIntegrityAudit = FileIntegrityAudit::getLatestCore();
        $hasBaseline = $fileIntegrityService->hasBaseline();

        // CSP settings status
        $cspEnabled = filter_var($this->securitySettingRepository->get('csp_enabled', true), FILTER_VALIDATE_BOOLEAN);
        $cspModeValue = $this->securitySettingRepository->get('csp_mode', (string) CspMode::default()->value);
        $cspModeEnum = CspMode::fromValue($cspModeValue) ?? CspMode::default();
        $cspMode = $cspModeEnum->label();

        // IP restriction status
        $enableAllowedAdminIps = filter_var($this->securitySettingRepository->get('enable_allowed_admin_ips', false), FILTER_VALIDATE_BOOLEAN);
        $enableBlockedAdminIps = filter_var($this->securitySettingRepository->get('enable_blocked_admin_ips', false), FILTER_VALIDATE_BOOLEAN);

        // Notification settings status
        $notificationEnabled = filter_var($this->securitySettingRepository->get('notification_enabled', true), FILTER_VALIDATE_BOOLEAN);

        // Session settings
        $sessionDriver = config('session.driver', 'file');

        // Environment settings
        $appEnv = config('app.env', 'local');
        $appDebug = config('app.debug', false);

        $this->viewParams['mailTestComplete'] = $mailTestComplete;
        $this->viewParams['captchaEnabled'] = $captchaEnabled;
        $this->viewParams['captchaTestResult'] = $captchaTestResult;
        $this->viewParams['latestIntegrityAudit'] = $latestIntegrityAudit;
        $this->viewParams['hasBaseline'] = $hasBaseline;
        $this->viewParams['cspEnabled'] = $cspEnabled;
        $this->viewParams['cspMode'] = $cspMode;
        $this->viewParams['enableAllowedAdminIps'] = $enableAllowedAdminIps;
        $this->viewParams['enableBlockedAdminIps'] = $enableBlockedAdminIps;
        $this->viewParams['notificationEnabled'] = $notificationEnabled;
        $this->viewParams['sessionDriver'] = $sessionDriver;
        $this->viewParams['appEnv'] = $appEnv;
        $this->viewParams['appDebug'] = $appDebug;
        $this->viewParams['integrityStatusOk'] = FileIntegrityAudit::STATUS_OK;
        $this->viewParams['integrityStatusWarning'] = FileIntegrityAudit::STATUS_WARNING;
        $this->viewParams['envColors'] = [
            'local' => 'text-blue-600 dark:text-blue-400',
            'staging' => 'text-yellow-600 dark:text-yellow-400',
            'production' => 'text-green-600 dark:text-green-400',
        ];
        $this->viewParams['envIcons'] = [
            'local' => 'fa-laptop-code',
            'staging' => 'fa-flask',
            'production' => 'fa-server',
        ];
        // Determine whether to display subpages (hidden subpages show summary card)
        $subPageKeys = ['password', 'login', 'two-fa', 'captcha', 'session', 'notifications', 'csp', 'extensions', 'ip', 'integrity', 'environment'];
        $subPageVisible = [];
        foreach ($subPageKeys as $key) {
            $visibility = AdminModeHelper::getMenuVisibility("settings.security.{$key}");
            $subPageVisible[$key] = $visibility !== MenuVisibility::Hidden;
        }
        $this->viewParams['subPageVisible'] = $subPageVisible;

        // Extension preset label (used in overview card)
        $extensionPresetValue = $this->securitySettingRepository->get('extension_security_preset', ExtensionSecurityPreset::Balanced->value);
        $extensionPreset = ExtensionSecurityPreset::tryFrom($extensionPresetValue) ?? ExtensionSecurityPreset::Balanced;
        $this->viewParams['extensionPresetLabel'] = $extensionPreset->label();

        return view('admin.settings.security.index', $this->viewParams);
    }
}
