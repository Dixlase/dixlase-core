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

namespace App\Http\Controllers\Admin\Settings\Base;

use App\Helpers\ConfigHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\BaseSetting;
use App\Contracts\Repositories\BaseSettingRepositoryInterface;

class AdminBaseIndexController extends AdminLoggedInController
{
    protected BaseSettingRepositoryInterface $baseSettingRepository;

    public function __construct(BaseSettingRepositoryInterface $baseSettingRepository)
    {
        parent::__construct();
        $this->baseSettingRepository = $baseSettingRepository;
    }

    /**
     * 基本設定概要ページ
     */
    public function index()
    {
        $this->addBreadcrumb(null, __('admin/nav.settings.text'));
        $this->addBreadcrumb('admin.settings.base.index', __('admin/nav.settings.base.text'));
        $this->setDescription(__('admin/settings/base/index.description'));
        $this->setBreadcrumbs();
        
        // サイト設定
        $appName = ConfigHelper::getAppName();
        $siteDescription = $this->baseSettingRepository->get('site_description', '');
        $locale = ConfigHelper::getAppLocale();
        $timezone = ConfigHelper::getAppTimezone();

        // 管理画面設定
        $adminUrl = $this->baseSettingRepository->get('admin_url', config('admin.admin_url'));
        $forceSsl = (bool) $this->baseSettingRepository->get('force_ssl', false);

        // メール設定
        $mailMailer = ConfigHelper::getMailMailer();
        $sessionTestResults = session('mail_test_results', []);
        $mailConnectionTested = (bool) ($sessionTestResults['mail_connection_tested'] ?? $this->baseSettingRepository->get('mail_connection_tested', false));
        $mailSendTested = (bool) ($sessionTestResults['mail_send_tested'] ?? $this->baseSettingRepository->get('mail_send_tested', false));
        $mailReceiveTested = (bool) ($sessionTestResults['mail_receive_tested'] ?? $this->baseSettingRepository->get('mail_receive_tested', false));
        $mailTestComplete = $mailConnectionTested && $mailSendTested && $mailReceiveTested;

        // メンテナンス設定
        $maintenanceMode = ConfigHelper::getMaintenanceMode();

        $this->viewParams['appName'] = $appName;
        $this->viewParams['siteDescription'] = $siteDescription;
        $this->viewParams['locale'] = $locale;
        $this->viewParams['timezone'] = $timezone;
        $this->viewParams['adminUrl'] = $adminUrl;
        $this->viewParams['forceSsl'] = $forceSsl;
        $this->viewParams['mailMailer'] = $mailMailer;
        $this->viewParams['mailTestComplete'] = $mailTestComplete;
        $this->viewParams['maintenanceMode'] = $maintenanceMode;

        return view('admin.settings.base.index', $this->viewParams);
    }
}
