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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleRecaptchaV2Driver implements CaptchaDriver
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'site_key' => CaptchaHelper::getSiteKey(),
            'secret_key' => CaptchaHelper::getSecretKey(),
            'version' => CaptchaHelper::getGoogleVersion(),
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

        return "<script src=\"https://www.google.com/recaptcha/api.js\" async defer{$nonce}></script>";
    }

    public function renderWidget(array $options = []): string
    {
        if (! $this->isEnabled()) {
            return '';
        }

        $siteKey = $this->config['site_key'];
        $version = $this->config['version'];
        $action = $options['action'] ?? 'submit';
        $callback = $options['callback'] ?? 'onRecaptchaCallback';
        $scriptTag = $this->renderScript();

        if ($version === 'v2_invisible') {
            // v2 Invisible: no checkbox, automatically executed on form submission
            return $scriptTag."
                <div id=\"recaptcha-container\" style=\"display:none;\"></div>
                <script>
                    var recaptchaWidgetId;
                    var isRecaptchaExecuting = false;
                    
                    function onRecaptchaLoad() {
                        
                        recaptchaWidgetId = grecaptcha.render('recaptcha-container', {
                            'sitekey': '$siteKey',
                            'size': 'invisible',
                            'callback': function(token) {
                                isRecaptchaExecuting = false;
                                // Google reCAPTCHA APIが自動的にhidden inputに値を設定するため、
                                // 少し待ってからフォームを送信
                                setTimeout(function() {
                                    var form = document.querySelector('form');
                                    HTMLFormElement.prototype.submit.call(form);
                                }, 100);
                            },
                            'error-callback': function() {
                                isRecaptchaExecuting = false;
                                alert('reCAPTCHA verification failed. Please try again.');
                            }
                        });
                    }
                    
                    document.addEventListener('DOMContentLoaded', function() {
                        const form = document.querySelector('form');
                        
                        if (!form) {
                            return;
                        }
                        
                        // grecaptchaが読み込まれるまで待つ
                        var checkInterval = setInterval(function() {
                            if (typeof grecaptcha !== 'undefined' && grecaptcha.render) {
                                clearInterval(checkInterval);
                                onRecaptchaLoad();
                            }
                        }, 100);
                        
                        // フォーム送信をインターセプト
                        form.addEventListener('submit', function(e) {
                            // 既にトークンがある場合はそのまま送信
                            const existingToken = document.getElementById('g-recaptcha-response').value;
                            if (existingToken) {
                                return true;
                            }
                            
                            // 実行中の場合は待つ
                            if (isRecaptchaExecuting) {
                                e.preventDefault();
                                return false;
                            }
                            
                            // トークンがない場合は取得
                            e.preventDefault();
                            
                            if (typeof grecaptcha === 'undefined' || typeof recaptchaWidgetId === 'undefined') {
                                return false;
                            }
                            
                            isRecaptchaExecuting = true;
                            grecaptcha.execute(recaptchaWidgetId);
                        });
                    });
                </script>
            ";
        } else {
            // v2 Checkbox: displays checkbox
            return $scriptTag."<div class=\"g-recaptcha\" data-sitekey=\"$siteKey\" data-callback=\"$callback\"></div>";
        }
    }

    public function verify(Request $request): CaptchaResult
    {
        if (! $this->isEnabled()) {
            return new CaptchaResult(true, null, null, [], ['bypass' => true]);
        }

        $response = $request->input('g-recaptcha-response');

        if (empty($response)) {
            Log::warning('GoogleRecaptchaV2Driver verify - No token provided');

            return new CaptchaResult(false, null, null, ['captcha' => 'reCAPTCHA response is required']);
        }

        try {
            $httpResponse = Http::asForm()->post($this->config['verify_url'], [
                'secret' => $this->config['secret_key'],
                'response' => $response,
                'remoteip' => $request->ip(),
            ]);

            $result = $httpResponse->json();

            if ($result['success']) {
                return new CaptchaResult(true, null, null, [], []);
            }

            return new CaptchaResult(
                false,
                null,
                null,
                ['captcha' => 'reCAPTCHA verification failed'],
                ['error_codes' => $result['error-codes'] ?? []]
            );
        } catch (\Exception $e) {
            Log::error('GoogleRecaptchaV2Driver verification error', [
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
        $version = CaptchaHelper::getGoogleVersion();

        return CaptchaHelper::isEnabled() &&
               CaptchaHelper::getDriver() === 'google' &&
               ($version === 'v2_checkbox' || $version === 'v2_invisible');
    }

    /**
     * reCAPTCHA v2 loads its API script from www.google.com and pulls
     * static assets (fonts/images/sub-scripts) from www.gstatic.com. The
     * challenge is rendered inside a www.google.com iframe and verification
     * traffic is posted back to the same origin.
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
