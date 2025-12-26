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
use App\Enums\CspMode;
use App\Enums\CspBlocklistAction;
use Illuminate\Http\Request;

class AdminSecurityCspController extends AdminLoggedInController
{
    protected SecuritySettingRepositoryInterface $securitySettingRepository;

    public function __construct(SecuritySettingRepositoryInterface $securitySettingRepository)
    {
        parent::__construct();
        $this->securitySettingRepository = $securitySettingRepository;
    }

    /**
     * CSP設定ページ
     */
    public function index()
    {
        $settings = [
            'csp_enabled' => filter_var($this->securitySettingRepository->get('csp_enabled', true), FILTER_VALIDATE_BOOLEAN),
            'csp_mode' => $this->securitySettingRepository->get('csp_mode', CspMode::default()->toString()),
            'csp_log_violations' => filter_var($this->securitySettingRepository->get('csp_log_violations', true), FILTER_VALIDATE_BOOLEAN),
            'csp_exclude_dev_tools' => filter_var($this->securitySettingRepository->get('csp_exclude_dev_tools', true), FILTER_VALIDATE_BOOLEAN),
            'csp_trusted_domains' => $this->securitySettingRepository->get('csp_trusted_domains', ''),
            'csp_denied_domains' => $this->securitySettingRepository->get('csp_denied_domains', ''),
            'csp_custom_directives' => $this->securitySettingRepository->get('csp_custom_directives', ''),
            'csp_blocklist_check_enabled' => filter_var($this->securitySettingRepository->get('csp_blocklist_check_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'csp_blocklist_action' => $this->securitySettingRepository->get('csp_blocklist_action', CspBlocklistAction::default()->toString()),
            'csp_blocklist_enabled_categories' => $this->securitySettingRepository->get('csp_blocklist_enabled_categories', ''),
        ];

        $this->viewParams['settings'] = $settings;

        return view('admin.settings.security.csp', $this->viewParams);
    }

    /**
     * CSP設定の更新
     */
    public function update(Request $request)
    {
        // CSP有効時のみモードを必須にする
        $cspEnabled = filter_var($request->input('csp_enabled'), FILTER_VALIDATE_BOOLEAN);
        
        $rules = [
            'csp_enabled' => 'boolean',
            'csp_mode' => $cspEnabled ? 'required|' . CspMode::validationRule() : 'nullable|' . CspMode::validationRule(),
            'csp_log_violations' => 'boolean',
            'csp_exclude_dev_tools' => 'boolean',
            'csp_trusted_domains' => 'nullable|string',
            'csp_denied_domains' => 'nullable|string',
            'csp_custom_directives' => 'nullable|string',
            'csp_blocklist_check_enabled' => 'boolean',
            'csp_blocklist_action' => $cspEnabled ? 'required|' . CspBlocklistAction::validationRule() : 'nullable|' . CspBlocklistAction::validationRule(),
            'csp_blocklist_categories' => 'nullable|array',
            'csp_blocklist_categories.*' => 'string',
        ];
        
        $validated = $request->validate($rules);

        // CSP設定を更新
        $this->securitySettingRepository->set('csp_enabled', $validated['csp_enabled'] ?? false);
        $this->securitySettingRepository->set('csp_mode', $validated['csp_mode'] ?? CspMode::default()->toString());
        $this->securitySettingRepository->set('csp_log_violations', $validated['csp_log_violations'] ?? true);
        $this->securitySettingRepository->set('csp_exclude_dev_tools', $validated['csp_exclude_dev_tools'] ?? true);
        $this->securitySettingRepository->set('csp_trusted_domains', $validated['csp_trusted_domains'] ?? '');
        $this->securitySettingRepository->set('csp_denied_domains', $validated['csp_denied_domains'] ?? '');
        $this->securitySettingRepository->set('csp_custom_directives', $validated['csp_custom_directives'] ?? '');
        $this->securitySettingRepository->set('csp_blocklist_check_enabled', $validated['csp_blocklist_check_enabled'] ?? false);
        $this->securitySettingRepository->set('csp_blocklist_action', $validated['csp_blocklist_action'] ?? CspBlocklistAction::default()->toString());
        
        // カテゴリ配列をカンマ区切り文字列に変換
        $categories = $validated['csp_blocklist_categories'] ?? [];
        $this->securitySettingRepository->set('csp_blocklist_enabled_categories', implode(',', $categories));

        return redirect()->route('admin.settings.security.csp')
            ->with('success', __('admin/settings/security/csp_settings_updated'));
    }
}
