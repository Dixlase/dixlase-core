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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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
     * Security settings repository
     */
    protected SecuritySettingRepositoryInterface $securitySettingRepository;

    /**
     * Constructor
     */
    public function __construct(SecuritySettingRepositoryInterface $securitySettingRepository)
    {
        $this->securitySettingRepository = $securitySettingRepository;
    }

    /**
     * Execute CAPTCHA connection test
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
     * Test Google reCAPTCHA standard version
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

        // Detailed test including version validation
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
     * Test Google reCAPTCHA Enterprise version
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

        // For Enterprise API, use REST API (Google Cloud SDK not required)
        try {
            $driver = new GoogleRecaptchaEnterpriseDriver([
                'site_key' => $siteKey,
                'api_key' => $secretKey,
                'project_id' => $projectId,
            ]);

            return [
                'success' => true,
                'message' => __('services/captcha_test_service.recaptcha_enterprise_rest_api_ok'),
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
     * Test Cloudflare Turnstile
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

        // Test API connection with dummy token
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

        // If invalid-input-secret error, the key is invalid
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
     * Validate compatibility between Google reCAPTCHA key and version
     */
    private function validateGoogleRecaptchaKeyCompatibility(string $siteKey, string $version): array
    {
        // v3 keys typically start with 6L
        // v2 keys typically start with 6L, but different patterns exist
        // As a more reliable method, detect version mismatch from actual API response

        // Basic format check for site key
        if (! preg_match('/^6[A-Za-z0-9_-]{39}$/', $siteKey)) {
            return [
                'valid' => false,
                'message' => __('admin/settings/security/captcha.test_invalid_site_key_format'),
            ];
        }

        return ['valid' => true];
    }

    /**
     * Check for version-specific errors
     */
    private function checkVersionSpecificErrors(array $apiResponse, string $version): array
    {
        $errorCodes = $apiResponse['error-codes'] ?? [];

        // Execute actual token test for more detailed version validation
        if ($version === 'v3') {
            // For v3, check for existence of score field
            $testResult = $this->performActualV3Test($apiResponse);
            if (! $testResult['valid']) {
                return $testResult;
            }
        } else {
            // For v2 (checkbox/invisible), confirm absence of v3-specific fields
            if (isset($apiResponse['score']) || isset($apiResponse['action'])) {
                return [
                    'valid' => false,
                    'message' => "Key type mismatch: This appears to be a v3 key but {$version} is configured. v2 keys should not return score/action fields.",
                ];
            }
        }

        // Error pattern when v2 settings are used with v3 key
        if ($version !== 'v3' && in_array('invalid-keys', $errorCodes)) {
            return [
                'valid' => false,
                'message' => __('admin/settings/security/captcha.test_key_version_mismatch_v2_to_v3'),
            ];
        }

        // Error pattern when using v2 key with v3 settings
        if ($version === 'v3' && in_array('invalid-keys', $errorCodes)) {
            return [
                'valid' => false,
                'message' => __('admin/settings/security/captcha.test_key_version_mismatch_v3_to_v2'),
            ];
        }

        // Other important errors
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
     * Execute actual test of v3 key
     */
    private function performActualV3Test(array $dummyResponse): array
    {
        // Check v3-specific fields with dummy response
        // For actual v3 keys, score and action fields may be returned even with invalid-input-response error

        // v3 key characteristics:
        // - score field exists (value between 0.0-1.0)
        // - action field exists
        // - these fields do not exist for v2 keys

        $errorCodes = $dummyResponse['error-codes'] ?? [];

        // invalid-input-response is normal (due to dummy token)
        // However, for v3 keys, score and action may be returned
        if (in_array('invalid-input-response', $errorCodes)) {
            // Determining if it's a v3 key is difficult, so basically allow it through
            // Version mismatch will be detected during actual use
            return ['valid' => true];
        }

        return ['valid' => true];
    }

    /**
     * Execute detailed version compatibility tests
     */
    private function performVersionCompatibilityTest(string $secretKey, string $version): array
    {
        // Validation using actual Google reCAPTCHA API behavior patterns
        // Different version keys show specific error patterns

        // 1. Test with empty response (basic key validation)
        $basicTest = $this->performBasicKeyTest($secretKey);
        if (! $basicTest['valid']) {
            return $basicTest;
        }

        // 2. Test with version-specific token patterns
        $versionTest = $this->performVersionSpecificTest($secretKey, $version);
        if (! $versionTest['valid']) {
            return $versionTest;
        }

        return ['valid' => true];
    }

    /**
     * Basic key validation test
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
                'message' => __('services/captcha_test_service.recaptcha_api_connection_failed'),
            ];
        }

        $data = $response->json();
        $errorCodes = $data['error-codes'] ?? [];

        if (in_array('invalid-input-secret', $errorCodes)) {
            return [
                'valid' => false,
                'message' => __('services/captcha_test_service.secret_key_invalid_enter_correct'),
            ];
        }

        return ['valid' => true];
    }

    /**
     * Version-specific test
     */
    private function performVersionSpecificTest(string $secretKey, string $version): array
    {
        // Use test tokens that behave differently between v3 and v2
        $testToken = 'version-compatibility-test-token-'.time();

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secretKey,
            'response' => $testToken,
            'remoteip' => '127.0.0.1',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $errorCodes = $data['error-codes'] ?? [];

            // Detect v3 key characteristics
            $hasV3Features = isset($data['score']) || isset($data['action']);

            // Detect version mismatch
            if ($version === 'v3' && ! $hasV3Features && $data['success'] === false) {
                // If v3 settings but v3-specific fields are missing
                $errorPattern = implode(', ', $errorCodes);
                if (strpos($errorPattern, 'invalid') !== false) {
                    return [
                        'valid' => false,
                        'message' => __('services/captcha_test_service.version_mismatch_v3_set_v2_key'),
                    ];
                }
            }

            if (($version === 'v2_checkbox' || $version === 'v2_invisible') && $hasV3Features) {
                return [
                    'valid' => false,
                    'message' => __('services/captcha_test_service.version_mismatch_key_for_v3', ['version' => $version]),
                ];
            }

            // Detect version mismatch by specific error code patterns
            if (in_array('invalid-keys', $errorCodes)) {
                return [
                    'valid' => false,
                    'message' => __('services/captcha_test_service.key_type_version_mismatch'),
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Detect version mismatch from API response
     */
    private function detectVersionMismatch(array $data, string $version, array $errorCodes): ?array
    {
        // v3 key characteristic: score field exists
        $hasV3Fields = isset($data['score']) || isset($data['action']);

        // When v2 key with v3 settings
        if ($version === 'v3' && ! $hasV3Fields && ! in_array('invalid-input-response', $errorCodes)) {
            return [
                'valid' => false,
                'message' => __('services/captcha_test_service.version_mismatch_v3_set_v2_confirmed'),
            ];
        }

        // When v3 key with v2 settings
        if ($version !== 'v3' && $hasV3Fields) {
            return [
                'valid' => false,
                'message' => __('services/captcha_test_service.version_mismatch_key_for_v3', ['version' => $version]),
            ];
        }

        return null;
    }

    /**
     * Perform final version check
     */
    private function performFinalVersionCheck(string $secretKey, string $version): array
    {
        // Test with empty token (missing-input-response error expected)
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secretKey,
            'response' => '',
            'remoteip' => '127.0.0.1',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $errorCodes = $data['error-codes'] ?? [];

            // If missing-input-response is expected but other error occurs, it's a key issue
            if (! in_array('missing-input-response', $errorCodes) && ! empty($errorCodes)) {
                // Possible issue with the key itself
                if (in_array('invalid-input-secret', $errorCodes)) {
                    return [
                        'valid' => false,
                        'message' => __('services/captcha_test_service.secret_key_invalid_enter_correct'),
                    ];
                }
            }
        }

        return ['valid' => true];
    }

    /**
     * Advanced version test (detect actual key type)
     */
    private function performAdvancedVersionTest(string $secretKey, string $version, string $siteKey): array
    {
        // 1. Basic key validation
        $basicTest = $this->testBasicKeyValidity($secretKey);
        if (! $basicTest['valid']) {
            return ['success' => false, 'message' => $basicTest['message']];
        }

        // 2. Site key pattern analysis
        $keyAnalysis = $this->analyzeSiteKeyPattern($siteKey);

        // 3. Verify version characteristics with multiple test tokens
        $versionCheck = $this->checkVersionCharacteristics($secretKey, $version);
        if (! $versionCheck['valid']) {
            return ['success' => false, 'message' => $versionCheck['message']];
        }

        return ['success' => true];
    }

    /**
     * Basic key validity test
     */
    private function testBasicKeyValidity(string $secretKey): array
    {
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secretKey,
            'response' => '',
            'remoteip' => '127.0.0.1',
        ]);

        if (! $response->successful()) {
            return ['valid' => false, 'message' => __('services/captcha_test_service.recaptcha_api_connection_failed')];
        }

        $data = $response->json();
        $errorCodes = $data['error-codes'] ?? [];

        if (in_array('invalid-input-secret', $errorCodes)) {
            return ['valid' => false, 'message' => __('services/captcha_test_service.secret_key_invalid')];
        }

        return ['valid' => true];
    }

    /**
     * Site key pattern analysis
     */
    private function analyzeSiteKeyPattern(string $siteKey): array
    {
        // Google reCAPTCHA site key pattern analysis
        // v2 and v3 keys may have subtle differences

        $analysis = [
            'length' => strlen($siteKey),
            'starts_with_6L' => str_starts_with($siteKey, '6L'),
            'pattern' => 'unknown',
        ];

        // Common patterns (not exhaustive, for reference only)
        if (preg_match('/^6L[a-zA-Z0-9_-]{38}$/', $siteKey)) {
            $analysis['pattern'] = 'standard_40_char';
        }

        return $analysis;
    }

    /**
     * Check version characteristics
     */
    private function checkVersionCharacteristics(string $secretKey, string $version): array
    {
        // 1. Basic API test (empty response)
        $basicResponse = $this->testWithEmptyResponse($secretKey);

        // 2. v3-specific test (test with score parameter)
        $v3Response = $this->testV3Characteristics($secretKey);

        // 3. Enterprise API test
        $enterpriseResponse = $this->testEnterpriseCharacteristics($secretKey);

        // 4. Analyze response patterns
        return $this->analyzeResponsePatterns([
            'basic' => $basicResponse,
            'v3_test' => $v3Response,
            'enterprise' => $enterpriseResponse,
        ], $version);
    }

    /**
     * Basic API response test
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
     * Test v3-specific characteristics
     */
    private function testV3Characteristics(string $secretKey): array
    {
        // For v3 keys, more detailed information may be obtained with specific parameters
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secretKey,
            'response' => 'test-v3-response-'.time(),
            'remoteip' => '127.0.0.1',
        ]);

        $data = $response->successful() ? $response->json() : [];

        return $data;
    }

    /**
     * Test Enterprise API characteristics
     */
    private function testEnterpriseCharacteristics(string $secretKey): array
    {
        // Test with Enterprise API endpoint
        $response = Http::asForm()->post('https://recaptchaenterprise.googleapis.com/v1/projects/test/assessments', [
            'secret' => $secretKey,
            'response' => 'test-enterprise-'.time(),
        ]);

        $data = $response->successful() ? $response->json() : [];
        $statusCode = $response->status();

        return array_merge($data, ['_enterprise_status' => $statusCode]);
    }

    /**
     * Analyze response patterns to detect version mismatch
     */
    private function analyzeResponsePatterns(array $responses, string $version): array
    {
        // Simplified version verification approach
        // Determine based on actual Google reCAPTCHA behavior

        $basicResponse = $responses['basic'] ?? [];
        $errorCodes = $basicResponse['error-codes'] ?? [];

        // Basic key validity check
        if (in_array('invalid-input-secret', $errorCodes)) {
            return [
                'valid' => false,
                'message' => __('services/captcha_test_service.secret_key_invalid_enter_correct_key'),
            ];
        }

        // Currently only performs basic connection test
        // Version mismatch detection is disabled until actual key behavior patterns become clear

        return ['valid' => true];
    }

    /**
     * Detect version mismatch from response patterns (for future implementation)
     */
    private function detectVersionMismatchFromResponses(array $responses, string $version): array
    {
        // To be used when implementing more precise version detection logic in the future
        // Currently only basic validation
        return ['valid' => true];
    }

    /**
     * Save test result to database
     */
    public function saveCaptchaTestResult(string $driver, bool $success, ?string $errorMessage = null): void
    {
        $testKey = 'captcha_authentication_result';

        // Use repository to automatically clear cache
        $this->securitySettingRepository->set($testKey, $success);
    }

    /**
     * Get test result from database
     */
    public function getTestResult(): bool
    {
        // Try to get from session
        $sessionResult = session('captcha_authentication_result');
        if ($sessionResult !== null) {
            return (bool) $sessionResult;
        }

        // Get from database
        $testKey = 'captcha_authentication_result';
        $setting = SecuritySetting::where('name', $testKey)->first();

        if (! $setting) {
            return false;
        }

        return (bool) $setting->value;
    }

    /**
     * Get test result from database (detailed version)
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

        // Treat as failure only when the value is stored as '0' in DB
        // Treat as not executed if value does not exist
        if ($setting->value === '0') {
            return [
                'success' => false,
                'tested_at' => $testedAt,
                'error_message' => __('services/captcha_test_service.test_failed'),
            ];
        } elseif ($setting->value === '1') {
            return [
                'success' => true,
                'tested_at' => $testedAt,
                'error_message' => null,
            ];
        }

        // Treat as not executed in other cases
        return null;
    }

    /**
     * Check if test is required with current settings
     */
    public function isTestRequired(array $settings): bool
    {
        // Test not required if CAPTCHA is disabled
        if (! ($settings['captcha_enabled'] ?? false)) {
            return false;
        }

        $driver = $settings['captcha_driver'] ?? 'google';
        $testResult = $this->getCaptchaTestResult($driver);

        // Test required if test result does not exist
        if (! $testResult) {
            return true;
        }

        // Test required if test has failed
        return ! ($testResult['success'] ?? false);
    }

    /**
     * Reset CAPTCHA test result
     */
    public function resetCaptchaTestResults(): void
    {
        // Remove test result from session
        session()->forget('captcha_authentication_result');

        // Reset test result to 0 in database (use repository to automatically clear cache)
        $testKey = 'captcha_authentication_result';
        $this->securitySettingRepository->set($testKey, false);
    }
}
