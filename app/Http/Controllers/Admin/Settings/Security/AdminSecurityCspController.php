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

use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\Enums\CspBlocklistAction;
use App\Enums\CspMode;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Security\AdminSecurityCspUpdateRequest;
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

        // カスタムディレクティブのJSONをフォーム用に分解
        $directiveFields = $this->parseDirectivesForForm($settings['csp_custom_directives']);

        $this->viewParams['settings'] = $settings;
        $this->viewParams['directiveFields'] = $directiveFields;
        $this->viewParams['cspModeOptions'] = CspMode::getRadioCardOptions();
        $this->viewParams['cspModeDefaultValue'] = (string) CspMode::default()->value;
        $this->viewParams['cspBlocklistActionDefaultValue'] = (string) CspBlocklistAction::default()->value;
        $this->viewParams['enabledCategories'] = explode(',', old('csp_blocklist_enabled_categories', $settings['csp_blocklist_enabled_categories'] ?? ''));
        $this->viewParams['blocklistSources'] = config('csp.blocklist_sources', []);
        $this->viewParams['categoryIcons'] = [
            'tracking' => 'fas fa-ad',
            'malware' => 'fas fa-virus',
            'phishing' => 'fas fa-fish',
            'cryptominer' => 'fas fa-coins',
        ];

        return view('admin.settings.security.csp', $this->viewParams);
    }

    /**
     * CSP設定の更新
     */
    public function update(AdminSecurityCspUpdateRequest $request)
    {
        $validated = $request->validated();

        // 現在の設定をセッションに退避（ロールバック用）
        $previousSettings = [
            'csp_enabled' => $this->securitySettingRepository->get('csp_enabled', true),
            'csp_mode' => $this->securitySettingRepository->get('csp_mode', (string) CspMode::default()->value),
            'csp_admin_mode' => $this->securitySettingRepository->get('csp_admin_mode'),
            'csp_log_violations' => $this->securitySettingRepository->get('csp_log_violations', true),
            'csp_exclude_dev_tools' => $this->securitySettingRepository->get('csp_exclude_dev_tools', true),
            'csp_trusted_domains' => $this->securitySettingRepository->get('csp_trusted_domains', ''),
            'csp_denied_domains' => $this->securitySettingRepository->get('csp_denied_domains', ''),
            'csp_custom_directives' => $this->securitySettingRepository->get('csp_custom_directives', ''),
            'csp_custom_directives_mode' => $this->securitySettingRepository->get('csp_custom_directives_mode', 'form'),
            'csp_blocklist_check_enabled' => $this->securitySettingRepository->get('csp_blocklist_check_enabled', false),
            'csp_blocklist_action' => $this->securitySettingRepository->get('csp_blocklist_action', (string) CspBlocklistAction::default()->value),
            'csp_blocklist_enabled_categories' => $this->securitySettingRepository->get('csp_blocklist_enabled_categories', ''),
        ];

        session(['csp_previous_settings' => $previousSettings]);
        session(['csp_pending_confirmation' => true]);
        session(['csp_confirmation_expires_at' => now()->addSeconds(10)]);

        // CSP設定を更新
        $this->securitySettingRepository->set('csp_enabled', $validated['csp_enabled'] ?? false);
        $this->securitySettingRepository->set('csp_mode', $validated['csp_mode'] ?? (string) CspMode::default()->value);
        $this->securitySettingRepository->set('csp_log_violations', $validated['csp_log_violations'] ?? true);
        $this->securitySettingRepository->set('csp_exclude_dev_tools', $validated['csp_exclude_dev_tools'] ?? true);
        $this->securitySettingRepository->set('csp_trusted_domains', $validated['csp_trusted_domains'] ?? '');
        $this->securitySettingRepository->set('csp_denied_domains', $validated['csp_denied_domains'] ?? '');

        // カスタムディレクティブの入力モードを保存
        $directivesMode = $validated['csp_custom_directives_mode'] ?? 'form';
        $this->securitySettingRepository->set('csp_custom_directives_mode', $directivesMode);

        // フォームベース入力時: 個別フィールドをJSONにマージして保存
        if ($directivesMode === 'form') {
            $customDirectives = $this->buildDirectivesFromForm($validated);
            $this->securitySettingRepository->set('csp_custom_directives', $customDirectives);
        } else {
            $this->securitySettingRepository->set('csp_custom_directives', $validated['csp_custom_directives'] ?? '');
        }

        $this->securitySettingRepository->set('csp_blocklist_check_enabled', $validated['csp_blocklist_check_enabled'] ?? false);
        $this->securitySettingRepository->set('csp_blocklist_action', $validated['csp_blocklist_action'] ?? (string) CspBlocklistAction::default()->value);

        // カテゴリ配列をカンマ区切り文字列に変換
        $categories = $validated['csp_blocklist_categories'] ?? [];
        $this->securitySettingRepository->set('csp_blocklist_enabled_categories', implode(',', $categories));

        return redirect()->route('admin.settings.security.csp')
            ->with('success', __('admin/settings/security/csp.settings_updated'))
            ->with('show_csp_confirmation', true);
    }

    /**
     * CSP設定変更の確認
     */
    public function confirm(Request $request)
    {
        // 確認済みフラグをセッションに保存
        session()->forget('csp_pending_confirmation');
        session()->forget('csp_previous_settings');
        session()->forget('csp_confirmation_expires_at');

        return response()->json([
            'success' => true,
            'message' => __('admin/settings/security/csp.settings_confirmed'),
        ]);
    }

    /**
     * CSP設定変更のロールバック
     */
    public function rollback(Request $request)
    {
        $previousSettings = session('csp_previous_settings');

        if (! $previousSettings) {
            return response()->json([
                'success' => false,
                'message' => __('admin/settings/security/csp.no_previous_settings'),
            ], 400);
        }

        // 前回の設定に戻す
        foreach ($previousSettings as $key => $value) {
            $this->securitySettingRepository->set($key, $value);
        }

        // セッションをクリア
        session()->forget('csp_pending_confirmation');
        session()->forget('csp_previous_settings');
        session()->forget('csp_confirmation_expires_at');

        return response()->json([
            'success' => true,
            'message' => __('admin/settings/security/csp.settings_rolled_back'),
        ]);
    }

    /**
     * カスタムディレクティブのJSON文字列をフォーム用フィールドに分解
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

    /**
     * フォーム入力から個別ディレクティブフィールドをJSON文字列に変換
     *
     * @param  array<string, mixed>  $validated
     */
    private function buildDirectivesFromForm(array $validated): string
    {
        $directiveKeys = [
            'script-src' => 'csp_directive_script_src',
            'style-src' => 'csp_directive_style_src',
            'img-src' => 'csp_directive_img_src',
            'connect-src' => 'csp_directive_connect_src',
            'font-src' => 'csp_directive_font_src',
            'frame-src' => 'csp_directive_frame_src',
        ];

        $result = [];
        foreach ($directiveKeys as $directive => $field) {
            $value = trim($validated[$field] ?? '');
            if ($value !== '') {
                $domains = array_filter(
                    array_map('trim', explode("\n", $value)),
                    fn (string $line): bool => $line !== ''
                );
                if (! empty($domains)) {
                    $result[$directive] = array_values($domains);
                }
            }
        }

        return ! empty($result) ? json_encode($result, JSON_UNESCAPED_SLASHES) : '';
    }
}
