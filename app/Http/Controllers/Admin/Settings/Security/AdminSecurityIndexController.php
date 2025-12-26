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
use App\Models\SecuritySetting;
use App\Models\BaseSetting;
use App\Models\FileIntegrityAudit;
use App\Services\FileIntegrityService;
use App\Services\CaptchaTestService;
use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\Enums\CspMode;
use Illuminate\Support\Facades\Log;

class AdminSecurityIndexController extends AdminLoggedInController
{
    protected SecuritySettingRepositoryInterface $securitySettingRepository;

    public function __construct(SecuritySettingRepositoryInterface $securitySettingRepository)
    {
        parent::__construct();
        $this->securitySettingRepository = $securitySettingRepository;
    }

    /**
     * セキュリティ設定概要ページ
     */
    public function index()
    {
        // メールテスト状態を取得
        $sessionTestResults = session('mail_test_results', []);
        $mailConnectionTested = (bool) ($sessionTestResults['mail_connection_tested'] ?? BaseSetting::getValue('mail_connection_tested', false));
        $mailSendTested = (bool) ($sessionTestResults['mail_send_tested'] ?? BaseSetting::getValue('mail_send_tested', false));
        $mailReceiveTested = (bool) ($sessionTestResults['mail_receive_tested'] ?? BaseSetting::getValue('mail_receive_tested', false));
        $mailTestComplete = $mailConnectionTested && $mailSendTested && $mailReceiveTested;

        // CAPTCHAテスト状態を取得
        $captchaTestService = app(CaptchaTestService::class);
        $captchaEnabled = filter_var($this->securitySettingRepository->get('captcha_enabled', false), FILTER_VALIDATE_BOOLEAN);
        $captchaTestResult = $captchaTestService->getTestResult();

        // ファイル整合性情報を取得
        $fileIntegrityService = app(FileIntegrityService::class);
        $latestIntegrityAudit = FileIntegrityAudit::getLatestCore();
        $hasBaseline = $fileIntegrityService->hasBaseline();

        // CSP設定状態
        $cspEnabled = filter_var($this->securitySettingRepository->get('csp_enabled', true), FILTER_VALIDATE_BOOLEAN);
        $cspModeValue = $this->securitySettingRepository->get('csp_mode', (string) CspMode::default()->value);
        $cspModeEnum = CspMode::fromValue($cspModeValue) ?? CspMode::default();
        $cspMode = $cspModeEnum->label();

        // IP制限状態
        $enableAllowedAdminIps = filter_var($this->securitySettingRepository->get('enable_allowed_admin_ips', false), FILTER_VALIDATE_BOOLEAN);
        $enableBlockedAdminIps = filter_var($this->securitySettingRepository->get('enable_blocked_admin_ips', false), FILTER_VALIDATE_BOOLEAN);

        // 通知設定状態
        $notificationEnabled = filter_var($this->securitySettingRepository->get('notification_enabled', true), FILTER_VALIDATE_BOOLEAN);

        // セッション設定
        $sessionDriver = config('session.driver', 'file');

        // 環境設定
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

        return view('admin.settings.security.index', $this->viewParams);
    }
}
