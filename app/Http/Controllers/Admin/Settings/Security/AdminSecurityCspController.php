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
use App\Enums\CspBlocklistAction;
use App\Enums\CspMode;
use App\Helpers\AdminModeHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Security\AdminSecurityCspUpdateRequest;
use Illuminate\Http\Request;

class AdminSecurityCspController extends AdminLoggedInController
{
    protected const SETTING_KEYS = ['csp_enabled', 'csp_mode', 'csp_log_violations', 'csp_exclude_dev_tools', 'csp_trusted_domains', 'csp_denied_domains', 'csp_custom_directives', 'csp_custom_directives_mode', 'csp_blocklist_check_enabled', 'csp_blocklist_action', 'csp_blocklist_enabled_categories'];

    protected SecuritySettingRepositoryInterface $securitySettingRepository;

    public function __construct(SecuritySettingRepositoryInterface $securitySettingRepository)
    {
        parent::__construct();
        $this->securitySettingRepository = $securitySettingRepository;
    }

    /**
     * CSP settings page
     */
    public function index()
    {
        $settings = [
            'csp_enabled' => filter_var($this->securitySettingRepository->get('csp_enabled', true), FILTER_VALIDATE_BOOLEAN),
            'csp_mode' => $this->securitySettingRepository->get('csp_mode', (string) CspMode::default()->value),
            'csp_log_violations' => filter_var($this->securitySettingRepository->get('csp_log_violations', true), FILTER_VALIDATE_BOOLEAN),
            'csp_exclude_dev_tools' => filter_var($this->securitySettingRepository->get('csp_exclude_dev_tools', true), FILTER_VALIDATE_BOOLEAN),
            'csp_trusted_domains' => $this->securitySettingRepository->get('csp_trusted_domains', ''),
            'csp_denied_domains' => $this->securitySettingRepository->get('csp_denied_domains', ''),
            'csp_custom_directives' => $this->securitySettingRepository->get('csp_custom_directives', ''),
            'csp_custom_directives_mode' => $this->securitySettingRepository->get('csp_custom_directives_mode', 'form'),
            'csp_blocklist_check_enabled' => filter_var($this->securitySettingRepository->get('csp_blocklist_check_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'csp_blocklist_action' => $this->securitySettingRepository->get('csp_blocklist_action', (string) CspBlocklistAction::default()->value),
            'csp_blocklist_enabled_categories' => $this->securitySettingRepository->get('csp_blocklist_enabled_categories', ''),
        ];

        // Parse custom directive JSON for form
        $directiveFields = $this->parseDirectivesForForm($settings['csp_custom_directives']);

        $this->viewParams['settings'] = $settings;
        $this->viewParams['directiveFields'] = $directiveFields;
        $this->viewParams['cspModeOptions'] = CspMode::getRadioCardOptions();
        $this->viewParams['cspModeDefaultValue'] = (string) CspMode::default()->value;
        $this->viewParams['cspBlocklistActionDefaultValue'] = (string) CspBlocklistAction::default()->value;
        $this->viewParams['enabledCategories'] = explode(',', old('csp_blocklist_enabled_categories', $settings['csp_blocklist_enabled_categories'] ?? ''));
        $this->viewParams['blocklistSources'] = config('csp.reporting.blocklist_sources', []);
        $this->viewParams['categoryIcons'] = [
            'tracking' => 'fas fa-ad',
            'malware' => 'fas fa-virus',
            'phishing' => 'fas fa-fish',
            'cryptominer' => 'fas fa-coins',
        ];
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.security.csp');
        $this->viewParams['configTrustedDomains'] = config('csp.domains.trusted_domains', []);

        return view('admin.settings.security.csp', $this->viewParams);
    }

    /**
     * Update CSP settings
     */
    public function update(AdminSecurityCspUpdateRequest $request)
    {
        $actor = new \App\Actors\MemberActor(\App\Helpers\AdminHelper::getMember());
        (new \App\Actions\Security\UpdateCspSettingsAction($this->securitySettingRepository))
            ->execute($actor, $request->validated());

        return redirect()->route('admin.settings.security.csp')
            ->with('success', __('admin/settings/security/csp.settings_updated'))
            ->with('show_csp_confirmation', true);
    }

    /**
     * Confirm CSP settings change
     */
    public function confirm(Request $request)
    {
        // Save confirmed flag to session
        session()->forget('csp_pending_confirmation');
        session()->forget('csp_previous_settings');
        session()->forget('csp_confirmation_expires_at');

        return response()->json([
            'success' => true,
            'message' => __('admin/settings/security/csp.settings_confirmed'),
        ]);
    }

    /**
     * Rollback CSP settings change
     */
    public function rollback(Request $request)
    {
        if (! session('csp_previous_settings')) {
            return response()->json([
                'success' => false,
                'message' => __('admin/settings/security/csp.no_previous_settings'),
            ], 400);
        }

        $actor = new \App\Actors\MemberActor(\App\Helpers\AdminHelper::getMember());
        $result = (new \App\Actions\Security\RollbackCspSettingsAction($this->securitySettingRepository))
            ->execute($actor, []);

        if (! $result->success) {
            return response()->json([
                'success' => false,
                'message' => $result->message,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => __('admin/settings/security/csp.settings_rolled_back'),
        ]);
    }

    /**
     * Parse custom directive JSON string into form fields
     *
     * @return array<string, string>
     */
    private function parseDirectivesForForm(string $json): array
    {
        $directives = [
            'script-src' => '',
            'style-src' => '',
            'img-src' => '',
            'connect-src' => '',
            'font-src' => '',
            'frame-src' => '',
        ];

        if (empty($json)) {
            return $directives;
        }

        $parsed = json_decode($json, true);
        if (! is_array($parsed)) {
            return $directives;
        }

        foreach ($directives as $key => $value) {
            if (isset($parsed[$key]) && is_array($parsed[$key])) {
                $directives[$key] = implode("\n", $parsed[$key]);
            }
        }

        return $directives;
    }
}
