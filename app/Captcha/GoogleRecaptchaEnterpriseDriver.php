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

namespace App\Captcha;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\CaptchaHelper;

class GoogleRecaptchaEnterpriseDriver implements CaptchaDriver
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'site_key' => CaptchaHelper::getSiteKey(),
            'api_key' => CaptchaHelper::getSecretKey(), // Enterprise uses API Key
            'version' => 'v3', // Enterprise always uses v3
            'min_score' => CaptchaHelper::getGoogleMinScore(),
            'project_id' => CaptchaHelper::getGoogleProjectId(),
        ], $config);
        
        Log::info('GoogleRecaptchaEnterpriseDriver constructor', [
            'site_key' => substr($this->config['site_key'], 0, 10) . '...',
            'api_key' => substr($this->config['api_key'], 0, 10) . '...',
            'project_id' => $this->config['project_id']
        ]);
    }

    public function renderScript(): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        $siteKey = $this->config['site_key'];
        return "<script src=\"https://www.google.com/recaptcha/enterprise.js?render={$siteKey}\"></script>";
    }

    public function renderWidget(array $options = []): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        $siteKey = $this->config['site_key'];
        $action = $options['action'] ?? 'submit';
        $scriptTag = $this->renderScript();
        
        Log::info('GoogleRecaptchaEnterpriseDriver renderWidget', [
            'siteKey' => $siteKey,
            'action' => $action
        ]);
        
        return $scriptTag . "
            <input type=\"hidden\" id=\"g-recaptcha-response\" name=\"g-recaptcha-response\" value=\"\">
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof grecaptcha !== 'undefined' && grecaptcha.enterprise) {
                        grecaptcha.enterprise.ready(function() {
                            grecaptcha.enterprise.execute('$siteKey', {action: '$action'}).then(function(token) {
                                document.getElementById('g-recaptcha-response').value = token;
                            });
                        });
                    } else {
                        console.error('reCAPTCHA Enterprise script not loaded properly');
                    }
                });
            </script>
        ";
    }

    public function verify(Request $request): CaptchaResult
    {
        if (!$this->isEnabled()) {
            return new CaptchaResult(true, null, null, [], ['bypass' => true]);
        }

        $token = $request->input('g-recaptcha-response');
        
        if (empty($token)) {
            return new CaptchaResult(false, null, null, ['captcha' => 'reCAPTCHA response is required']);
        }

        return $this->verifyWithEnterpriseAPI($request, $token);
    }

    protected function verifyWithEnterpriseAPI(Request $request, string $token): CaptchaResult
    {
        try {
            Log::info('reCAPTCHA Enterprise verification attempt', [
                'project_id' => $this->config['project_id'],
                'site_key' => substr($this->config['site_key'], 0, 10) . '...',
                'api_key' => substr($this->config['api_key'], 0, 10) . '...'
            ]);
            
            // Enterprise REST APIを使用（APIキーベース認証）
            $apiUrl = "https://recaptchaenterprise.googleapis.com/v1/projects/{$this->config['project_id']}/assessments?key={$this->config['api_key']}";
            
            $payload = [
                'event' => [
                    'token' => $token,
                    'siteKey' => $this->config['site_key'],
                    'userIpAddress' => $request->ip(),
                    'userAgent' => $request->userAgent() ?? '',
                    'expectedAction' => 'admin_login'
                ]
            ];

            Log::info('reCAPTCHA Enterprise API call', [
                'api_url' => preg_replace('/key=[^&]+/', 'key=***', $apiUrl),
                'site_key' => substr($this->config['site_key'], 0, 10) . '...',
                'token_length' => strlen($token),
                'user_ip' => $request->ip()
            ]);
            
            $httpResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($apiUrl, $payload);
            
            if (!$httpResponse->successful()) {
                Log::error('reCAPTCHA Enterprise API HTTP error', [
                    'status' => $httpResponse->status(),
                    'body' => $httpResponse->body(),
                    'ip' => $request->ip()
                ]);
                
                throw new \Exception('Enterprise API request failed: ' . $httpResponse->body());
            }
            
            $result = $httpResponse->json();
            
            Log::info('reCAPTCHA Enterprise API response', [
                'token_valid' => $result['tokenProperties']['valid'] ?? false,
                'score' => $result['riskAnalysis']['score'] ?? null,
                'action' => $result['tokenProperties']['action'] ?? null,
                'invalid_reason' => ($result['tokenProperties']['valid'] ?? false) ? null : ($result['tokenProperties']['invalidReason'] ?? 'unknown')
            ]);

            if (!($result['tokenProperties']['valid'] ?? false)) {
                Log::warning('reCAPTCHA Enterprise verification failed', [
                    'reason' => $result['tokenProperties']['invalidReason'] ?? 'unknown',
                    'ip' => $request->ip(),
                ]);

                return new CaptchaResult(
                    false,
                    null,
                    null,
                    ['captcha' => 'reCAPTCHA verification failed'],
                    ['invalid_reason' => $result['tokenProperties']['invalidReason'] ?? 'unknown']
                );
            }

            $score = $result['riskAnalysis']['score'] ?? 0;
            $action = $result['tokenProperties']['action'] ?? null;

            if ($score < $this->config['min_score']) {
                Log::warning('reCAPTCHA Enterprise score too low', [
                    'score' => $score,
                    'min_score' => $this->config['min_score'],
                    'action' => $action,
                    'ip' => $request->ip(),
                ]);

                return new CaptchaResult(
                    false,
                    $score,
                    $action,
                    ['captcha' => 'reCAPTCHA score too low'],
                    ['score' => $score, 'min_score' => $this->config['min_score']]
                );
            }

            return new CaptchaResult(true, $score, $action, [], ['score' => $score]);

        } catch (\Exception $e) {
            Log::error('reCAPTCHA Enterprise verification error', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'error_code' => $e->getCode(),
                'ip' => $request->ip(),
                'project_id' => $this->config['project_id'] ?? 'not_set',
                'site_key' => substr($this->config['site_key'] ?? '', 0, 10) . '...',
                'token_length' => strlen($token)
            ]);

            // 障害時の挙動を設定から取得
            $onFailure = config('security.external_services.captcha_on_failure', 'fail_closed');
            
            if ($onFailure === 'fail_open') {
                Log::warning('CAPTCHA verification failed but fail_open is configured, allowing request', [
                    'ip' => $request->ip(),
                ]);
                return new CaptchaResult(
                    true,
                    null,
                    null,
                    [],
                    ['bypass' => true, 'reason' => 'fail_open_on_error', 'exception' => $e->getMessage()]
                );
            }

            return new CaptchaResult(
                false,
                null,
                null,
                ['captcha' => 'reCAPTCHA verification error: ' . $e->getMessage()],
                ['exception' => $e->getMessage(), 'exception_class' => get_class($e)]
            );
        }
    }

    public function rules(): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        return [
            'g-recaptcha-response' => 'required|string',
        ];
    }

    public function isEnabled(): bool
    {
        return CaptchaHelper::isEnabled() && CaptchaHelper::getDriver() === 'google_enterprise';
    }
}
