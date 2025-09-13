<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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

namespace App\Services;

use App\Models\SecuritySetting;
use App\Captcha\GoogleRecaptchaDriver;
use App\Captcha\TurnstileCaptchaDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CaptchaTestService
{
    /**
     * CAPTCHA接続テストを実行
     */
    public function testCaptchaConnection(array $settings): array
    {
        $driver = $settings['captcha_driver'] ?? 'google';
        
        try {
            switch ($driver) {
                case 'google':
                    return $this->testGoogleRecaptcha($settings);
                case 'google_enterprise':
                    return $this->testGoogleRecaptchaEnterprise($settings);
                case 'turnstile':
                    return $this->testTurnstile($settings);
                default:
                    return [
                        'success' => false,
                        'message' => 'Unsupported CAPTCHA driver: ' . $driver
                    ];
            }
        } catch (\Exception $e) {
            Log::error('CAPTCHA test exception', [
                'driver' => $driver,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Test failed due to system error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Google reCAPTCHA標準版のテスト
     */
    private function testGoogleRecaptcha(array $settings): array
    {
        $siteKey = $settings['captcha_google_site_key'] ?? '';
        $secretKey = $settings['captcha_google_secret_key'] ?? '';
        
        if (empty($siteKey) || empty($secretKey)) {
            return [
                'success' => false,
                'message' => 'Site key or secret key is missing'
            ];
        }
        
        // ダミートークンでAPI接続をテスト
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secretKey,
            'response' => 'test-token',
            'remoteip' => '127.0.0.1',
        ]);
        
        if (!$response->successful()) {
            return [
                'success' => false,
                'message' => 'API request failed with status: ' . $response->status()
            ];
        }
        
        $data = $response->json();
        
        // invalid-input-responseは正常（ダミートークンのため）
        if (isset($data['error-codes']) && in_array('invalid-input-secret', $data['error-codes'])) {
            return [
                'success' => false,
                'message' => 'Invalid secret key'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Google reCAPTCHA connection test successful'
        ];
    }

    /**
     * Google reCAPTCHA Enterprise版のテスト
     */
    private function testGoogleRecaptchaEnterprise(array $settings): array
    {
        $siteKey = $settings['captcha_google_enterprise_site_key'] ?? '';
        $secretKey = $settings['captcha_google_enterprise_secret_key'] ?? '';
        $projectId = $settings['captcha_google_project_id'] ?? '';
        
        if (empty($siteKey) || empty($secretKey) || empty($projectId)) {
            return [
                'success' => false,
                'message' => 'Site key, secret key, or project ID is missing'
            ];
        }
        
        // Enterprise APIの場合、Google Cloud SDKが必要
        try {
            $driver = new GoogleRecaptchaDriver([
                'site_key' => $siteKey,
                'secret_key' => $secretKey,
                'project_id' => $projectId,
                'use_enterprise' => true
            ]);
            
            // 基本的な設定チェック
            if (!$driver->isEnabled()) {
                return [
                    'success' => false,
                    'message' => 'Google reCAPTCHA Enterprise configuration is invalid'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Google reCAPTCHA Enterprise configuration test successful'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Enterprise API test failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cloudflare Turnstileのテスト
     */
    private function testTurnstile(array $settings): array
    {
        $siteKey = $settings['captcha_turnstile_site_key'] ?? '';
        $secretKey = $settings['captcha_turnstile_secret_key'] ?? '';
        
        if (empty($siteKey) || empty($secretKey)) {
            return [
                'success' => false,
                'message' => 'Site key or secret key is missing'
            ];
        }
        
        // ダミートークンでAPI接続をテスト
        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => $secretKey,
            'response' => 'test-token',
            'remoteip' => '127.0.0.1',
        ]);
        
        if (!$response->successful()) {
            return [
                'success' => false,
                'message' => 'API request failed with status: ' . $response->status()
            ];
        }
        
        $data = $response->json();
        
        // invalid-input-secretエラーの場合はキーが無効
        if (isset($data['error-codes']) && in_array('invalid-input-secret', $data['error-codes'])) {
            return [
                'success' => false,
                'message' => 'Invalid secret key'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Cloudflare Turnstile connection test successful'
        ];
    }

    /**
     * テスト結果をデータベースに保存
     */
    public function saveCaptchaTestResult(string $driver, bool $success, ?string $errorMessage = null): void
    {
        $testKey = "captcha_test_result";
        
        SecuritySetting::updateOrCreate(
            ['name' => $testKey],
            ['value' => $success ? '1' : '0']
        );
    }

    /**
     * テスト結果をデータベースから取得
     */
    public function getCaptchaTestResult(string $driver): ?array
    {
        $testKey = "captcha_test_result";
        $setting = SecuritySetting::where('name', $testKey)->first();
        
        if (!$setting) {
            return null;
        }
        
        $success = (bool) $setting->value;
        $testedAt = $setting->updated_at ? $setting->updated_at->toISOString() : null;
        
        return [
            'success' => $success,
            'tested_at' => $testedAt,
            'error_message' => $success ? null : 'テストに失敗しました'
        ];
    }

    /**
     * 設定変更時にテスト結果をリセット
     */
    public function resetCaptchaTestResults(): void
    {
        $testKey = "captcha_test_result";
        SecuritySetting::updateOrCreate(
            ['name' => $testKey],
            ['value' => '0']
        );
    }

    /**
     * 現在の設定でテストが必要かチェック
     */
    public function isTestRequired(array $settings): bool
    {
        // CAPTCHAが無効の場合はテスト不要
        if (!($settings['captcha_enabled'] ?? false)) {
            return false;
        }
        
        $driver = $settings['captcha_driver'] ?? 'google';
        $testResult = $this->getCaptchaTestResult($driver);
        
        // テスト結果がない場合はテスト必要
        if (!$testResult) {
            return true;
        }
        
        // テストが失敗している場合はテスト必要
        return !($testResult['success'] ?? false);
    }
}
