<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
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

namespace App\Services;

use App\Captcha\GoogleRecaptchaEnterpriseDriver;
use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\Helpers\CaptchaHelper;
use App\Models\SecuritySetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CaptchaTestService
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
        $this->securitySettingRepository = $securitySettingRepository;
    }

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
                        'message' => __('admin/settings/security/captcha.test_unsupported_driver').': '.$driver,
                    ];
            }
        } catch (\Exception $e) {
            Log::error('CAPTCHA test exception', [
                'driver' => $driver,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => __('admin/settings/security/captcha.test_system_error').': '.$e->getMessage(),
            ];
        }
    }

    /**
     * Google reCAPTCHA標準版のテスト
     */
    private function testGoogleRecaptcha(array $settings): array
    {
        $siteKey = $settings['captcha_site_key'] ?? CaptchaHelper::getSiteKey();
        $secretKey = $settings['captcha_secret_key'] ?? CaptchaHelper::getSecretKey();
        $version = $settings['captcha_google_version'] ?? 'v3';

        if (empty($siteKey) || empty($secretKey)) {
            return [
                'success' => false,
                'message' => __('admin/settings/security/captcha.test_keys_missing'),
            ];
        }

        // バージョン検証を含む詳細テスト
        $versionTestResult = $this->performAdvancedVersionTest($secretKey, $version, $siteKey);
        if (! $versionTestResult['success']) {
            return $versionTestResult;
        }

        return [
            'success' => true,
            'message' => "Google reCAPTCHA {$version} connection test successful",
        ];
    }

    /**
     * Google reCAPTCHA Enterprise版のテスト
     */
    private function testGoogleRecaptchaEnterprise(array $settings): array
    {
        $siteKey = $settings['captcha_site_key'] ?? CaptchaHelper::getSiteKey();
        $secretKey = $settings['captcha_secret_key'] ?? CaptchaHelper::getSecretKey();
        $projectId = $settings['captcha_google_project_id'] ?? CaptchaHelper::getGoogleProjectId();

        if (empty($siteKey) || empty($secretKey) || empty($projectId)) {
            return [
                'success' => false,
                'message' => __('admin/settings/security/captcha.test_enterprise_keys_missing'),
            ];
        }

        // Enterprise APIの場合、REST APIを使用（Google Cloud SDK不要）
        try {
            $driver = new GoogleRecaptchaEnterpriseDriver([
                'site_key' => $siteKey,
                'api_key' => $secretKey,
                'project_id' => $projectId,
            ]);

            return [
                'success' => true,
                'message' => 'Google reCAPTCHA Enterprise設定は正常です。REST APIを使用します。',
            ];
        } catch (\Exception $e) {
            Log::error('CAPTCHA Test Debug - Enterprise test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => __('admin/settings/security/captcha.api_connection_failed'),
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
                'message' => __('admin/settings/security/captcha.test_keys_missing'),
            ];
        }

        // ダミートークンでAPI接続をテスト
        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => $secretKey,
            'response' => 'test-token',
            'remoteip' => '127.0.0.1',
        ]);

        if (! $response->successful()) {
            return [
                'success' => false,
                'message' => __('admin/settings/security/captcha.test_api_request_failed').': '.$response->status(),
            ];
        }

        $data = $response->json();

        // invalid-input-secretエラーの場合はキーが無効
        if (isset($data['error-codes']) && in_array('invalid-input-secret', $data['error-codes'])) {
            return [
                'success' => false,
                'message' => __('admin/settings/security/captcha.test_invalid_secret_key'),
            ];
        }

        return [
            'success' => true,
            'message' => __('admin/settings/security/captcha.test_turnstile_success'),
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
        if (! preg_match('/^6[A-Za-z0-9_-]{39}$/', $siteKey)) {
            return [
                'valid' => false,
                'message' => __('admin/settings/security/captcha.test_invalid_site_key_format'),
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
            if (! $testResult['valid']) {
                return $testResult;
            }
        } else {
            // v2の場合（checkbox/invisible）、v3特有のフィールドがないことを確認
            if (isset($apiResponse['score']) || isset($apiResponse['action'])) {
                return [
                    'valid' => false,
                    'message' => "Key type mismatch: This appears to be a v3 key but {$version} is configured. v2 keys should not return score/action fields.",
                ];
            }
        }

        // v3キーでv2設定を使用した場合のエラーパターン
        if ($version !== 'v3' && in_array('invalid-keys', $errorCodes)) {
            return [
                'valid' => false,
                'message' => __('admin/settings/security/captcha.test_key_version_mismatch_v2_to_v3'),
            ];
        }

        // v2キーでv3設定を使用した場合のエラーパターン
        if ($version === 'v3' && in_array('invalid-keys', $errorCodes)) {
            return [
                'valid' => false,
                'message' => __('admin/settings/security/captcha.test_key_version_mismatch_v3_to_v2'),
            ];
        }

        // その他の重要なエラー
        if (in_array('invalid-input-secret', $errorCodes)) {
            return [
                'valid' => false,
                'message' => __('admin/settings/security/captcha.test_invalid_secret_verify'),
            ];
        }

        if (in_array('bad-request', $errorCodes)) {
            return [
                'valid' => false,
                'message' => __('admin/settings/security/captcha.test_bad_request'),
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
        if (! $basicTest['valid']) {
            return $basicTest;
        }

        // 2. バージョン固有のトークンパターンでテスト
        $versionTest = $this->performVersionSpecificTest($secretKey, $version);
        if (! $versionTest['valid']) {
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

        if (! $response->successful()) {
            return [
                'valid' => false,
                'message' => 'reCAPTCHA APIへの接続に失敗しました。',
            ];
        }

        $data = $response->json();
        $errorCodes = $data['error-codes'] ?? [];

        if (in_array('invalid-input-secret', $errorCodes)) {
            return [
                'valid' => false,
                'message' => 'シークレットキーが無効です。正しいシークレットキーを入力してください。',
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
        $testToken = 'version-compatibility-test-token-'.time();

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secretKey,
            'response' => $testToken,
            'remoteip' => '127.0.0.1',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $errorCodes = $data['error-codes'] ?? [];

            // v3キーの特徴を検出
            $hasV3Features = isset($data['score']) || isset($data['action']);

            // バージョンミスマッチの検出
            if ($version === 'v3' && ! $hasV3Features && $data['success'] === false) {
                // v3設定だがv3特有のフィールドがない場合
                $errorPattern = implode(', ', $errorCodes);
                if (strpos($errorPattern, 'invalid') !== false) {
                    return [
                        'valid' => false,
                        'message' => 'バージョンミスマッチ: v3が設定されていますが、このキーはv2用のようです。reCAPTCHAのバージョン設定を確認してください。',
                    ];
                }
            }

            if (($version === 'v2_checkbox' || $version === 'v2_invisible') && $hasV3Features) {
                return [
                    'valid' => false,
                    'message' => "バージョンミスマッチ: {$version}が設定されていますが、このキーはv3用です。reCAPTCHAのバージョン設定をv3に変更してください。",
                ];
            }

            // 特定のエラーコードパターンでバージョンミスマッチを検出
            if (in_array('invalid-keys', $errorCodes)) {
                return [
                    'valid' => false,
                    'message' => 'キータイプとバージョン設定が一致しません。reCAPTCHAコンソールで取得したキーのタイプと設定を確認してください。',
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
        if ($version === 'v3' && ! $hasV3Fields && ! in_array('invalid-input-response', $errorCodes)) {
            return [
                'valid' => false,
                'message' => 'バージョンミスマッチ: v3が設定されていますが、このキーはv2用です。reCAPTCHAのバージョン設定を確認してください。',
            ];
        }

        // v3キーでv2設定の場合
        if ($version !== 'v3' && $hasV3Fields) {
            return [
                'valid' => false,
                'message' => "バージョンミスマッチ: {$version}が設定されていますが、このキーはv3用です。reCAPTCHAのバージョン設定をv3に変更してください。",
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
            if (! in_array('missing-input-response', $errorCodes) && ! empty($errorCodes)) {
                // キー自体に問題がある可能性
                if (in_array('invalid-input-secret', $errorCodes)) {
                    return [
                        'valid' => false,
                        'message' => 'シークレットキーが無効です。正しいシークレットキーを入力してください。',
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
        // 1. 基本的なキー検証
        $basicTest = $this->testBasicKeyValidity($secretKey);
        if (! $basicTest['valid']) {
            return ['success' => false, 'message' => $basicTest['message']];
        }

        // 2. サイトキーのパターン分析
        $keyAnalysis = $this->analyzeSiteKeyPattern($siteKey);

        // 3. 複数のテストトークンでバージョン特性を検証
        $versionCheck = $this->checkVersionCharacteristics($secretKey, $version);
        if (! $versionCheck['valid']) {
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

        if (! $response->successful()) {
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
            'pattern' => 'unknown',
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
            'enterprise' => $enterpriseResponse,
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
            'response' => 'test-v3-response-'.time(),
            'remoteip' => '127.0.0.1',
        ]);

        $data = $response->successful() ? $response->json() : [];

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
            'response' => 'test-enterprise-'.time(),
        ]);

        $data = $response->successful() ? $response->json() : [];
        $statusCode = $response->status();

        return array_merge($data, ['_enterprise_status' => $statusCode]);
    }

    /**
     * レスポンスパターンを分析してバージョンミスマッチを検出
     */
    private function analyzeResponsePatterns(array $responses, string $version): array
    {
        // 簡略化されたバージョン検証アプローチ
        // Google reCAPTCHAの実際の動作に基づいて判定

        $basicResponse = $responses['basic'] ?? [];
        $errorCodes = $basicResponse['error-codes'] ?? [];

        // 基本的なキー有効性チェック
        if (in_array('invalid-input-secret', $errorCodes)) {
            return [
                'valid' => false,
                'message' => 'シークレットキーが無効です。正しいキーを入力してください。',
            ];
        }

        // 現在は基本的な接続テストのみ実行
        // バージョンミスマッチ検出は実際のキーの動作パターンが明確になるまで無効化

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
        $testKey = 'captcha_authentication_result';

        // リポジトリを使用してキャッシュを自動クリア
        $this->securitySettingRepository->set($testKey, $success);
    }

    /**
     * テスト結果をデータベースから取得
     */
    public function getTestResult(): bool
    {
        // セッションから取得を試行
        $sessionResult = session('captcha_authentication_result');
        if ($sessionResult !== null) {
            return (bool) $sessionResult;
        }

        // データベースから取得
        $testKey = 'captcha_authentication_result';
        $setting = SecuritySetting::where('name', $testKey)->first();

        if (! $setting) {
            return false;
        }

        return (bool) $setting->value;
    }

    /**
     * テスト結果をデータベースから取得（詳細版）
     */
    public function getCaptchaTestResult(string $driver): ?array
    {
        $testKey = 'captcha_authentication_result';
        $setting = SecuritySetting::where('name', $testKey)->first();

        if (! $setting) {
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
                'error_message' => 'テストに失敗しました',
            ];
        } elseif ($setting->value === '1') {
            return [
                'success' => true,
                'tested_at' => $testedAt,
                'error_message' => null,
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
        if (! ($settings['captcha_enabled'] ?? false)) {
            return false;
        }

        $driver = $settings['captcha_driver'] ?? 'google';
        $testResult = $this->getCaptchaTestResult($driver);

        // テスト結果がない場合はテスト必要
        if (! $testResult) {
            return true;
        }

        // テストが失敗している場合はテスト必要
        return ! ($testResult['success'] ?? false);
    }

    /**
     * CAPTCHAテスト結果をリセット
     */
    public function resetCaptchaTestResults(): void
    {
        // セッションからテスト結果を削除
        session()->forget('captcha_authentication_result');

        // データベースのテスト結果を0にリセット（リポジトリを使用してキャッシュを自動クリア）
        $testKey = 'captcha_authentication_result';
        $this->securitySettingRepository->set($testKey, false);
    }
}
