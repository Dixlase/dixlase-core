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
        $settings = [
            'captcha_enabled' => filter_var($this->securitySettingRepository->get('captcha_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'captcha_driver' => $this->securitySettingRepository->get('captcha_driver', 'google'),
            'captcha_site_key' => $this->securitySettingRepository->get('captcha_site_key', ''),
            'captcha_secret_key' => $this->securitySettingRepository->get('captcha_secret_key', ''),
            'captcha_google_version' => $this->securitySettingRepository->get('captcha_google_version', 'v3'),
            'captcha_google_min_score' => $this->securitySettingRepository->get('captcha_google_min_score', '0.5'),
            'captcha_google_project_id' => $this->securitySettingRepository->get('captcha_google_project_id', ''),
        ];

        // CAPTCHAテスト結果を取得
        $captchaTestService = app(CaptchaTestService::class);
        
        // バリデーションエラーがない場合はセッションをクリアしてDBから読み込み
        if (!session()->has('errors') || !session('errors')->any()) {
            session()->forget('captcha_authentication_result');
        }
        
        $captchaTestResult = $captchaTestService->getTestResult();

        $this->viewParams['settings'] = $settings;
        $this->viewParams['captchaTestResult'] = $captchaTestResult;

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
            'captcha_google_version' => 'nullable|in:v2,v3',
            'captcha_google_min_score' => 'nullable|numeric|min:0|max:1',
            'captcha_google_project_id' => 'nullable|string|max:255',
        ]);

        // CAPTCHA設定を更新
        $this->securitySettingRepository->set('captcha_enabled', $validated['captcha_enabled'] ?? false);
        $this->securitySettingRepository->set('captcha_driver', $validated['captcha_driver'] ?? 'google');
        $this->securitySettingRepository->set('captcha_site_key', $validated['captcha_site_key'] ?? '');
        $this->securitySettingRepository->set('captcha_secret_key', $validated['captcha_secret_key'] ?? '');
        $this->securitySettingRepository->set('captcha_google_version', $validated['captcha_google_version'] ?? 'v3');
        $this->securitySettingRepository->set('captcha_google_min_score', $validated['captcha_google_min_score'] ?? '0.5');
        $this->securitySettingRepository->set('captcha_google_project_id', $validated['captcha_google_project_id'] ?? '');

        return redirect()->route('admin.settings.security.captcha')
            ->with('success', __('admin.settings.security.captcha_settings_updated'));
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
                'message' => __('admin.settings.security.captcha_token_required'),
            ]);
        }

        try {
            $secretKey = $this->securitySettingRepository->get('captcha_secret_key', '');
            $minScore = (float) $this->securitySettingRepository->get('captcha_google_min_score', '0.5');
            
            $result = $this->verifyCaptchaToken($token, $secretKey, $driver, $minScore);
            
            if ($result['success']) {
                // テスト結果を保存
                $captchaTestService = app(CaptchaTestService::class);
                $captchaTestService->saveTestResult(true);
                
                return response()->json([
                    'success' => true,
                    'message' => __('admin.settings.security.captcha_validation_success_with_score', [
                        'score' => $result['score'] ?? 'N/A'
                    ]),
                    'score' => $result['score'] ?? null,
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? __('admin.settings.security.captcha_validation_failed'),
            ]);
            
        } catch (\Exception $e) {
            Log::error('CAPTCHA validation error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => __('admin.settings.security.captcha_api_connection_failed'),
            ]);
        }
    }

    /**
     * CAPTCHAテスト結果をクリア
     */
    public function clearTest()
    {
        $captchaTestService = app(CaptchaTestService::class);
        $captchaTestService->clearTestResult();
        
        return response()->json(['success' => true]);
    }

    /**
     * CAPTCHAトークンを検証
     */
    protected function verifyCaptchaToken(string $token, string $secretKey, string $driver, float $minScore): array
    {
        $verifyUrl = match ($driver) {
            'google', 'google_enterprise' => 'https://www.google.com/recaptcha/api/siteverify',
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
                'message' => __('admin.settings.security.captcha_validation_failed_with_errors', [
                    'errors' => implode(', ', $data['error-codes'] ?? ['unknown'])
                ]),
            ];
        }

        // v3の場合はスコアチェック
        if (isset($data['score']) && $data['score'] < $minScore) {
            return [
                'success' => false,
                'message' => __('admin.settings.security.captcha_validation_score_too_low', [
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
}
