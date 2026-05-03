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

namespace App\Http\Controllers\Admin\Settings\Base;

use App\Contracts\Repositories\SiteSettingRepositoryInterface;
use App\Enums\AdminMode;
use App\Enums\MenuVisibility;
use App\Helpers\AdminModeHelper;
use App\Helpers\ConfigHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Services\Editor\EditorManager;

class AdminBaseIndexController extends AdminLoggedInController
{
    protected SiteSettingRepositoryInterface $baseSettingRepository;

    protected EditorManager $editorManager;

    public function __construct(
        SiteSettingRepositoryInterface $baseSettingRepository,
        EditorManager $editorManager,
    ) {
        parent::__construct();
        $this->baseSettingRepository = $baseSettingRepository;
        $this->editorManager = $editorManager;
    }

    /**
     * 基本設定概要ページ
     */
    public function index()
    {
        // サイト設定
        $appName = ConfigHelper::getAppName();
        $siteDescription = $this->baseSettingRepository->get('site_description', '');
        $locale = ConfigHelper::getAppLocale();
        $timezone = ConfigHelper::getDisplayTimezone();

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

        // モード設定
        $adminMode = AdminMode::fromInt(
            (int) $this->baseSettingRepository->get('admin_mode', AdminMode::Simple->value)
        );

        $this->viewParams['appName'] = $appName;
        $this->viewParams['siteDescription'] = $siteDescription;
        $this->viewParams['locale'] = $locale;
        $this->viewParams['timezone'] = $timezone;
        $this->viewParams['adminUrl'] = $adminUrl;
        $this->viewParams['forceSsl'] = $forceSsl;
        $this->viewParams['mailMailer'] = $mailMailer;
        $this->viewParams['mailTestComplete'] = $mailTestComplete;
        $this->viewParams['maintenanceMode'] = $maintenanceMode;
        $this->viewParams['adminMode'] = $adminMode;

        // コンテンツエディター設定
        $preferredEditor = $this->editorManager->getPreferredEditor('gui');
        $this->viewParams['preferredEditorName'] = $preferredEditor?->label ?? '';

        // サブページの表示可否を判定（Hiddenのサブページはカード非表示）
        $subPageKeys = ['site', 'admin', 'mail', 'maintenance', 'mode', 'editor'];
        $subPageVisible = [];
        foreach ($subPageKeys as $key) {
            $visibility = AdminModeHelper::getMenuVisibility("settings.base.{$key}");
            $subPageVisible[$key] = $visibility !== MenuVisibility::Hidden;
        }
        $this->viewParams['subPageVisible'] = $subPageVisible;

        return view('admin.settings.base.index', $this->viewParams);
    }
}
