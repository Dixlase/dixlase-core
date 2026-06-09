<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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

namespace App\Http\Controllers\Admin\Settings\Security;

use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\Helpers\AdminModeHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Security\AdminSecurityCaptchaUpdateRequest;
use App\Services\CaptchaService;
use App\Services\CaptchaTestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminSecurityCaptchaController extends AdminLoggedInController
{
    protected const SETTING_KEYS = ['captcha_enabled', 'captcha_driver', 'captcha_google_version', 'captcha_google_min_score', 'captcha_google_project_id', 'captcha_google_site_key', 'captcha_google_secret_key', 'captcha_google_enterprise_site_key', 'captcha_google_enterprise_secret_key', 'captcha_turnstile_site_key', 'captcha_turnstile_secret_key'];

    protected const SENSITIVE_KEYS = ['captcha_google_secret_key', 'captcha_google_enterprise_secret_key', 'captcha_turnstile_secret_key'];

    protected SecuritySettingRepositoryInterface $securitySettingRepository;

    protected CaptchaService $captchaService;

    public function __construct(
        SecuritySettingRepositoryInterface $securitySettingRepository,
        CaptchaService $captchaService
    ) {
        parent::__construct();
        $this->securitySettingRepository = $securitySettingRepository;
        $this->captchaService = $captchaService;
    }

    /**
     * CAPTCHA settings page
     */
    public function index()
    {
        $currentDriver = $this->securitySettingRepository->get('captcha_driver', 'google');

        // Get keys for each provider
        $providerKeys = $this->getProviderKeys();

        $settings = [
            'captcha_enabled' => filter_var($this->securitySettingRepository->get('captcha_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'captcha_driver' => $currentDriver,
            // Set current provider's keys for display
            'captcha_site_key' => $providerKeys[$currentDriver]['site_key'] ?? '',
            'captcha_secret_key' => $providerKeys[$currentDriver]['secret_key'] ?? '',
            'captcha_google_version' => $this->securitySettingRepository->get('captcha_google_version', 'v3'),
            'captcha_google_min_score' => $this->securitySettingRepository->get('captcha_google_min_score', '0.5'),
            'captcha_google_project_id' => $this->securitySettingRepository->get('captcha_google_project_id', ''),
            // All provider keys (for JavaScript)
            'provider_keys' => $providerKeys,
        ];

        // Get CAPTCHA test results
        $captchaTestService = app(CaptchaTestService::class);

        // Clear session and load from DB if there are no validation errors
        if (! session()->has('errors') || ! session('errors')->any()) {
            session()->forget('captcha_authentication_result');
        }

        $captchaTestResult = $captchaTestService->getTestResult();
        $captchaTestDetails = $captchaTestService->getCaptchaTestResult($settings['captcha_driver']);

        // Get all forms and preserve keys
        $allForms = $this->captchaService->getAllForms();
        $enabledForms = [];
        $formsByCategory = [];

        // Group by category while preserving original keys
        foreach ($allForms as $formKey => $form) {
            $category = $form['category'] ?? 'other';
            if (! isset($formsByCategory[$category])) {
                $formsByCategory[$category] = [];
            }
            $formsByCategory[$category][$formKey] = $form;
            $enabledForms[$formKey] = $this->captchaService->isEnabled($formKey);
        }

        $this->viewParams['settings'] = $settings;
        $this->viewParams['captchaTestResult'] = $captchaTestResult;
        $this->viewParams['captchaTestDetails'] = $captchaTestDetails;
        $this->viewParams['formsByCategory'] = $formsByCategory;
        $this->viewParams['enabledForms'] = $enabledForms;
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.security.captcha');

        return view('admin.settings.security.captcha', $this->viewParams);
    }

    /**
     * Update CAPTCHA settings
     */
    public function update(AdminSecurityCaptchaUpdateRequest $request)
    {
        $actor = new \App\Actors\MemberActor(\App\Helpers\AdminHelper::getMember());

        $data = $request->validated();
        $data['captcha_authentication_result'] = $request->boolean('captcha_authentication_result');

        (new \App\Actions\Security\UpdateCaptchaSettingsAction(
            $this->securitySettingRepository,
            $this->captchaService,
        ))->execute($actor, $data);

        return redirect()->route('admin.settings.security.captcha')
            ->with('success', __('admin/settings/security/captcha.settings_updated'));
    }

    /**
     * Verify CAPTCHA widget
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
            // Prioritize values submitted from form (for testing before saving)
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
                // Save test results
                $captchaTestService = app(CaptchaTestService::class);
                $captchaTestService->saveCaptchaTestResult($driver, true);

                return response()->json([
                    'success' => true,
                    'message' => __('admin/settings/security/captcha.validation_success_with_score', [
                        'score' => $result['score'] ?? 'N/A',
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
     * Clear CAPTCHA test results
     */
    public function clearTest()
    {
        $captchaTestService = app(CaptchaTestService::class);
        $captchaTestService->resetCaptchaTestResults();

        return response()->json(['success' => true]);
    }

    /**
     * Verify CAPTCHA token
     */
    protected function verifyCaptchaToken(string $token, string $secretKey, string $driver, float $minScore, ?string $siteKey = null, ?string $projectId = null): array
    {
        // Google reCAPTCHA Enterprise uses a different API
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

        if (! ($data['success'] ?? false)) {
            return [
                'success' => false,
                'message' => __('admin/settings/security/captcha.validation_failed_with_errors', [
                    'errors' => implode(', ', $data['error-codes'] ?? ['unknown']),
                ]),
            ];
        }

        // Check score for v3
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
     * Verify Google reCAPTCHA Enterprise token
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

        // Google reCAPTCHA Enterprise API call
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("https://recaptchaenterprise.googleapis.com/v1/projects/{$projectId}/assessments?key={$apiKey}", [
            'event' => [
                'token' => $token,
                'siteKey' => $siteKey ?? '',
            ],
        ]);

        if (! $response->successful()) {
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

        if (! ($data['tokenProperties']['valid'] ?? false)) {
            $reasons = $data['tokenProperties']['invalidReason'] ?? 'unknown';

            return [
                'success' => false,
                'message' => __('admin/settings/security/captcha.validation_failed_with_errors', [
                    'errors' => is_array($reasons) ? implode(', ', $reasons) : $reasons,
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
     * Get keys for each provider
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
}
