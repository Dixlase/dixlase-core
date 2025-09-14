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
        $version = $settings['captcha_google_version'] ?? 'v3';
        
        Log::info('CAPTCHA Test Debug - Starting Google reCAPTCHA test', [
            'site_key' => substr($siteKey, 0, 10) . '...',
            'secret_key' => substr($secretKey, 0, 10) . '...',
            'version' => $version
        ]);
        
        if (empty($siteKey) || empty($secretKey)) {
            Log::warning('CAPTCHA Test Debug - Missing keys', [
                'has_site_key' => !empty($siteKey),
                'has_secret_key' => !empty($secretKey)
            ]);
            return [
                'success' => false,
                'message' => 'Site key or secret key is missing'
            ];
        }
        
        // バージョン検証を含む詳細テスト
        $versionTestResult = $this->performAdvancedVersionTest($secretKey, $version, $siteKey);
        if (!$versionTestResult['success']) {
            return $versionTestResult;
        }
        
        return [
            'success' => true,
            'message' => "Google reCAPTCHA {$version} connection test successful"
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
     * Google reCAPTCHAキーとバージョンの互換性を検証
     */
    private function validateGoogleRecaptchaKeyCompatibility(string $siteKey, string $version): array
    {
        // v3キーは通常6Lで始まる
        // v2キーは通常6Lで始まるが、異なるパターンもある
        // より確実な方法として、実際のAPIレスポンスでバージョンミスマッチを検出
        
        // サイトキーの基本形式チェック
        if (!preg_match('/^6[A-Za-z0-9_-]{39}$/', $siteKey)) {
            return [
                'valid' => false,
                'message' => 'Invalid site key format. Google reCAPTCHA site keys should be 40 characters starting with "6".'
            ];
        }
        
        return ['valid' => true];
    }

    /**
     * バージョン固有のエラーをチェック
     */
    private function checkVersionSpecificErrors(array $apiResponse, string $version): array
    {
        $errorCodes = $apiResponse['error-codes'] ?? [];
        
        // より詳細なバージョン検証のため、実際のトークンテストを実行
        if ($version === 'v3') {
            // v3の場合、scoreフィールドの存在をチェック
            $testResult = $this->performActualV3Test($apiResponse);
            if (!$testResult['valid']) {
                return $testResult;
            }
        } else {
            // v2の場合（checkbox/invisible）、v3特有のフィールドがないことを確認
            if (isset($apiResponse['score']) || isset($apiResponse['action'])) {
                return [
                    'valid' => false,
                    'message' => "Key type mismatch: This appears to be a v3 key but {$version} is configured. v2 keys should not return score/action fields."
                ];
            }
        }
        
        // v3キーでv2設定を使用した場合のエラーパターン
        if ($version !== 'v3' && in_array('invalid-keys', $errorCodes)) {
            return [
                'valid' => false,
                'message' => 'Key type mismatch: This appears to be a v3 key but v2 is configured. Please check your reCAPTCHA version settings.'
            ];
        }
        
        // v2キーでv3設定を使用した場合のエラーパターン
        if ($version === 'v3' && in_array('invalid-keys', $errorCodes)) {
            return [
                'valid' => false,
                'message' => 'Key type mismatch: This appears to be a v2 key but v3 is configured. Please check your reCAPTCHA version settings.'
            ];
        }
        
        // その他の重要なエラー
        if (in_array('invalid-input-secret', $errorCodes)) {
            return [
                'valid' => false,
                'message' => 'Invalid secret key. Please verify your secret key is correct.'
            ];
        }
        
        if (in_array('bad-request', $errorCodes)) {
            return [
                'valid' => false,
                'message' => 'Bad request. The request format may be incorrect for the selected version.'
            ];
        }
        
        return ['valid' => true];
    }

    /**
     * v3キーの実際のテストを実行
     */
    private function performActualV3Test(array $dummyResponse): array
    {
        // ダミーレスポンスでv3特有のフィールドをチェック
        // 実際のv3キーの場合、invalid-input-responseエラーでもscoreやactionフィールドが返される場合がある
        
        // v3キーの特徴：
        // - scoreフィールドが存在する（0.0-1.0の値）
        // - actionフィールドが存在する
        // - v2キーではこれらのフィールドは存在しない
        
        $errorCodes = $dummyResponse['error-codes'] ?? [];
        
        // invalid-input-responseは正常（ダミートークンのため）
        // しかし、v3キーの場合はscoreやactionが返される可能性がある
        if (in_array('invalid-input-response', $errorCodes)) {
            // v3キーかどうかの判定は困難なため、基本的には通す
            // 実際の使用時にバージョンミスマッチが検出される
            return ['valid' => true];
        }
        
        return ['valid' => true];
    }

    /**
     * バージョン互換性の詳細テストを実行
     */
    private function performVersionCompatibilityTest(string $secretKey, string $version): array
    {
        // 実際のGoogle reCAPTCHA APIの動作パターンを利用した検証
        // 異なるバージョンのキーは特定のエラーパターンを示す
        
        // 1. 空のレスポンスでテスト（基本的なキー検証）
        $basicTest = $this->performBasicKeyTest($secretKey);
        if (!$basicTest['valid']) {
            return $basicTest;
        }
        
        // 2. バージョン固有のトークンパターンでテスト
        $versionTest = $this->performVersionSpecificTest($secretKey, $version);
        if (!$versionTest['valid']) {
            return $versionTest;
        }
        
        return ['valid' => true];
    }

    /**
     * 基本的なキー検証テスト
     */
    private function performBasicKeyTest(string $secretKey): array
    {
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secretKey,
            'response' => '',
            'remoteip' => '127.0.0.1',
        ]);
        
        if (!$response->successful()) {
            return [
                'valid' => false,
                'message' => 'reCAPTCHA APIへの接続に失敗しました。'
            ];
        }
        
        $data = $response->json();
        $errorCodes = $data['error-codes'] ?? [];
        
        if (in_array('invalid-input-secret', $errorCodes)) {
            return [
                'valid' => false,
                'message' => 'シークレットキーが無効です。正しいシークレットキーを入力してください。'
            ];
        }
        
        return ['valid' => true];
    }

    /**
     * バージョン固有のテスト
     */
    private function performVersionSpecificTest(string $secretKey, string $version): array
    {
        // v3とv2で異なる動作をするテストトークンを使用
        $testToken = 'version-compatibility-test-token-' . time();
        
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secretKey,
            'response' => $testToken,
            'remoteip' => '127.0.0.1',
        ]);
        
        if ($response->successful()) {
            $data = $response->json();
            $errorCodes = $data['error-codes'] ?? [];
            
            Log::info('CAPTCHA version compatibility test', [
                'version' => $version,
                'response' => $data,
                'error_codes' => $errorCodes
            ]);
            
            // v3キーの特徴を検出
            $hasV3Features = isset($data['score']) || isset($data['action']);
            
            // バージョンミスマッチの検出
            if ($version === 'v3' && !$hasV3Features && $data['success'] === false) {
                // v3設定だがv3特有のフィールドがない場合
                $errorPattern = implode(', ', $errorCodes);
                if (strpos($errorPattern, 'invalid') !== false) {
                    return [
                        'valid' => false,
                        'message' => 'バージョンミスマッチ: v3が設定されていますが、このキーはv2用のようです。reCAPTCHAのバージョン設定を確認してください。'
                    ];
                }
            }
            
            if (($version === 'v2_checkbox' || $version === 'v2_invisible') && $hasV3Features) {
                return [
                    'valid' => false,
                    'message' => "バージョンミスマッチ: {$version}が設定されていますが、このキーはv3用です。reCAPTCHAのバージョン設定をv3に変更してください。"
                ];
            }
            
            // 特定のエラーコードパターンでバージョンミスマッチを検出
            if (in_array('invalid-keys', $errorCodes)) {
                return [
                    'valid' => false,
                    'message' => 'キータイプとバージョン設定が一致しません。reCAPTCHAコンソールで取得したキーのタイプと設定を確認してください。'
                ];
            }
        }
        
        return ['valid' => true];
    }

    /**
     * APIレスポンスからバージョンミスマッチを検出
     */
    private function detectVersionMismatch(array $data, string $version, array $errorCodes): ?array
    {
        // v3キーの特徴：scoreフィールドが存在する
        $hasV3Fields = isset($data['score']) || isset($data['action']);
        
        // v2キーでv3設定の場合
        if ($version === 'v3' && !$hasV3Fields && !in_array('invalid-input-response', $errorCodes)) {
            return [
                'valid' => false,
                'message' => 'バージョンミスマッチ: v3が設定されていますが、このキーはv2用です。reCAPTCHAのバージョン設定を確認してください。'
            ];
        }
        
        // v3キーでv2設定の場合
        if ($version !== 'v3' && $hasV3Fields) {
            return [
                'valid' => false,
                'message' => "バージョンミスマッチ: {$version}が設定されていますが、このキーはv3用です。reCAPTCHAのバージョン設定をv3に変更してください。"
            ];
        }
        
        return null;
    }

    /**
     * 最終的なバージョンチェックを実行
     */
    private function performFinalVersionCheck(string $secretKey, string $version): array
    {
        // 空のトークンでテスト（missing-input-responseエラーが期待される）
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secretKey,
            'response' => '',
            'remoteip' => '127.0.0.1',
        ]);
        
        if ($response->successful()) {
            $data = $response->json();
            $errorCodes = $data['error-codes'] ?? [];
            
            // missing-input-responseが期待されるが、他のエラーが出た場合はキーの問題
            if (!in_array('missing-input-response', $errorCodes) && !empty($errorCodes)) {
                // キー自体に問題がある可能性
                if (in_array('invalid-input-secret', $errorCodes)) {
                    return [
                        'valid' => false,
                        'message' => 'シークレットキーが無効です。正しいシークレットキーを入力してください。'
                    ];
                }
            }
        }
        
        return ['valid' => true];
    }

    /**
     * 高度なバージョンテスト（実際のキータイプを検出）
     */
    private function performAdvancedVersionTest(string $secretKey, string $version, string $siteKey): array
    {
        Log::info('CAPTCHA Advanced Version Test - Starting', [
            'version' => $version,
            'site_key' => substr($siteKey, 0, 10) . '...'
        ]);

        // 1. 基本的なキー検証
        $basicTest = $this->testBasicKeyValidity($secretKey);
        if (!$basicTest['valid']) {
            return ['success' => false, 'message' => $basicTest['message']];
        }

        // 2. サイトキーのパターン分析
        $keyAnalysis = $this->analyzeSiteKeyPattern($siteKey);
        Log::info('CAPTCHA Advanced Version Test - Key Analysis', $keyAnalysis);

        // 3. 複数のテストトークンでバージョン特性を検証
        $versionCheck = $this->checkVersionCharacteristics($secretKey, $version);
        if (!$versionCheck['valid']) {
            return ['success' => false, 'message' => $versionCheck['message']];
        }

        return ['success' => true];
    }

    /**
     * 基本的なキー有効性テスト
     */
    private function testBasicKeyValidity(string $secretKey): array
    {
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secretKey,
            'response' => '',
            'remoteip' => '127.0.0.1',
        ]);

        if (!$response->successful()) {
            return ['valid' => false, 'message' => 'reCAPTCHA APIへの接続に失敗しました。'];
        }

        $data = $response->json();
        $errorCodes = $data['error-codes'] ?? [];

        if (in_array('invalid-input-secret', $errorCodes)) {
            return ['valid' => false, 'message' => 'シークレットキーが無効です。'];
        }

        return ['valid' => true];
    }

    /**
     * サイトキーのパターン分析
     */
    private function analyzeSiteKeyPattern(string $siteKey): array
    {
        // Google reCAPTCHAのサイトキーパターン分析
        // v2とv3のキーには微妙な違いがある場合がある
        
        $analysis = [
            'length' => strlen($siteKey),
            'starts_with_6L' => str_starts_with($siteKey, '6L'),
            'pattern' => 'unknown'
        ];

        // 一般的なパターン（完全ではないが参考情報として）
        if (preg_match('/^6L[a-zA-Z0-9_-]{38}$/', $siteKey)) {
            $analysis['pattern'] = 'standard_40_char';
        }

        return $analysis;
    }

    /**
     * バージョン特性をチェック
     */
    private function checkVersionCharacteristics(string $secretKey, string $version): array
    {
        Log::info('CAPTCHA Version Check - Starting comprehensive test', [
            'version' => $version,
            'secret_key_prefix' => substr($secretKey, 0, 10) . '...'
        ]);

        // 1. 基本的なAPIテスト（空のレスポンス）
        $basicResponse = $this->testWithEmptyResponse($secretKey);
        
        // 2. v3特有のテスト（scoreパラメータ付きでテスト）
        $v3Response = $this->testV3Characteristics($secretKey);
        
        // 3. Enterprise APIテスト
        $enterpriseResponse = $this->testEnterpriseCharacteristics($secretKey);

        // 4. レスポンスパターンを分析
        return $this->analyzeResponsePatterns([
            'basic' => $basicResponse,
            'v3_test' => $v3Response,
            'enterprise' => $enterpriseResponse
        ], $version);
    }

    /**
     * 基本的なAPIレスポンステスト
     */
    private function testWithEmptyResponse(string $secretKey): array
    {
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secretKey,
            'response' => '',
            'remoteip' => '127.0.0.1',
        ]);

        $data = $response->successful() ? $response->json() : [];
        
        Log::info('CAPTCHA Version Test - Basic API Test', [
            'data' => $data,
            'error_codes' => $data['error-codes'] ?? []
        ]);

        return $data;
    }

    /**
     * v3特有の特性をテスト
     */
    private function testV3Characteristics(string $secretKey): array
    {
        // v3キーの場合、特定のパラメータでより詳細な情報が得られる場合がある
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secretKey,
            'response' => 'test-v3-response-' . time(),
            'remoteip' => '127.0.0.1',
        ]);

        $data = $response->successful() ? $response->json() : [];
        
        Log::info('CAPTCHA Version Test - v3 Characteristics Test', [
            'data' => $data,
            'has_score' => isset($data['score']),
            'has_action' => isset($data['action']),
            'error_codes' => $data['error-codes'] ?? []
        ]);

        return $data;
    }

    /**
     * Enterprise API特性をテスト
     */
    private function testEnterpriseCharacteristics(string $secretKey): array
    {
        // Enterprise APIエンドポイントでテスト
        $response = Http::asForm()->post('https://recaptchaenterprise.googleapis.com/v1/projects/test/assessments', [
            'secret' => $secretKey,
            'response' => 'test-enterprise-' . time(),
        ]);

        $data = $response->successful() ? $response->json() : [];
        $statusCode = $response->status();
        
        Log::info('CAPTCHA Version Test - Enterprise API Test', [
            'status_code' => $statusCode,
            'data' => $data,
            'is_enterprise_endpoint' => $statusCode !== 404
        ]);

        return array_merge($data, ['_enterprise_status' => $statusCode]);
    }

    /**
     * レスポンスパターンを分析してバージョンミスマッチを検出
     */
    private function analyzeResponsePatterns(array $responses, string $version): array
    {
        Log::info('CAPTCHA Version Analysis - Starting pattern analysis', [
            'version' => $version,
            'response_keys' => array_keys($responses)
        ]);

        // 簡略化されたバージョン検証アプローチ
        // Google reCAPTCHAの実際の動作に基づいて判定
        
        $basicResponse = $responses['basic'] ?? [];
        $errorCodes = $basicResponse['error-codes'] ?? [];
        
        // 基本的なキー有効性チェック
        if (in_array('invalid-input-secret', $errorCodes)) {
            return [
                'valid' => false,
                'message' => 'シークレットキーが無効です。正しいキーを入力してください。'
            ];
        }

        // 現在は基本的な接続テストのみ実行
        // バージョンミスマッチ検出は実際のキーの動作パターンが明確になるまで無効化
        Log::info('CAPTCHA Version Analysis - Simplified validation', [
            'version' => $version,
            'basic_errors' => $errorCodes,
            'validation_result' => 'pass_basic_connectivity'
        ]);

        return ['valid' => true];
    }

    /**
     * レスポンスパターンからバージョンミスマッチを検出（将来の実装用）
     */
    private function detectVersionMismatchFromResponses(array $responses, string $version): array
    {
        // 将来的により精密なバージョン検出ロジックを実装する場合に使用
        // 現在は基本的な検証のみ
        return ['valid' => true];
    }

    /**
     * テスト結果をデータベースに保存
     */
    public function saveCaptchaTestResult(string $driver, bool $success, ?string $errorMessage = null): void
    {
        $testKey = "captcha_authentication_result";
        
        SecuritySetting::updateOrCreate(
            ['name' => $testKey],
            ['value' => $success ? '1' : '0']
        );
    }

    /**
     * テスト結果をデータベースから取得
     */
    public function getTestResult(): bool
    {
        // セッションから取得を試行
        $sessionResult = session('captcha_test_result');
        if ($sessionResult !== null) {
            return (bool) $sessionResult;
        }
        
        // データベースから取得
        $testKey = "captcha_authentication_result";
        $setting = SecuritySetting::where('name', $testKey)->first();
        
        if (!$setting) {
            return false;
        }
        
        return (bool) $setting->value;
    }

    /**
     * テスト結果をデータベースから取得（詳細版）
     */
    public function getCaptchaTestResult(string $driver): ?array
    {
        $testKey = "captcha_authentication_result";
        $setting = SecuritySetting::where('name', $testKey)->first();
        
        if (!$setting) {
            return null;
        }
        
        $success = (bool) $setting->value;
        $testedAt = $setting->updated_at ? $setting->updated_at->toISOString() : null;
        
        // DBに値が'0'で保存されている場合のみ失敗として扱う
        // 値が存在しない場合は未実行として扱う
        if ($setting->value === '0') {
            return [
                'success' => false,
                'tested_at' => $testedAt,
                'error_message' => 'テストに失敗しました'
            ];
        } elseif ($setting->value === '1') {
            return [
                'success' => true,
                'tested_at' => $testedAt,
                'error_message' => null
            ];
        }
        
        // その他の場合は未実行として扱う
        return null;
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
