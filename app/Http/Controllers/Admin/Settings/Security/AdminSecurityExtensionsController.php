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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

namespace App\Http\Controllers\Admin\Settings\Security;

use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\Enums\ExtensionSecurityLevel;
use App\Enums\ExtensionSecurityPreset;
use App\Enums\SecurityAction;
use App\Helpers\AdminModeHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Security\AdminSecurityExtensionsUpdateRequest;
use App\Models\BaseSetting;
use App\Models\ExtensionSource;
use App\Services\Extension\ExtensionSourceManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSecurityExtensionsController extends AdminLoggedInController
{
    protected const SETTING_KEYS = [
        'extension_security_preset',
        'extension_require_signature',
        'extension_require_permission_definition',
        'extension_allow_undefined_permissions',
        'extension_plugin_max_health_level',
        'extension_theme_max_health_level',
        'extension_allow_logic_themes',
        'extension_permission_mismatch_action',
        'extension_notify_on_install',
        'extension_notify_on_uninstall',
        'extension_notify_on_enable',
        'extension_notify_on_disable',
        'extension_notify_on_unhealthy',
        'extension_log_operations',
        'extension_source_type',
        'extension_update_check_interval',
    ];

    public function __construct(
        protected SecuritySettingRepositoryInterface $securitySettingRepository,
    ) {
        parent::__construct();
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
            'extension_permission_mismatch_action' => $this->securitySettingRepository->get('extension_permission_mismatch_action', SecurityAction::default()->toString()),
            // Extension notification settings
            'extension_notify_on_install' => filter_var($this->securitySettingRepository->get('extension_notify_on_install', true), FILTER_VALIDATE_BOOLEAN),
            'extension_notify_on_uninstall' => filter_var($this->securitySettingRepository->get('extension_notify_on_uninstall', true), FILTER_VALIDATE_BOOLEAN),
            'extension_notify_on_enable' => filter_var($this->securitySettingRepository->get('extension_notify_on_enable', true), FILTER_VALIDATE_BOOLEAN),
            'extension_notify_on_disable' => filter_var($this->securitySettingRepository->get('extension_notify_on_disable', false), FILTER_VALIDATE_BOOLEAN),
            'extension_notify_on_unhealthy' => filter_var($this->securitySettingRepository->get('extension_notify_on_unhealthy', true), FILTER_VALIDATE_BOOLEAN),
            'extension_log_operations' => filter_var($this->securitySettingRepository->get('extension_log_operations', true), FILTER_VALIDATE_BOOLEAN),
            // Extension source settings
            'extension_source_type' => $this->securitySettingRepository->get('extension_source_type', 'github'),
            'extension_update_check_interval' => (int) $this->securitySettingRepository->get('extension_update_check_interval', config('extension-sources.check_interval', 86400)),
        ];

        // メールテスト状態を取得
        $sessionTestResults = session('mail_test_results', []);
        $mailConnectionTested = (bool) ($sessionTestResults['mail_connection_tested'] ?? BaseSetting::getValue('mail_connection_tested', false));
        $mailSendTested = (bool) ($sessionTestResults['mail_send_tested'] ?? BaseSetting::getValue('mail_send_tested', false));
        $mailReceiveTested = (bool) ($sessionTestResults['mail_receive_tested'] ?? BaseSetting::getValue('mail_receive_tested', false));

        // ソースプリセット情報を構築
        $sourcePresets = collect(config('extension-sources.presets', []))->map(fn (array $preset, string $type) => [
            'value' => $type,
            'label' => $preset['name'],
            'icon' => $preset['icon'] ?? 'fas fa-globe',
            'is_official' => $preset['is_official'] ?? false,
        ])->values()->all();

        // チェック間隔オプション（key => translationKey 形式）
        $checkIntervalOptions = config('extension-sources.check_intervals', []);

        $this->viewParams['settings'] = $settings;
        $this->viewParams['mailConnectionTested'] = $mailConnectionTested;
        $this->viewParams['mailSendTested'] = $mailSendTested;
        $this->viewParams['mailReceiveTested'] = $mailReceiveTested;
        $this->viewParams['presetOptions'] = ExtensionSecurityPreset::getRadioCardOptions();
        $this->viewParams['securityLevelRangeLabels'] = ExtensionSecurityLevel::getRangeLabels();
        $this->viewParams['securityLevelRangeLabelColors'] = ExtensionSecurityLevel::getRangeLabelColors();
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.security.extensions');
        // 現在のソースレコードからトークン設定状態を取得
        $currentSource = ExtensionSource::query()
            ->ofType($settings['extension_source_type'])
            ->enabled()
            ->first();
        $hasSourceToken = $currentSource && $currentSource->hasAuthentication();

        $this->viewParams['sourcePresets'] = $sourcePresets;
        $this->viewParams['checkIntervalOptions'] = $checkIntervalOptions;
        $this->viewParams['sourceReferenceUrl'] = 'https://github.com/'.config('extension-sources.github.default_owner', 'Dixlase');
        $this->viewParams['hasSourceToken'] = $hasSourceToken;
        // 署名検証用 Authority の URL を表示用に渡す（透明性のため常時表示）
        $this->viewParams['authorityUrl'] = config('dixlase-authority.url');

        return view('admin.settings.security.extensions', $this->viewParams);
    }

    /**
     * 拡張機能セキュリティ設定の更新
     */
    public function update(AdminSecurityExtensionsUpdateRequest $request)
    {
        $actor = new \App\Actors\MemberActor(\App\Helpers\AdminHelper::getMember());

        \App\Actions\Settings\UpdateSettingsAction::make(
            repository: $this->securitySettingRepository,
            settingsPage: 'security.extensions',
            settingKeys: static::SETTING_KEYS,
            writeCallback: function ($repo, $data) {
                foreach (static::SETTING_KEYS as $key) {
                    if (array_key_exists($key, $data)) {
                        $repo->set($key, $data[$key]);
                    }
                }
            },
            permission: \App\Enums\Permission::SETTINGS_SECURITY,
        )->execute($actor, $request->validated());

        // ソースのトークンを DB に保存（入力がある場合のみ更新）
        $sourceToken = $request->input('extension_source_token');
        if ($sourceToken !== null && $sourceToken !== '') {
            $sourceType = $request->validated()['extension_source_type'] ?? 'github';
            $source = ExtensionSource::query()->ofType($sourceType)->enabled()->first();
            if ($source) {
                $source->update(['auth_token' => $sourceToken]);
            }
        }

        return redirect()->route('admin.settings.security.extensions')
            ->with('success', __('admin/settings/security/extensions.settings_updated'));
    }

    /**
     * ソース接続テスト API
     */
    public function testSource(Request $request, ExtensionSourceManager $manager): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|max:50',
            'token' => 'nullable|string|max:500',
        ]);

        $type = $request->input('type');
        $inputToken = $request->input('token');

        // DB のソースレコードを取得（あれば DB のトークンを使う）
        $dbSource = ExtensionSource::query()->ofType($type)->enabled()->first();

        // トークン優先順位: フォーム入力 → DB → config/.env
        $token = $inputToken ?: ($dbSource?->auth_token ?? config('extension-sources.github.default_token'));

        $baseUrl = match ($type) {
            'github' => config('extension-sources.github.api_base', 'https://api.github.com'),
            default => '',
        };

        // 一時的な ExtensionSource インスタンスを作成（DB 保存しない）
        $source = new ExtensionSource([
            'name' => 'Connection Test',
            'type' => $type,
            'base_url' => $baseUrl,
            'owner' => config('extension-sources.github.default_owner', 'Dixlase'),
            'auth_token' => $token,
        ]);

        try {
            $provider = $manager->makeProvider($source);
            $result = $provider->checkConnection();

            // 公式判定を付与
            $preset = config("extension-sources.presets.{$type}");
            $result['is_official'] = $preset && ($preset['is_official'] ?? false);

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'details' => [],
            ]);
        }
    }
}
