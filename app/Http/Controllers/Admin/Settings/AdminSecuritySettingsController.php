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



class AdminSecuritySettingsController extends AdminLoggedInController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        // DEBUG: Log current CAPTCHA settings
        if (config('app.debug')) {
            $rawCaptchaEnabled = SecuritySetting::get('captcha_enabled', false);
            $convertedCaptchaEnabled = filter_var($rawCaptchaEnabled, FILTER_VALIDATE_BOOLEAN);
            Log::info('AdminSecuritySettingsController::index() - CAPTCHA Settings Debug', [
                'captcha_enabled_raw' => $rawCaptchaEnabled,
                'captcha_enabled_converted' => $convertedCaptchaEnabled,
                'captcha_driver' => SecuritySetting::get('captcha_driver', 'google'),
            ]);
        }

        $settings = [
            'enable_allowed_admin_ips' => filter_var(SecuritySetting::get('enable_allowed_admin_ips', false), FILTER_VALIDATE_BOOLEAN),
            'allowed_admin_ips' => SecuritySetting::get('allowed_admin_ips', ''),
            'enable_blocked_admin_ips' => filter_var(SecuritySetting::get('enable_blocked_admin_ips', false), FILTER_VALIDATE_BOOLEAN),
            'blocked_admin_ips' => SecuritySetting::get('blocked_admin_ips', ''),
            'enable_allowed_front_ips' => filter_var(SecuritySetting::get('enable_allowed_front_ips', false), FILTER_VALIDATE_BOOLEAN),
            'allowed_front_ips' => SecuritySetting::get('allowed_front_ips', ''),
            'enable_blocked_front_ips' => filter_var(SecuritySetting::get('enable_blocked_front_ips', false), FILTER_VALIDATE_BOOLEAN),
            'blocked_front_ips' => SecuritySetting::get('blocked_front_ips', ''),
            // reCAPTCHA settings
            'captcha_enabled' => filter_var(SecuritySetting::get('captcha_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'captcha_driver' => SecuritySetting::get('captcha_driver', 'google'),
            'captcha_site_key' => SecuritySetting::get('captcha_site_key', ''),
            'captcha_secret_key' => SecuritySetting::get('captcha_secret_key', ''),
            'captcha_google_version' => SecuritySetting::get('captcha_google_version', 'v3'),
            'captcha_google_min_score' => SecuritySetting::get('captcha_google_min_score', '0.5'),
            'captcha_google_project_id' => SecuritySetting::get('captcha_google_project_id', ''),
            // Notification settings
            'notification_enabled' => filter_var(SecuritySetting::get('notification_enabled', true), FILTER_VALIDATE_BOOLEAN),
            'notification_log_levels' => array_map('intval', array_filter(explode(',', SecuritySetting::get('notification_log_levels', implode(',', LogLevel::getDefaultNotificationLevels()))))),
        ];

        // 動的reCAPTCHAフォーム設定を取得
        $captchaFormSettings = CaptchaFormSetting::getOrderedForms();

        // メールテスト状態を取得（DB優先、セッションは一時的な状態のみ）
        $sessionTestResults = session('mail_test_results', []);
        
        $mailConnectionTested = (bool) ($sessionTestResults['mail_connection_tested'] ?? BaseSetting::getValue('mail_connection_tested', false));
        $mailSendTested = (bool) ($sessionTestResults['mail_send_tested'] ?? BaseSetting::getValue('mail_send_tested', false));
        $mailReceiveTested = (bool) ($sessionTestResults['mail_receive_tested'] ?? BaseSetting::getValue('mail_receive_tested', false));

        // CAPTCHAテスト結果を取得（セッション優先、DB次点）
        $captchaTestService = new CaptchaTestService();
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
        $currentCaptchaEnabled = SecuritySetting::get('captcha_enabled', false);
        $currentCaptchaDriver = SecuritySetting::get('captcha_driver', 'google');
        $currentCaptchaSettings = [
            'captcha_site_key' => SecuritySetting::get('captcha_site_key', ''),
            'captcha_secret_key' => SecuritySetting::get('captcha_secret_key', ''),
            'captcha_google_version' => SecuritySetting::get('captcha_google_version', 'v3'),
            'captcha_google_min_score' => SecuritySetting::get('captcha_google_min_score', '0.5'),
            'captcha_google_site_key' => SecuritySetting::get('captcha_google_site_key', ''),
            'captcha_google_secret_key' => SecuritySetting::get('captcha_google_secret_key', ''),
            'captcha_google_enterprise_site_key' => SecuritySetting::get('captcha_google_enterprise_site_key', ''),
            'captcha_google_enterprise_secret_key' => SecuritySetting::get('captcha_google_enterprise_secret_key', ''),
            'captcha_google_project_id' => SecuritySetting::get('captcha_google_project_id', ''),
            'captcha_turnstile_site_key' => SecuritySetting::get('captcha_turnstile_site_key', ''),
            'captcha_turnstile_secret_key' => SecuritySetting::get('captcha_turnstile_secret_key', ''),
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
        $submittedTestResult = $request->boolean('captcha_validation_status');
        
        // テスト結果をデータベースに保存（フォームから送信された値を使用）
        if ($submittedTestResult) {
            $captchaTestService->saveCaptchaTestResult('form_submission', true);
        }
        
        // CAPTCHA設定が変更された場合のテスト結果リセット処理
        // ただし、フォームでテスト成功状態が送信された場合は保持
        if ($captchaSettingsChanged && !$submittedTestResult) {
            $captchaTestService->resetCaptchaTestResults();
        }

        SecuritySetting::set('enable_allowed_admin_ips', $request->boolean('enable_allowed_admin_ips'));
        SecuritySetting::set('allowed_admin_ips', $request->input('allowed_admin_ips'));
        SecuritySetting::set('enable_blocked_admin_ips', $request->boolean('enable_blocked_admin_ips'));
        SecuritySetting::set('blocked_admin_ips', $request->input('blocked_admin_ips'));
        SecuritySetting::set('enable_allowed_front_ips', $request->boolean('enable_allowed_front_ips'));
        SecuritySetting::set('allowed_front_ips', $request->input('allowed_front_ips'));
        SecuritySetting::set('enable_blocked_front_ips', $request->boolean('enable_blocked_front_ips'));
        SecuritySetting::set('blocked_front_ips', $request->input('blocked_front_ips'));
        
        // Save reCAPTCHA settings
        SecuritySetting::set('captcha_enabled', $request->boolean('captcha_enabled'));
        SecuritySetting::set('captcha_driver', $request->input('captcha_driver', 'google'));
        
        // Save common keys directly
        SecuritySetting::set('captcha_site_key', $request->input('captcha_site_key', ''));
        SecuritySetting::set('captcha_secret_key', $request->input('captcha_secret_key', ''));
        
        SecuritySetting::set('captcha_google_version', $request->input('captcha_google_version', 'v3'));
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
                'before_save' => SecuritySetting::get('captcha_google_min_score')
            ]);
            SecuritySetting::set('captcha_google_min_score', $minScore);
            \Log::info('Min Score After Save', [
                'saved_value' => SecuritySetting::get('captcha_google_min_score')
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
        SecuritySetting::set('captcha_google_project_id', $request->input('captcha_google_project_id', ''));
        
        // Save notification settings
        $notificationEnabled = $request->boolean('notification_enabled');
        SecuritySetting::set('notification_enabled', $notificationEnabled);

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

            SecuritySetting::set('notification_log_levels', implode(',', $validLogLevels));
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
                
                if ($data['success'] ?? false) {
                    // v3の場合はスコアもチェック
                    if ($version === 'v3') {
                        $score = $data['score'] ?? 0;
                        $minScore = floatval($request->input('captcha_google_min_score', 0.5));
                        
                        if ($score >= $minScore) {
                            // テスト成功時はセッションのみに保存（DBには保存しない）
                            session(['captcha_test_result' => true]);
                            
                            return response()->json([
                                'success' => true,
                                'message' => "CAPTCHA validation successful (score: {$score})"
                            ]);
                        } else {
                            return response()->json([
                                'success' => false,
                                'message' => "CAPTCHA score too low: {$score} (minimum: {$minScore})"
                            ]);
                        }
                    } else {
                        // v2の場合もテスト成功時はセッションのみに保存（DBには保存しない）
                        session(['captcha_test_result' => true]);
                        
                        return response()->json([
                            'success' => true,
                            'message' => 'CAPTCHA validation successful'
                        ]);
                    }
                } else {
                    $errorCodes = $data['error-codes'] ?? [];
                    return response()->json([
                        'success' => false,
                        'message' => 'CAPTCHA validation failed: ' . implode(', ', $errorCodes)
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect to reCAPTCHA API'
                ]);
            }
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Unsupported CAPTCHA driver'
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
