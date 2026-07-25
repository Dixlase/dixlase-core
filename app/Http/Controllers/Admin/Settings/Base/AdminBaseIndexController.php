<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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
     * Basic settings overview page
     */
    public function index()
    {
        // Site settings
        $appName = ConfigHelper::getAppName();
        $siteDescription = $this->baseSettingRepository->get('site_description', '');
        $locale = ConfigHelper::getAppLocale();
        $timezone = ConfigHelper::getDisplayTimezone();

        // Admin panel settings
        $adminUrl = $this->baseSettingRepository->get('admin_url', config('admin.admin_url'));
        $forceSsl = (bool) $this->baseSettingRepository->get('force_ssl', false);

        // Email settings
        $mailMailer = ConfigHelper::getMailMailer();
        $sessionTestResults = session('mail_test_results', []);
        $mailConnectionTested = (bool) ($sessionTestResults['mail_connection_tested'] ?? $this->baseSettingRepository->get('mail_connection_tested', false));
        $mailSendTested = (bool) ($sessionTestResults['mail_send_tested'] ?? $this->baseSettingRepository->get('mail_send_tested', false));
        $mailReceiveTested = (bool) ($sessionTestResults['mail_receive_tested'] ?? $this->baseSettingRepository->get('mail_receive_tested', false));
        $mailTestComplete = $mailConnectionTested && $mailSendTested && $mailReceiveTested;

        // Maintenance settings
        $maintenanceMode = ConfigHelper::getMaintenanceMode();

        // Mode settings
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

        // Content editor settings
        $preferredEditor = $this->editorManager->getPreferredEditor('gui');
        $this->viewParams['preferredEditorName'] = $preferredEditor?->label ?? '';

        // Determine whether to display subpages (hidden subpages do not show cards)
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
