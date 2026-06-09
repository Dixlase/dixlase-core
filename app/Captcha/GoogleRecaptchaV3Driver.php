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

namespace App\Captcha;

use App\Helpers\CaptchaHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleRecaptchaV3Driver implements CaptchaDriver
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'site_key' => CaptchaHelper::getSiteKey(),
            'secret_key' => CaptchaHelper::getSecretKey(),
            'min_score' => CaptchaHelper::getGoogleMinScore(),
            'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
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

        return "<script src=\"https://www.google.com/recaptcha/api.js?render={$siteKey}\"{$nonce}></script>";
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
                console.log('CAPTCHA v3 Debug - Site Key:', '$siteKey');
                console.log('CAPTCHA v3 Debug - Action:', '$action');
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof grecaptcha !== 'undefined') {
                        grecaptcha.ready(function() {
                            console.log('CAPTCHA v3 Debug - About to execute');
                            grecaptcha.execute('$siteKey', {action: '$action'}).then(function(token) {
                                console.log('CAPTCHA v3 Debug - Token received:', token.substring(0, 20) + '...');
                                document.getElementById('g-recaptcha-response').value = token;
                            }).catch(function(error) {
                                console.error('CAPTCHA v3 Debug - Execute failed:', error);
                            });
                        });
                    } else {
                        console.error('reCAPTCHA v3 script not loaded properly');
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

        $response = $request->input('g-recaptcha-response');

        if (empty($response)) {
            Log::warning('GoogleRecaptchaV3Driver verify - No token provided');

            return new CaptchaResult(false, null, null, ['captcha' => 'reCAPTCHA response is required']);
        }

        try {
            $httpResponse = Http::asForm()->post($this->config['verify_url'], [
                'secret' => $this->config['secret_key'],
                'response' => $response,
                'remoteip' => $request->ip(),
            ]);

            $result = $httpResponse->json();

            if (! $result['success']) {
                Log::warning('GoogleRecaptchaV3Driver verification failed', [
                    'errors' => $result['error-codes'] ?? [],
                    'ip' => $request->ip(),
                ]);

                return new CaptchaResult(
                    false,
                    null,
                    null,
                    ['captcha' => 'reCAPTCHA verification failed'],
                    ['error_codes' => $result['error-codes'] ?? []]
                );
            }

            $score = $result['score'] ?? 0;
            $action = $result['action'] ?? null;

            if ($result['success'] && $score >= $this->config['min_score']) {
                return new CaptchaResult(true, $score, $action, [], ['score' => $score]);
            }

            if ($score < $this->config['min_score']) {
                Log::warning('GoogleRecaptchaV3Driver score too low', [
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
            Log::error('GoogleRecaptchaV3Driver verification error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return new CaptchaResult(
                false,
                null,
                null,
                ['captcha' => 'reCAPTCHA verification error'],
                ['exception' => $e->getMessage()]
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
        return CaptchaHelper::isEnabled() &&
               CaptchaHelper::getDriver() === 'google' &&
               CaptchaHelper::getGoogleVersion() === 'v3';
    }

    /**
     * reCAPTCHA v3 loads its API script from www.google.com and additional
     * runtime assets from www.gstatic.com. v3 is invisible so the iframe is
     * usually hidden, but the same www.google.com origin must be allowed in
     * frame-src for the underlying widget. Verification traffic is posted to
     * www.google.com.
     */
    public function cspDirectives(): array
    {
        return [
            'script-src' => ['https://www.google.com', 'https://www.gstatic.com'],
            'frame-src' => ['https://www.google.com'],
            'connect-src' => ['https://www.google.com'],
        ];
    }
}
