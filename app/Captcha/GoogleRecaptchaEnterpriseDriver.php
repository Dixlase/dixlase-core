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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

namespace App\Captcha;

use App\Helpers\CaptchaHelper;
use App\Services\CaptchaFailoverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
    }

    public function renderScript(): string
    {
        if (! $this->isEnabled()) {
            return '';
        }

        // Get CSP nonce if available
        $nonce = '';
        if (function_exists('csp_nonce')) {
            $nonceValue = csp_nonce();
            $nonce = $nonceValue ? ' nonce="'.$nonceValue.'"' : '';
        }

        $siteKey = $this->config['site_key'];

        return "<script src=\"https://www.google.com/recaptcha/enterprise.js?render={$siteKey}\"{$nonce}></script>";
    }

    public function renderWidget(array $options = []): string
    {
        if (! $this->isEnabled()) {
            return '';
        }

        $siteKey = $this->config['site_key'];
        $action = $options['action'] ?? 'submit';
        $scriptTag = $this->renderScript();

        // Get CSP nonce for inline script
        $nonce = '';
        if (function_exists('csp_nonce')) {
            $nonceValue = csp_nonce();
            $nonce = $nonceValue ? ' nonce="'.$nonceValue.'"' : '';
        }

        return $scriptTag."
            <input type=\"hidden\" id=\"g-recaptcha-response\" name=\"g-recaptcha-response\" value=\"\">
            <script{$nonce}>
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
        if (! $this->isEnabled()) {
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
            // Enterprise REST APIを使用（APIキーベース認証）
            $apiUrl = "https://recaptchaenterprise.googleapis.com/v1/projects/{$this->config['project_id']}/assessments?key={$this->config['api_key']}";

            $payload = [
                'event' => [
                    'token' => $token,
                    'siteKey' => $this->config['site_key'],
                    'userIpAddress' => $request->ip(),
                    'userAgent' => $request->userAgent() ?? '',
                    'expectedAction' => 'admin_login',
                ],
            ];

            $timeout = config('security.external_services.captcha_timeout', 10);

            $httpResponse = Http::timeout($timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post($apiUrl, $payload);

            if (! $httpResponse->successful()) {
                Log::error('reCAPTCHA Enterprise API HTTP error', [
                    'status' => $httpResponse->status(),
                    'body' => $httpResponse->body(),
                    'ip' => $request->ip(),
                ]);

                throw new \Exception('Enterprise API request failed: '.$httpResponse->body());
            }

            $result = $httpResponse->json();

            if (! ($result['tokenProperties']['valid'] ?? false)) {
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

            // 成功を記録
            CaptchaFailoverService::recordSuccess('google_enterprise');

            return new CaptchaResult(true, $score, $action, [], ['score' => $score]);
        } catch (\Exception $e) {
            Log::error('reCAPTCHA Enterprise verification error', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'error_code' => $e->getCode(),
                'ip' => $request->ip(),
                'project_id' => $this->config['project_id'] ?? 'not_set',
                'site_key' => substr($this->config['site_key'] ?? '', 0, 10).'...',
                'token_length' => strlen($token),
            ]);

            // 失敗を記録（自動フェイルオーバーのトリガー）
            CaptchaFailoverService::recordFailure('google_enterprise', $e->getMessage());

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
                ['captcha' => 'reCAPTCHA verification error: '.$e->getMessage()],
                ['exception' => $e->getMessage(), 'exception_class' => get_class($e)]
            );
        }
    }

    public function rules(): array
    {
        if (! $this->isEnabled()) {
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
