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
use App\Services\CaptchaTestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminSecurityCaptchaController extends AdminLoggedInController
{
    protected SecuritySettingRepositoryInterface $securitySettingRepository;

    public function __construct(SecuritySettingRepositoryInterface $securitySettingRepository)
    {
        parent::__construct();
        $this->securitySettingRepository = $securitySettingRepository;
    }

    /**
     * CAPTCHA設定ページ
     */
    public function index()
    {
        $this->addBreadcrumb(null, __('admin/nav.settings.text'));
        $this->addBreadcrumb('admin.settings.security.index', __('admin/nav.settings.security.text'));
        $this->addBreadcrumb(null, __('admin/nav.settings.security.captcha'));
        $this->setBreadcrumbs();
        
        $currentDriver = $this->securitySettingRepository->get('captcha_driver', 'google');
        
        // プロバイダごとのキーを取得
        $providerKeys = $this->getProviderKeys();
        
        $settings = [
            'captcha_enabled' => filter_var($this->securitySettingRepository->get('captcha_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'captcha_driver' => $currentDriver,
            // 現在のプロバイダのキーを表示用に設定
            'captcha_site_key' => $providerKeys[$currentDriver]['site_key'] ?? '',
            'captcha_secret_key' => $providerKeys[$currentDriver]['secret_key'] ?? '',
            'captcha_google_version' => $this->securitySettingRepository->get('captcha_google_version', 'v3'),
            'captcha_google_min_score' => $this->securitySettingRepository->get('captcha_google_min_score', '0.5'),
            'captcha_google_project_id' => $this->securitySettingRepository->get('captcha_google_project_id', ''),
            // 全プロバイダのキー（JavaScript用）
            'provider_keys' => $providerKeys,
        ];

        // CAPTCHAテスト結果を取得
        $captchaTestService = app(CaptchaTestService::class);
        
        // バリデーションエラーがない場合はセッションをクリアしてDBから読み込み
        if (!session()->has('errors') || !session('errors')->any()) {
            session()->forget('captcha_authentication_result');
        }
        
        $captchaTestResult = $captchaTestService->getTestResult();
        $captchaTestDetails = $captchaTestService->getCaptchaTestResult($settings['captcha_driver']);

        $this->viewParams['settings'] = $settings;
        $this->viewParams['captchaTestResult'] = $captchaTestResult;
        $this->viewParams['captchaTestDetails'] = $captchaTestDetails;

        return view('admin.settings.security.captcha', $this->viewParams);
    }

    /**
     * CAPTCHA設定の更新
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'captcha_enabled' => 'boolean',
            'captcha_driver' => 'required_if:captcha_enabled,1|in:google,google_enterprise,turnstile',
            'captcha_site_key' => 'required_if:captcha_enabled,1|nullable|string|max:255',
            'captcha_secret_key' => 'required_if:captcha_enabled,1|nullable|string|max:255',
            'captcha_google_version' => 'nullable|in:v2_checkbox,v2_invisible,v3',
            'captcha_google_min_score' => 'nullable|numeric|min:0|max:1',
            'captcha_google_project_id' => 'nullable|string|max:255',
            'captcha_authentication_result' => 'nullable|boolean',
        ]);

        // 現在のCAPTCHA設定を取得
        $currentDriver = $this->securitySettingRepository->get('captcha_driver', 'google');
        $currentSiteKey = $this->securitySettingRepository->get('captcha_site_key', '');
        $currentSecretKey = $this->securitySettingRepository->get('captcha_secret_key', '');
        $currentVersion = $this->securitySettingRepository->get('captcha_google_version', 'v3');
        $currentMinScore = $this->securitySettingRepository->get('captcha_google_min_score', '0.5');
        
        // 新しいCAPTCHA設定
        $newDriver = $validated['captcha_driver'] ?? 'google';
        $newSiteKey = $validated['captcha_site_key'] ?? '';
        $newSecretKey = $validated['captcha_secret_key'] ?? '';
        $newVersion = $validated['captcha_google_version'] ?? 'v3';
        $newMinScore = $validated['captcha_google_min_score'] ?? '0.5';
        
        // CAPTCHA設定が変更されたかチェック
        $captchaSettingsChanged = (
            $currentDriver !== $newDriver ||
            $currentSiteKey !== $newSiteKey ||
            $currentSecretKey !== $newSecretKey ||
            $currentVersion !== $newVersion ||
            (string)$currentMinScore !== (string)$newMinScore
        );
        
        // フォームから送信されたテスト結果を確認
        $submittedTestResult = $request->boolean('captcha_authentication_result');
        
        // CAPTCHAテストサービス
        $captchaTestService = app(CaptchaTestService::class);
        
        // CAPTCHA設定が変更された場合のテスト結果リセット処理
        // ただし、フォームでテスト成功状態が送信された場合は保持
        if ($captchaSettingsChanged && !$submittedTestResult) {
            $captchaTestService->resetCaptchaTestResults();
        } elseif ($submittedTestResult) {
            // テスト結果をデータベースに保存（フォームから送信された値を使用）
            $captchaTestService->saveCaptchaTestResult($newDriver, true);
        }

        // CAPTCHA設定を更新
        $this->securitySettingRepository->set('captcha_enabled', $validated['captcha_enabled'] ?? false);
        $this->securitySettingRepository->set('captcha_driver', $newDriver);
        
        // プロバイダごとにキーを保存
        $this->saveProviderKeys($newDriver, $newSiteKey, $newSecretKey);
        
        $this->securitySettingRepository->set('captcha_google_version', $validated['captcha_google_version'] ?? 'v3');
        $this->securitySettingRepository->set('captcha_google_min_score', $validated['captcha_google_min_score'] ?? '0.5');
        $this->securitySettingRepository->set('captcha_google_project_id', $validated['captcha_google_project_id'] ?? '');

        return redirect()->route('admin.settings.security.captcha')
            ->with('success', __('admin/settings/security/captcha.settings_updated'));
    }

    /**
     * CAPTCHAウィジェットの検証
     */
    public function validateWidget(Request $request)
    {
        $token = $request->input('token');
        $driver = $request->input('driver', 'google');
        
        if (empty($token)) {
            return response()->json([
                'success' => false,
                'message' => __('admin/settings/security/captcha.token_required'),
            ]);
        }

        try {
            // フォームから送信された値を優先的に使用（保存前のテスト用）
            $secretKey = $request->input('secret_key') ?: $this->securitySettingRepository->get('captcha_secret_key', '');
            $minScore = (float) ($request->input('min_score') ?: $this->securitySettingRepository->get('captcha_google_min_score', '0.5'));
            $siteKey = $request->input('site_key') ?: $this->securitySettingRepository->get('captcha_site_key', '');
            $projectId = $request->input('project_id') ?: $this->securitySettingRepository->get('captcha_google_project_id', '');
            
            if (empty($secretKey)) {
                return response()->json([
                    'success' => false,
                    'message' => __('admin/settings/security/captcha.secret_key_required'),
                ]);
            }
            
            $result = $this->verifyCaptchaToken($token, $secretKey, $driver, $minScore, $siteKey, $projectId);
            
            if ($result['success']) {
                // テスト結果を保存
                $captchaTestService = app(CaptchaTestService::class);
                $captchaTestService->saveCaptchaTestResult($driver, true);
                
                return response()->json([
                    'success' => true,
                    'message' => __('admin/settings/security/captcha.validation_success_with_score', [
                        'score' => $result['score'] ?? 'N/A'
                    ]),
                    'score' => $result['score'] ?? null,
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? __('admin/settings/security/captcha.validation_failed'),
            ]);
            
        } catch (\Exception $e) {
            Log::error('CAPTCHA validation error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => __('admin/settings/security/captcha.api_connection_failed'),
            ]);
        }
    }

    /**
     * CAPTCHAテスト結果をクリア
     */
    public function clearTest()
    {
        $captchaTestService = app(CaptchaTestService::class);
        $captchaTestService->resetCaptchaTestResults();
        
        return response()->json(['success' => true]);
    }

    /**
     * CAPTCHAトークンを検証
     */
    protected function verifyCaptchaToken(string $token, string $secretKey, string $driver, float $minScore, ?string $siteKey = null, ?string $projectId = null): array
    {
        // Google reCAPTCHA Enterpriseは別のAPIを使用
        if ($driver === 'google_enterprise') {
            return $this->verifyEnterpriseToken($token, $secretKey, $siteKey, $projectId, $minScore);
        }
        
        $verifyUrl = match ($driver) {
            'google' => 'https://www.google.com/recaptcha/api/siteverify',
            'turnstile' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            default => throw new \InvalidArgumentException("Unsupported CAPTCHA driver: {$driver}"),
        };

        $response = Http::asForm()->post($verifyUrl, [
            'secret' => $secretKey,
            'response' => $token,
        ]);

        $data = $response->json();

        if (!($data['success'] ?? false)) {
            return [
                'success' => false,
                'message' => __('admin/settings/security/captcha.validation_failed_with_errors', [
                    'errors' => implode(', ', $data['error-codes'] ?? ['unknown'])
                ]),
            ];
        }

        // v3の場合はスコアチェック
        if (isset($data['score']) && $data['score'] < $minScore) {
            return [
                'success' => false,
                'message' => __('admin/settings/security/captcha.validation_score_too_low', [
                    'score' => $data['score'],
                    'min_score' => $minScore,
                ]),
                'score' => $data['score'],
            ];
        }

        return [
            'success' => true,
            'score' => $data['score'] ?? null,
        ];
    }
    
    /**
     * Google reCAPTCHA Enterpriseトークンを検証
     */
    protected function verifyEnterpriseToken(string $token, string $apiKey, ?string $siteKey, ?string $projectId, float $minScore): array
    {
        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => __('admin/settings/security/captcha.test_enterprise_keys_missing'),
            ];
        }
        
        if (empty($projectId)) {
            return [
                'success' => false,
                'message' => __('admin/settings/security/captcha.enterprise_project_id_required'),
            ];
        }
        
        // Google reCAPTCHA Enterprise API呼び出し
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("https://recaptchaenterprise.googleapis.com/v1/projects/{$projectId}/assessments?key={$apiKey}", [
            'event' => [
                'token' => $token,
                'siteKey' => $siteKey ?? '',
            ]
        ]);
        
        if (!$response->successful()) {
            Log::error('Google reCAPTCHA Enterprise API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [
                'success' => false,
                'message' => __('admin/settings/security/captcha.api_connection_failed'),
            ];
        }
        
        $data = $response->json();
        
        Log::info('Google reCAPTCHA Enterprise API Response', [
            'token_valid' => $data['tokenProperties']['valid'] ?? false,
            'score' => $data['riskAnalysis']['score'] ?? 'not_provided',
            'reasons' => $data['riskAnalysis']['reasons'] ?? [],
        ]);
        
        if (!($data['tokenProperties']['valid'] ?? false)) {
            $reasons = $data['tokenProperties']['invalidReason'] ?? 'unknown';
            return [
                'success' => false,
                'message' => __('admin/settings/security/captcha.validation_failed_with_errors', [
                    'errors' => is_array($reasons) ? implode(', ', $reasons) : $reasons
                ]),
            ];
        }
        
        $score = $data['riskAnalysis']['score'] ?? 0;
        
        if ($score < $minScore) {
            return [
                'success' => false,
                'message' => __('admin/settings/security/captcha.validation_score_too_low', [
                    'score' => $score,
                    'min_score' => $minScore,
                ]),
                'score' => $score,
            ];
        }
        
        return [
            'success' => true,
            'score' => $score,
        ];
    }
    
    /**
     * プロバイダごとのキーを取得
     */
    protected function getProviderKeys(): array
    {
        return [
            'google' => [
                'site_key' => $this->securitySettingRepository->get('captcha_google_site_key', ''),
                'secret_key' => $this->securitySettingRepository->get('captcha_google_secret_key', ''),
            ],
            'google_enterprise' => [
                'site_key' => $this->securitySettingRepository->get('captcha_google_enterprise_site_key', ''),
                'secret_key' => $this->securitySettingRepository->get('captcha_google_enterprise_secret_key', ''),
            ],
            'turnstile' => [
                'site_key' => $this->securitySettingRepository->get('captcha_turnstile_site_key', ''),
                'secret_key' => $this->securitySettingRepository->get('captcha_turnstile_secret_key', ''),
            ],
        ];
    }
    
    /**
     * プロバイダごとにキーを保存
     */
    protected function saveProviderKeys(string $driver, string $siteKey, string $secretKey): void
    {
        $keyPrefix = match ($driver) {
            'google' => 'captcha_google',
            'google_enterprise' => 'captcha_google_enterprise',
            'turnstile' => 'captcha_turnstile',
            default => 'captcha_google',
        };
        
        $this->securitySettingRepository->set("{$keyPrefix}_site_key", $siteKey);
        $this->securitySettingRepository->set("{$keyPrefix}_secret_key", $secretKey);
    }
}
