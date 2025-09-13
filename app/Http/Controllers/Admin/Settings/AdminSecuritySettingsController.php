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
        // バリデーションエラーがある場合はセッションを保持（リセット状態を維持）
        if (!session()->has('errors')) {
            // ページ開始時にCAPTCHAセッションをクリアして最新のDB状態を反映
            session()->forget('captcha_test_result');
        }

        $settings = [
            'enable_allowed_admin_ips' => SecuritySetting::get('enable_allowed_admin_ips', false),
            'allowed_admin_ips' => SecuritySetting::get('allowed_admin_ips', ''),
            'enable_blocked_admin_ips' => SecuritySetting::get('enable_blocked_admin_ips', false),
            'blocked_admin_ips' => SecuritySetting::get('blocked_admin_ips', ''),
            'enable_allowed_front_ips' => SecuritySetting::get('enable_allowed_front_ips', false),
            'allowed_front_ips' => SecuritySetting::get('allowed_front_ips', ''),
            'enable_blocked_front_ips' => SecuritySetting::get('enable_blocked_front_ips', false),
            'blocked_front_ips' => SecuritySetting::get('blocked_front_ips', ''),
            // reCAPTCHA settings
            'captcha_enabled' => SecuritySetting::get('captcha_enabled', false),
            'captcha_driver' => SecuritySetting::get('captcha_driver', 'google'),
            'captcha_google_site_key' => SecuritySetting::get('captcha_google_site_key', ''),
            'captcha_google_secret_key' => SecuritySetting::get('captcha_google_secret_key', ''),
            'captcha_google_version' => SecuritySetting::get('captcha_google_version', 'v3'),
            'captcha_google_min_score' => SecuritySetting::get('captcha_google_min_score', '0.5'),
            'captcha_google_enterprise_site_key' => SecuritySetting::get('captcha_google_enterprise_site_key', ''),
            'captcha_google_enterprise_secret_key' => SecuritySetting::get('captcha_google_enterprise_secret_key', ''),
            'captcha_google_project_id' => SecuritySetting::get('captcha_google_project_id', ''),
            // Turnstile settings
            'captcha_turnstile_site_key' => SecuritySetting::get('captcha_turnstile_site_key', ''),
            'captcha_turnstile_secret_key' => SecuritySetting::get('captcha_turnstile_secret_key', ''),
            // Notification settings
            'notification_enabled' => SecuritySetting::get('notification_enabled', true),
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
        $captchaTestResults = [];
        
        // セッションから共通のテスト結果を取得
        $sessionResult = session('captcha_test_result');
        
        foreach (['google', 'google_enterprise', 'turnstile'] as $driver) {
            if ($sessionResult) {
                // セッションにデータがある場合はそれを使用（リセット状態も含む）
                $captchaTestResults[$driver] = $sessionResult;
            } else {
                // セッションにない場合のみDBから取得
                $testResult = $captchaTestService->getCaptchaTestResult($driver);
                $captchaTestResults[$driver] = $testResult;
            }
        }
        
        // デバッグ用: セッションとテスト結果の状態をログ出力
        if (config('app.debug')) {
            \Log::debug('CAPTCHA Debug - Session result:', ['session' => $sessionResult]);
            \Log::debug('CAPTCHA Debug - Test results:', ['results' => $captchaTestResults]);
            \Log::debug('CAPTCHA Debug - Has validation errors:', ['has_errors' => session()->has('errors')]);
        }

        $this->viewParams['settings'] = $settings;
        $this->viewParams['captchaFormSettings'] = $captchaFormSettings;
        $this->viewParams['captchaTestResults'] = $captchaTestResults;
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
            
            // セッションの状態をチェック（プロバイダー変更でリセットされた場合）
            $sessionResult = session('captcha_test_result');
            $isSessionReset = $sessionResult && ($sessionResult['is_reset'] ?? false);
            $hasValidSessionTest = $sessionResult && ($sessionResult['success'] ?? false) && !$isSessionReset;
            
            // 無効→有効に変更された場合、設定が変更された場合、またはセッションがリセット状態の場合はテストが必要
            if (!$currentCaptchaEnabled || $captchaSettingsChanged || $isSessionReset) {
                // セッションに有効なテスト結果がある場合はテスト不要
                if (!$hasValidSessionTest && ($captchaTestService->isTestRequired($captchaSettings) || $isSessionReset)) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['captcha' => __('admin.settings.security.captcha_test_required')]);
                }
            }
        }
        
        // CAPTCHA設定が変更された場合のみテスト結果をリセット（保存時は保持）
        // 注意: 設定保存時はテスト結果を保持し、設定変更時のみリセットする

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
        SecuritySetting::set('captcha_google_site_key', $request->input('captcha_google_site_key', ''));
        SecuritySetting::set('captcha_google_secret_key', $request->input('captcha_google_secret_key', ''));
        SecuritySetting::set('captcha_google_version', $request->input('captcha_google_version', 'v3'));
        SecuritySetting::set('captcha_google_min_score', $request->input('captcha_google_min_score', '0.5'));
        
        // Save Google reCAPTCHA Enterprise settings
        SecuritySetting::set('captcha_google_enterprise_site_key', $request->input('captcha_google_enterprise_site_key', ''));
        SecuritySetting::set('captcha_google_enterprise_secret_key', $request->input('captcha_google_enterprise_secret_key', ''));
        SecuritySetting::set('captcha_google_project_id', $request->input('captcha_google_project_id', ''));
        
        // Save Turnstile settings
        SecuritySetting::set('captcha_turnstile_site_key', $request->input('captcha_turnstile_site_key', ''));
        SecuritySetting::set('captcha_turnstile_secret_key', $request->input('captcha_turnstile_secret_key', ''));
        
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

        // フォーム保存後にCAPTCHAセッションをクリアして次回ページ読み込み時にDB状態を反映
        session()->forget('captcha_test_result');
        
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
        
        // セッションにもテスト結果を保存（即座にUIに反映するため）
        session()->put('captcha_test_result', [
            'success' => $result['success'],
            'tested_at' => now()->toISOString(),
            'error_message' => $result['success'] ? null : $result['message']
        ]);
        
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
            $secretKey = $request->input('captcha_google_secret_key', '');
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
        session()->forget('captcha_test_result');
        
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
            // セッションからテスト結果を削除（DBは更新しない）
            session()->forget('captcha_test_result');
            
            // セッションに未テスト状態を設定
            session()->put('captcha_test_result', [
                'success' => false,
                'tested_at' => null,
                'error_message' => null,
                'is_reset' => true // リセット状態を示すフラグ
            ]);
            
            // デバッグ用: セッション設定後の状態をログ出力
            if (config('app.debug')) {
                \Log::debug('CAPTCHA Clear - Session after reset:', ['session' => session('captcha_test_result')]);
            }
            
            Log::info("CAPTCHAテスト結果をクリアしました（セッションのみ）", [
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
