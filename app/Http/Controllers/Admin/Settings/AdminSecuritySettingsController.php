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

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\SecuritySetting;
use App\Models\BaseSetting;
use App\Models\CaptchaFormSetting;
use App\Http\Requests\Admin\Settings\Security\AdminSettngsSecurityUpdateRequest;
use App\Services\CaptchaTestService;
use App\Enums\LogLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Helpers\EnvHelper;
use App\Helpers\ConfigHelper;
use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\Enums\ExtensionSecurityLevel;
use App\Enums\ExtensionSecurityPreset;



class AdminSecuritySettingsController extends AdminLoggedInController
{
    /**
     * セキュリティ設定リポジトリ
     */
    protected SecuritySettingRepositoryInterface $securitySettingRepository;

    /**
     * コンストラクタ
     */
    public function __construct(SecuritySettingRepositoryInterface $securitySettingRepository)
    {
        parent::__construct();
        $this->securitySettingRepository = $securitySettingRepository;
    }

    public function index()
    {
        // DEBUG: Log current CAPTCHA settings
        if (config('app.debug')) {
            $rawCaptchaEnabled = $this->securitySettingRepository->get('captcha_enabled', false);
            $convertedCaptchaEnabled = filter_var($rawCaptchaEnabled, FILTER_VALIDATE_BOOLEAN);
            Log::info('AdminSecuritySettingsController::index() - CAPTCHA Settings Debug', [
                'captcha_enabled_raw' => $rawCaptchaEnabled,
                'captcha_enabled_converted' => $convertedCaptchaEnabled,
                'captcha_driver' => $this->securitySettingRepository->get('captcha_driver', 'google'),
            ]);
        }

        $settings = [
            'enable_allowed_admin_ips' => filter_var($this->securitySettingRepository->get('enable_allowed_admin_ips', false), FILTER_VALIDATE_BOOLEAN),
            'allowed_admin_ips' => $this->securitySettingRepository->get('allowed_admin_ips', ''),
            'enable_blocked_admin_ips' => filter_var($this->securitySettingRepository->get('enable_blocked_admin_ips', false), FILTER_VALIDATE_BOOLEAN),
            'blocked_admin_ips' => $this->securitySettingRepository->get('blocked_admin_ips', ''),
            'enable_allowed_front_ips' => filter_var($this->securitySettingRepository->get('enable_allowed_front_ips', false), FILTER_VALIDATE_BOOLEAN),
            'allowed_front_ips' => $this->securitySettingRepository->get('allowed_front_ips', ''),
            'enable_blocked_front_ips' => filter_var($this->securitySettingRepository->get('enable_blocked_front_ips', false), FILTER_VALIDATE_BOOLEAN),
            'blocked_front_ips' => $this->securitySettingRepository->get('blocked_front_ips', ''),
            // Session settings - use base values for security settings form
            'session_driver' => ConfigHelper::getSessionDriver(),
            'session_encrypt' => ConfigHelper::getSessionEncrypt(),
            'session_lifetime' => ConfigHelper::getSessionLifetime(),
            // reCAPTCHA settings
            'captcha_enabled' => filter_var($this->securitySettingRepository->get('captcha_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'captcha_driver' => $this->securitySettingRepository->get('captcha_driver', 'google'),
            'captcha_site_key' => $this->securitySettingRepository->get('captcha_site_key', ''),
            'captcha_secret_key' => $this->securitySettingRepository->get('captcha_secret_key', ''),
            'captcha_google_version' => $this->securitySettingRepository->get('captcha_google_version', 'v3'),
            'captcha_google_min_score' => $this->securitySettingRepository->get('captcha_google_min_score', '0.5'),
            'captcha_google_project_id' => $this->securitySettingRepository->get('captcha_google_project_id', ''),
            // Notification settings
            'notification_enabled' => filter_var($this->securitySettingRepository->get('notification_enabled', true), FILTER_VALIDATE_BOOLEAN),
            'notification_log_levels' => array_map('intval', array_filter(explode(',', $this->securitySettingRepository->get('notification_log_levels', implode(',', LogLevel::getDefaultNotificationLevels()))))),
            // Password security settings
            'pwned_password_check_enabled' => filter_var($this->securitySettingRepository->get('pwned_password_check_enabled', false), FILTER_VALIDATE_BOOLEAN),
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

        // 動的reCAPTCHAフォーム設定を取得
        $captchaFormSettings = CaptchaFormSetting::getOrderedForms();

        // メールテスト状態を取得（DB優先、セッションは一時的な状態のみ）
        $sessionTestResults = session('mail_test_results', []);
        
        $mailConnectionTested = (bool) ($sessionTestResults['mail_connection_tested'] ?? BaseSetting::getValue('mail_connection_tested', false));
        $mailSendTested = (bool) ($sessionTestResults['mail_send_tested'] ?? BaseSetting::getValue('mail_send_tested', false));
        $mailReceiveTested = (bool) ($sessionTestResults['mail_receive_tested'] ?? BaseSetting::getValue('mail_receive_tested', false));

        // CAPTCHAテスト結果を取得（初回読み込み時はセッションクリア、バリデーションエラー時は保持）
        $captchaTestService = new CaptchaTestService();
        
        // バリデーションエラーがない場合はセッションをクリアしてDBから読み込み
        if (!session()->has('errors') || !session('errors')->any()) {
            session()->forget('captcha_authentication_result');
        }
        
        $captchaTestResult = $captchaTestService->getTestResult();
        
        $this->viewParams['settings'] = $settings;
        $this->viewParams['captchaFormSettings'] = $captchaFormSettings;
        $this->viewParams['captchaTestResult'] = $captchaTestResult;
        $this->viewParams['mailConnectionTested'] = $mailConnectionTested;
        $this->viewParams['mailSendTested'] = $mailSendTested;
        $this->viewParams['mailReceiveTested'] = $mailReceiveTested;

        return view('admin.settings.security.index', $this->viewParams);
    }

    public function update(AdminSettngsSecurityUpdateRequest $request)
    {
        $captchaTestService = new CaptchaTestService();
        
        // 現在のCAPTCHA設定を取得
        $currentCaptchaEnabled = $this->securitySettingRepository->get('captcha_enabled', false);
        $currentCaptchaDriver = $this->securitySettingRepository->get('captcha_driver', 'google');
        $currentCaptchaSettings = [
            'captcha_site_key' => $this->securitySettingRepository->get('captcha_site_key', ''),
            'captcha_secret_key' => $this->securitySettingRepository->get('captcha_secret_key', ''),
            'captcha_google_version' => $this->securitySettingRepository->get('captcha_google_version', 'v3'),
            'captcha_google_min_score' => $this->securitySettingRepository->get('captcha_google_min_score', '0.5'),
            'captcha_google_site_key' => $this->securitySettingRepository->get('captcha_google_site_key', ''),
            'captcha_google_secret_key' => $this->securitySettingRepository->get('captcha_google_secret_key', ''),
            'captcha_google_enterprise_site_key' => $this->securitySettingRepository->get('captcha_google_enterprise_site_key', ''),
            'captcha_google_enterprise_secret_key' => $this->securitySettingRepository->get('captcha_google_enterprise_secret_key', ''),
            'captcha_google_project_id' => $this->securitySettingRepository->get('captcha_google_project_id', ''),
            'captcha_turnstile_site_key' => $this->securitySettingRepository->get('captcha_turnstile_site_key', ''),
            'captcha_turnstile_secret_key' => $this->securitySettingRepository->get('captcha_turnstile_secret_key', ''),
        ];
        
        // 新しいCAPTCHA設定
        $newCaptchaEnabled = $request->boolean('captcha_enabled');
        $newCaptchaDriver = $request->input('captcha_driver', 'google');
        $newCaptchaSettings = [
            'captcha_site_key' => $request->input('captcha_site_key', ''),
            'captcha_secret_key' => $request->input('captcha_secret_key', ''),
            'captcha_google_version' => $request->input('captcha_google_version', 'v3'),
            'captcha_google_min_score' => $request->input('captcha_google_min_score', '0.5'),
            'captcha_google_site_key' => $request->input('captcha_google_site_key', ''),
            'captcha_google_secret_key' => $request->input('captcha_google_secret_key', ''),
            'captcha_google_enterprise_site_key' => $request->input('captcha_google_enterprise_site_key', ''),
            'captcha_google_enterprise_secret_key' => $request->input('captcha_google_enterprise_secret_key', ''),
            'captcha_google_project_id' => $request->input('captcha_google_project_id', ''),
            'captcha_turnstile_site_key' => $request->input('captcha_turnstile_site_key', ''),
            'captcha_turnstile_secret_key' => $request->input('captcha_turnstile_secret_key', ''),
        ];
        
        // CAPTCHA設定が変更されたかチェック
        $captchaSettingsChanged = (
            $currentCaptchaEnabled !== $newCaptchaEnabled ||
            $currentCaptchaDriver !== $newCaptchaDriver ||
            $currentCaptchaSettings !== $newCaptchaSettings
        );
        
        // CAPTCHA設定が有効で、テストが必要な場合はチェック
        if ($newCaptchaEnabled) {
            $captchaSettings = [
                'captcha_enabled' => true,
                'captcha_driver' => $newCaptchaDriver,
            ] + $newCaptchaSettings;
            
            // 旧CAPTCHA接続テストロジックを削除
            // 新しいライブ認証システムではフォームバリデーションで処理
        }
        
        // フォームから送信されたテスト結果を確認
        $submittedTestResult = $request->boolean('captcha_authentication_result');
        
        // テスト結果をデータベースに保存（フォームから送信された値を使用）
        if ($submittedTestResult) {
            $captchaTestService->saveCaptchaTestResult('form_submission', true);
        }
        
        // CAPTCHA設定が変更された場合のテスト結果リセット処理
        // ただし、フォームでテスト成功状態が送信された場合は保持
        if ($captchaSettingsChanged && !$submittedTestResult) {
            $captchaTestService->resetCaptchaTestResults();
        }

        $this->securitySettingRepository->set('enable_allowed_admin_ips', $request->boolean('enable_allowed_admin_ips'));
        $this->securitySettingRepository->set('allowed_admin_ips', $request->input('allowed_admin_ips'));
        $this->securitySettingRepository->set('enable_blocked_admin_ips', $request->boolean('enable_blocked_admin_ips'));
        $this->securitySettingRepository->set('blocked_admin_ips', $request->input('blocked_admin_ips'));
        $this->securitySettingRepository->set('enable_allowed_front_ips', $request->boolean('enable_allowed_front_ips'));
        $this->securitySettingRepository->set('allowed_front_ips', $request->input('allowed_front_ips'));
        $this->securitySettingRepository->set('enable_blocked_front_ips', $request->boolean('enable_blocked_front_ips'));
        $this->securitySettingRepository->set('blocked_front_ips', $request->input('blocked_front_ips'));
        
        // Save session settings
        $sessionDriver = $request->input('session_driver', 'file');
        $sessionEncrypt = $request->boolean('session_encrypt');
        $sessionLifetime = $request->integer('session_lifetime', 120);
        
        $this->securitySettingRepository->set('session_driver', $sessionDriver);
        $this->securitySettingRepository->set('session_encrypt', $sessionEncrypt);
        $this->securitySettingRepository->set('session_lifetime', $sessionLifetime);
        
        // Update .env file with session settings
        EnvHelper::update([
            'session_driver' => $sessionDriver,
            'session_encrypt' => $sessionEncrypt ? 'true' : 'false',
            'session_lifetime' => (string) $sessionLifetime,
        ]);
        
        // Save reCAPTCHA settings
        $this->securitySettingRepository->set('captcha_enabled', $request->boolean('captcha_enabled'));
        $this->securitySettingRepository->set('captcha_driver', $request->input('captcha_driver', 'google'));
        
        // Save common keys directly
        $this->securitySettingRepository->set('captcha_site_key', $request->input('captcha_site_key', ''));
        $this->securitySettingRepository->set('captcha_secret_key', $request->input('captcha_secret_key', ''));
        
        $this->securitySettingRepository->set('captcha_google_version', $request->input('captcha_google_version', 'v3'));
        // Debug: Check if min_score is in POST data
        \Log::info('CAPTCHA Min Score Debug', [
            'all_post_data' => $request->all(),
            'has_min_score_field' => $request->has('captcha_google_min_score'),
            'min_score_value' => $request->input('captcha_google_min_score'),
            'captcha_driver' => $request->input('captcha_driver'),
            'captcha_version' => $request->input('captcha_google_version'),
            'request_method' => $request->method(),
            'content_type' => $request->header('Content-Type')
        ]);
        
        // Handle min_score for Google reCAPTCHA v3 and Enterprise
        $driver = $request->input('captcha_driver');
        $version = $request->input('captcha_google_version');
        
        if (($driver === 'google' && $version === 'v3') || $driver === 'google_enterprise') {
            $minScore = $request->input('captcha_google_min_score', '0.5');
            \Log::info('Min Score Processing', [
                'condition_met' => true,
                'driver' => $driver,
                'version' => $version,
                'min_score_value' => $minScore,
                'before_save' => $this->securitySettingRepository->get('captcha_google_min_score')
            ]);
            $this->securitySettingRepository->set('captcha_google_min_score', $minScore);
            \Log::info('Min Score After Save', [
                'saved_value' => $this->securitySettingRepository->get('captcha_google_min_score')
            ]);
        } else {
            \Log::info('Min Score Processing', [
                'condition_met' => false,
                'driver' => $driver,
                'version' => $version,
                'reason' => 'Not Google v3 or Enterprise'
            ]);
        }
        
        // Save Google reCAPTCHA Enterprise project ID
        $this->securitySettingRepository->set('captcha_google_project_id', $request->input('captcha_google_project_id', ''));
        
        // Save notification settings
        $notificationEnabled = $request->boolean('notification_enabled');
        $this->securitySettingRepository->set('notification_enabled', $notificationEnabled);

        // Save password security settings
        $this->securitySettingRepository->set('pwned_password_check_enabled', $request->boolean('pwned_password_check_enabled'));

        // Save extension security settings
        $extensionPreset = $request->input('extension_security_preset', ExtensionSecurityPreset::Balanced->value);
        $this->securitySettingRepository->set('extension_security_preset', $extensionPreset);
        
        // プリセットがカスタム以外の場合は、プリセットのデフォルト値を適用
        if ($extensionPreset !== ExtensionSecurityPreset::Custom->value) {
            $preset = ExtensionSecurityPreset::tryFrom($extensionPreset) ?? ExtensionSecurityPreset::Balanced;
            $presetSettings = $preset->getDefaultSettings();
            
            $this->securitySettingRepository->set('extension_require_signature', $presetSettings['require_signature']);
            $this->securitySettingRepository->set('extension_require_permission_definition', $presetSettings['require_permission_definition']);
            $this->securitySettingRepository->set('extension_allow_undefined_permissions', $presetSettings['allow_undefined_permissions']);
            $this->securitySettingRepository->set('extension_plugin_max_health_level', $presetSettings['plugin_max_health_level']);
            $this->securitySettingRepository->set('extension_theme_max_health_level', $presetSettings['theme_max_health_level']);
            $this->securitySettingRepository->set('extension_allow_logic_themes', $presetSettings['allow_logic_themes']);
        } else {
            // カスタムモードの場合はフォームから送信された値を使用
            $this->securitySettingRepository->set('extension_require_signature', $request->boolean('extension_require_signature'));
            $this->securitySettingRepository->set('extension_require_permission_definition', $request->boolean('extension_require_permission_definition'));
            $this->securitySettingRepository->set('extension_allow_undefined_permissions', $request->boolean('extension_allow_undefined_permissions'));
            $this->securitySettingRepository->set('extension_plugin_max_health_level', $request->integer('extension_plugin_max_health_level', ExtensionSecurityLevel::Warning->value));
            $this->securitySettingRepository->set('extension_theme_max_health_level', $request->integer('extension_theme_max_health_level', ExtensionSecurityLevel::NeedsAttention->value));
            $this->securitySettingRepository->set('extension_allow_logic_themes', $request->boolean('extension_allow_logic_themes'));
        }
        
        // 権限不一致時の動作は常にフォームから取得
        $this->securitySettingRepository->set('extension_permission_mismatch_action', $request->input('extension_permission_mismatch_action', 'warn'));

        // Save extension notification settings
        $this->securitySettingRepository->set('extension_notify_on_install', $request->boolean('extension_notify_on_install'));
        $this->securitySettingRepository->set('extension_notify_on_uninstall', $request->boolean('extension_notify_on_uninstall'));
        $this->securitySettingRepository->set('extension_notify_on_enable', $request->boolean('extension_notify_on_enable'));
        $this->securitySettingRepository->set('extension_notify_on_disable', $request->boolean('extension_notify_on_disable'));
        $this->securitySettingRepository->set('extension_notify_on_unhealthy', $request->boolean('extension_notify_on_unhealthy'));
        $this->securitySettingRepository->set('extension_log_operations', $request->boolean('extension_log_operations'));

        // ログレベルは通知の有効/無効に関わらず保存できるようにする
        $submittedLevels = $request->input('notification_log_levels', null);

        if (is_array($submittedLevels)) {
            // チェックされた値のみを取得し、0を除外してLogLevel enumの値のみを保存
            $validLogLevels = array_filter(
                array_map('intval', $submittedLevels),
                function ($value) {
                    return $value > 0 && in_array($value, \App\Enums\LogLevel::getNotificationLevels());
                }
            );

            // 何も選択されていない（または不正）場合はデフォルトレベルを適用
            if (empty($validLogLevels)) {
                $validLogLevels = \App\Enums\LogLevel::getDefaultNotificationLevels();
            }

            $this->securitySettingRepository->set('notification_log_levels', implode(',', $validLogLevels));
        }
        
        // Save dynamic captcha form settings
        $captchaFormSettings = CaptchaFormSetting::all();
        foreach ($captchaFormSettings as $formSetting) {
            $inputKey = 'captcha_form_' . $formSetting->key;
            $formSetting->enabled = $request->boolean($inputKey);
            $formSetting->save();
        }


        // 保存成功後は認証状態をセッションに一時保存（次回ページ読み込み時用）
        if ($request->boolean('captcha_enabled') && $request->boolean('captcha_validation_status')) {
            session()->flash('captcha_just_saved', true);
        }
        
        return redirect()->route('admin.settings.security')
            ->with('success', __('admin.settings.security.controller_messages.settings_updated'));
    }

    /**
     * CAPTCHAテストを実行
     */
    public function testCaptcha(Request $request)
    {
        $captchaTestService = new CaptchaTestService();
        
        $settings = [
            'captcha_driver' => $request->input('captcha_driver', 'google'),
            'captcha_google_site_key' => $request->input('captcha_google_site_key', ''),
            'captcha_google_secret_key' => $request->input('captcha_google_secret_key', ''),
            'captcha_google_version' => $request->input('captcha_google_version', 'v3'),
            'captcha_google_enterprise_site_key' => $request->input('captcha_google_enterprise_site_key', ''),
            'captcha_google_enterprise_secret_key' => $request->input('captcha_google_enterprise_secret_key', ''),
            'captcha_google_project_id' => $request->input('captcha_google_project_id', ''),
            'captcha_turnstile_site_key' => $request->input('captcha_turnstile_site_key', ''),
            'captcha_turnstile_secret_key' => $request->input('captcha_turnstile_secret_key', ''),
        ];
        
        $result = $captchaTestService->testCaptchaConnection($settings);
        
        // テスト結果をデータベースに保存
        $driver = $settings['captcha_driver'];
        $captchaTestService->saveCaptchaTestResult(
            $driver, 
            $result['success'], 
            $result['success'] ? null : $result['message']
        );
        
        // 旧セッション保存処理を削除（新ライブ認証システムでは不要）
        
        return response()->json($result);
    }

    /**
     * CAPTCHAウィジェットの実証検証
     */
    public function validateCaptchaWidget(Request $request)
    {
        $driver = $request->input('captcha_driver', 'google');
        $token = $request->input('g-recaptcha-response', '');
        
        if (empty($token)) {
            return response()->json([
                'success' => false,
                'message' => 'CAPTCHA token is missing'
            ]);
        }
        
        // 実際のCAPTCHA検証を実行
        if ($driver === 'google') {
            $secretKey = $request->input('captcha_secret_key', '');
            $version = $request->input('captcha_google_version', 'v3');
            
            if (empty($secretKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Secret key is missing'
                ]);
            }
            
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secretKey,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                \Log::info('Google reCAPTCHA API Response', [
                    'success' => $data['success'] ?? false,
                    'score' => $data['score'] ?? 'not_provided',
                    'error_codes' => $data['error-codes'] ?? [],
                    'challenge_ts' => $data['challenge_ts'] ?? 'not_provided',
                    'hostname' => $data['hostname'] ?? 'not_provided',
                    'version' => $version,
                    'request_ip' => $request->ip()
                ]);
                
                // localhost/開発環境での警告
                if (($data['hostname'] ?? '') === 'localhost' && ($data['score'] ?? 0) === 0.0) {
                    \Log::warning('reCAPTCHA v3 returning score 0 for localhost - this is expected behavior. Please add your domain to Google reCAPTCHA console for production.');
                }
                
                if ($data['success'] ?? false) {
                    // v3の場合はスコアもチェック
                    if ($version === 'v3') {
                        $score = $data['score'] ?? 0;
                        $minScore = floatval($request->input('captcha_google_min_score', 0.5));
                        
                        \Log::info('reCAPTCHA v3 Score Check', [
                            'score' => $score,
                            'min_score' => $minScore,
                            'min_score_input' => $request->input('captcha_google_min_score'),
                            'passed' => $score >= $minScore
                        ]);
                        
                        if ($score >= $minScore) {
                            // テスト成功時はセッションのみに保存（DBには保存しない）
                            session(['captcha_authentication_result' => true]);
                            
                            return response()->json([
                                'success' => true,
                                'message' => __('admin.settings.security.captcha_validation_success_with_score', ['score' => $score])
                            ]);
                        } else {
                            // localhost環境での特別なメッセージ
                            if (($data['hostname'] ?? '') === 'localhost' && $score === 0.0) {
                                return response()->json([
                                    'success' => false,
                                    'message' => 'CAPTCHAスコアが低すぎます: ' . $score . ' (最小値: ' . $minScore . '). 開発環境(localhost)では正常なスコアが取得できません。Google reCAPTCHA管理コンソールで本番ドメインを設定してください。'
                                ]);
                            }
                            
                            return response()->json([
                                'success' => false,
                                'message' => __('admin.settings.security.captcha_validation_score_too_low', ['score' => $score, 'min_score' => $minScore])
                            ]);
                        }
                    } else {
                        // v2の場合もテスト成功時はセッションのみに保存（DBには保存しない）
                        session(['captcha_test_result' => true]);
                        
                        return response()->json([
                            'success' => true,
                            'message' => __('admin.settings.security.captcha_validation_success') . '. ' . __('admin.settings.security.captcha_test_validation_description')
                        ]);
                    }
                } else {
                    $errorCodes = $data['error-codes'] ?? [];
                    return response()->json([
                        'success' => false,
                        'message' => __('admin.settings.security.captcha_validation_failed_with_errors', ['errors' => implode(', ', $errorCodes)])
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('admin.settings.security.captcha_api_connection_failed')
                ]);
            }
        } elseif ($driver === 'google_enterprise') {
            $apiKey = $request->input('captcha_secret_key', '');
            $projectId = $request->input('captcha_google_project_id', '');
            
            if (empty($apiKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'API key is missing'
                ]);
            }
            
            if (empty($projectId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project ID is missing'
                ]);
            }
            
            // Google reCAPTCHA Enterprise API呼び出し
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://recaptchaenterprise.googleapis.com/v1/projects/{$projectId}/assessments?key={$apiKey}", [
                'event' => [
                    'token' => $token,
                    'siteKey' => $request->input('captcha_site_key', ''),
                ]
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                \Log::info('Google reCAPTCHA Enterprise API Response', [
                    'token_valid' => $data['tokenProperties']['valid'] ?? false,
                    'score' => $data['riskAnalysis']['score'] ?? 'not_provided',
                    'reasons' => $data['riskAnalysis']['reasons'] ?? [],
                    'hostname' => $data['tokenProperties']['hostname'] ?? 'not_provided',
                    'project_id' => $projectId,
                    'request_ip' => $request->ip()
                ]);
                
                if ($data['tokenProperties']['valid'] ?? false) {
                    $score = $data['riskAnalysis']['score'] ?? 0;
                    $minScore = floatval($request->input('captcha_google_min_score', 0.5));
                    
                    \Log::info('reCAPTCHA Enterprise Score Check', [
                        'score' => $score,
                        'min_score' => $minScore,
                        'passed' => $score >= $minScore
                    ]);
                    
                    if ($score >= $minScore) {
                        // テスト成功時はセッションのみに保存（DBには保存しない）
                        session(['captcha_authentication_result' => true]);
                        
                        return response()->json([
                            'success' => true,
                            'message' => __('admin.settings.security.captcha_validation_success_with_score', ['score' => $score])
                        ]);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => __('admin.settings.security.captcha_validation_score_too_low', ['score' => $score, 'min_score' => $minScore])
                        ]);
                    }
                } else {
                    $reasons = $data['riskAnalysis']['reasons'] ?? [];
                    return response()->json([
                        'success' => false,
                        'message' => 'reCAPTCHA Enterprise token validation failed: ' . implode(', ', $reasons)
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('admin.settings.security.captcha_api_connection_failed')
                ]);
            }
        } elseif ($driver === 'turnstile') {
            $secretKey = $request->input('captcha_secret_key', '');
            
            if (empty($secretKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Secret key is missing'
                ]);
            }
            
            $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secretKey,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                \Log::info('Cloudflare Turnstile API Response', [
                    'success' => $data['success'] ?? false,
                    'error_codes' => $data['error-codes'] ?? [],
                    'challenge_ts' => $data['challenge_ts'] ?? 'not_provided',
                    'hostname' => $data['hostname'] ?? 'not_provided',
                    'request_ip' => $request->ip()
                ]);
                
                if ($data['success'] ?? false) {
                    // テスト成功時はセッションのみに保存（DBには保存しない）
                    session(['captcha_authentication_result' => true]);
                    
                    return response()->json([
                        'success' => true,
                        'message' => __('admin.settings.security.captcha_validation_success') . '. ' . __('admin.settings.security.captcha_test_validation_description')
                    ]);
                } else {
                    $errorCodes = $data['error-codes'] ?? [];
                    return response()->json([
                        'success' => false,
                        'message' => __('admin.settings.security.captcha_validation_failed_with_errors', ['errors' => implode(', ', $errorCodes)])
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('admin.settings.security.captcha_api_connection_failed')
                ]);
            }
        }
        
        return response()->json([
            'success' => false,
            'message' => __('admin.settings.security.captcha_driver_unsupported')
        ]);
    }

    /**
     * CAPTCHAテスト結果をリセット
     */
    public function resetCaptchaTest(Request $request)
    {
        $captchaTestService = new CaptchaTestService();
        
        // テスト結果をfalseにリセット（レコードは保持）
        $captchaTestService->resetCaptchaTestResults();
        // 旧セッション処理を削除（新ライブ認証システムでは不要）
        
        return response()->json(['success' => true]);
    }

    /**
     * CAPTCHAテスト結果をクリア（プロバイダー切り替え時用）
     */
    public function clearCaptchaTest(Request $request)
    {
        $driver = $request->input('driver');
        
        if (!$driver) {
            return response()->json(['success' => false, 'message' => 'Driver not specified'], 400);
        }
        
        try {
            // 旧セッション処理を削除（新ライブ認証システムでは不要）
            
            Log::info("CAPTCHAテスト結果をクリアしました", [
                'driver' => $driver,
                'admin_id' => Auth::id()
            ]);
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            Log::error("CAPTCHAテスト結果のクリアに失敗しました", [
                'driver' => $driver,
                'error' => $e->getMessage(),
                'admin_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false, 
                'message' => 'テスト結果のクリアに失敗しました'
            ], 500);
        }
    }
}
