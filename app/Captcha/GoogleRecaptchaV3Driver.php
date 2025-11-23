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
        
        Log::info('GoogleRecaptchaV3Driver constructor', [
            'site_key' => substr($this->config['site_key'], 0, 10) . '...'
        ]);
    }

    public function renderScript(): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        $siteKey = $this->config['site_key'];
        return "<script src=\"https://www.google.com/recaptcha/api.js?render={$siteKey}\"></script>";
    }

    public function renderWidget(array $options = []): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        $siteKey = $this->config['site_key'];
        $action = $options['action'] ?? 'submit';
        $scriptTag = $this->renderScript();
        
        Log::info('GoogleRecaptchaV3Driver renderWidget', [
            'siteKey' => $siteKey,
            'action' => $action
        ]);
        
        return $scriptTag . "
            <input type=\"hidden\" id=\"g-recaptcha-response\" name=\"g-recaptcha-response\" value=\"\">
            <script>
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
        if (!$this->isEnabled()) {
            Log::info('GoogleRecaptchaV3Driver verify - CAPTCHA disabled, bypassing');
            return new CaptchaResult(true, null, null, [], ['bypass' => true]);
        }

        $response = $request->input('g-recaptcha-response');
        
        Log::info('GoogleRecaptchaV3Driver verify - Start', [
            'has_token' => !empty($response),
            'token_length' => strlen($response ?? ''),
            'ip' => $request->ip()
        ]);
        
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

            if (!$result['success']) {
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

            Log::info('GoogleRecaptchaV3Driver verification success', [
                'score' => $score,
                'action' => $action,
                'ip' => $request->ip()
            ]);

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
        if (!$this->isEnabled()) {
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
}
