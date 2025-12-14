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
use App\Enums\ExtensionSecurityLevel;
use App\Enums\ExtensionSecurityPreset;
use App\Models\BaseSetting;
use Illuminate\Http\Request;

class AdminSecurityExtensionsController extends AdminLoggedInController
{
    protected SecuritySettingRepositoryInterface $securitySettingRepository;

    public function __construct(SecuritySettingRepositoryInterface $securitySettingRepository)
    {
        parent::__construct();
        $this->securitySettingRepository = $securitySettingRepository;
    }

    /**
     * 拡張機能セキュリティ設定ページ
     */
    public function index()
    {
        $settings = [
            // Extension security settings
            'extension_security_preset' => $this->securitySettingRepository->get('extension_security_preset', ExtensionSecurityPreset::Balanced->value),
            'extension_require_signature' => filter_var($this->securitySettingRepository->get('extension_require_signature', false), FILTER_VALIDATE_BOOLEAN),
            'extension_require_permission_definition' => filter_var($this->securitySettingRepository->get('extension_require_permission_definition', false), FILTER_VALIDATE_BOOLEAN),
            'extension_allow_undefined_permissions' => filter_var($this->securitySettingRepository->get('extension_allow_undefined_permissions', true), FILTER_VALIDATE_BOOLEAN),
            'extension_plugin_max_health_level' => (int) $this->securitySettingRepository->get('extension_plugin_max_health_level', ExtensionSecurityLevel::Warning->value),
            'extension_theme_max_health_level' => (int) $this->securitySettingRepository->get('extension_theme_max_health_level', ExtensionSecurityLevel::NeedsAttention->value),
            'extension_allow_logic_themes' => filter_var($this->securitySettingRepository->get('extension_allow_logic_themes', true), FILTER_VALIDATE_BOOLEAN),
            'extension_permission_mismatch_action' => $this->securitySettingRepository->get('extension_permission_mismatch_action', 'warn'),
            // Extension notification settings
            'extension_notify_on_install' => filter_var($this->securitySettingRepository->get('extension_notify_on_install', true), FILTER_VALIDATE_BOOLEAN),
            'extension_notify_on_uninstall' => filter_var($this->securitySettingRepository->get('extension_notify_on_uninstall', true), FILTER_VALIDATE_BOOLEAN),
            'extension_notify_on_enable' => filter_var($this->securitySettingRepository->get('extension_notify_on_enable', true), FILTER_VALIDATE_BOOLEAN),
            'extension_notify_on_disable' => filter_var($this->securitySettingRepository->get('extension_notify_on_disable', false), FILTER_VALIDATE_BOOLEAN),
            'extension_notify_on_unhealthy' => filter_var($this->securitySettingRepository->get('extension_notify_on_unhealthy', true), FILTER_VALIDATE_BOOLEAN),
            'extension_log_operations' => filter_var($this->securitySettingRepository->get('extension_log_operations', true), FILTER_VALIDATE_BOOLEAN),
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

        return view('admin.settings.security.extensions', $this->viewParams);
    }

    /**
     * 拡張機能セキュリティ設定の更新
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'extension_security_preset' => 'required|in:strict,balanced,development,custom',
            'extension_require_signature' => 'boolean',
            'extension_require_permission_definition' => 'boolean',
            'extension_allow_undefined_permissions' => 'boolean',
            'extension_plugin_max_health_level' => 'required|integer|min:0|max:3',
            'extension_theme_max_health_level' => 'required|integer|min:0|max:3',
            'extension_allow_logic_themes' => 'boolean',
            'extension_permission_mismatch_action' => 'required|in:warn,block',
            'extension_notify_on_install' => 'boolean',
            'extension_notify_on_uninstall' => 'boolean',
            'extension_notify_on_enable' => 'boolean',
            'extension_notify_on_disable' => 'boolean',
            'extension_notify_on_unhealthy' => 'boolean',
            'extension_log_operations' => 'boolean',
        ]);

        // 拡張機能セキュリティ設定を更新
        $this->securitySettingRepository->set('extension_security_preset', $validated['extension_security_preset']);
        $this->securitySettingRepository->set('extension_require_signature', $validated['extension_require_signature'] ?? false);
        $this->securitySettingRepository->set('extension_require_permission_definition', $validated['extension_require_permission_definition'] ?? false);
        $this->securitySettingRepository->set('extension_allow_undefined_permissions', $validated['extension_allow_undefined_permissions'] ?? true);
        $this->securitySettingRepository->set('extension_plugin_max_health_level', $validated['extension_plugin_max_health_level']);
        $this->securitySettingRepository->set('extension_theme_max_health_level', $validated['extension_theme_max_health_level']);
        $this->securitySettingRepository->set('extension_allow_logic_themes', $validated['extension_allow_logic_themes'] ?? true);
        $this->securitySettingRepository->set('extension_permission_mismatch_action', $validated['extension_permission_mismatch_action']);
        $this->securitySettingRepository->set('extension_notify_on_install', $validated['extension_notify_on_install'] ?? true);
        $this->securitySettingRepository->set('extension_notify_on_uninstall', $validated['extension_notify_on_uninstall'] ?? true);
        $this->securitySettingRepository->set('extension_notify_on_enable', $validated['extension_notify_on_enable'] ?? true);
        $this->securitySettingRepository->set('extension_notify_on_disable', $validated['extension_notify_on_disable'] ?? false);
        $this->securitySettingRepository->set('extension_notify_on_unhealthy', $validated['extension_notify_on_unhealthy'] ?? true);
        $this->securitySettingRepository->set('extension_log_operations', $validated['extension_log_operations'] ?? true);

        return redirect()->route('admin.settings.security.extensions')
            ->with('success', __('admin/settings/security/extensions_settings_updated'));
    }
}
