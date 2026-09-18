<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

        // CSP nonce for the inline script below. Without it the script is
        // blocked wherever CSP is on, and the invisible widget never produces
        // a token — the submission then fails as "response is required".
        $nonce = '';
        if (function_exists('csp_nonce')) {
            $nonceValue = csp_nonce();
            $nonce = $nonceValue ? ' nonce="'.$nonceValue.'"' : '';
        }

        if ($version === 'v2_invisible') {
            // v2 invisible: no checkbox. The token is fetched on load and
            // written into the hidden field, exactly like v3, so the page can
            // submit however it likes — a native submit, or the fetch() the
            // inquiry form uses. The previous shape intercepted the submit
            // event and called HTMLFormElement.prototype.submit() from the
            // callback, which cannot work for a form that posts with fetch:
            // the request is already in flight by then. It also carried no CSP
            // nonce (so the whole script was blocked on any site with CSP on),
            // grabbed `document.querySelector("form")` — the first form on the
            // page, not necessarily this one — and used a fixed element id, so
            // a second form on the page broke both.
            //
            // Trade-off: executing on load means Google may raise its
            // challenge when the page opens rather than on submit, and the
            // token expires two minutes later, as it does for v3.
            $instance = 'dls_rc_'.bin2hex(random_bytes(4));
            $containerId = 'recaptcha-container-'.$instance;

            return $scriptTag."
                <div id=\"{$containerId}\" style=\"display:none;\"></div>
                <input type=\"hidden\" name=\"g-recaptcha-response\" value=\"\">
                <script{$nonce}>
                    (function () {
                        var script = document.currentScript;
                        var container = document.getElementById('{$containerId}');

                        function tokenField() {
                            // The field this widget shipped with, inside the
                            // form the widget was rendered into.
                            var form = script && script.closest ? script.closest('form') : null;
                            var scope = form || document;

                            return scope.querySelector('input[name=\"g-recaptcha-response\"]');
                        }

                        function execute() {
                            var field = tokenField();
                            if (! field) {
                                console.error('reCAPTCHA v2 invisible: no g-recaptcha-response field found');

                                return;
                            }

                            var widgetId = grecaptcha.render(container, {
                                'sitekey': '$siteKey',
                                'size': 'invisible',
                                'callback': function (token) {
                                    field.value = token;
                                },
                                'error-callback': function () {
                                    console.error('reCAPTCHA v2 invisible: challenge failed');
                                }
                            });

                            grecaptcha.execute(widgetId);
                        }

                        function whenReady() {
                            if (typeof grecaptcha === 'undefined' || ! grecaptcha.render) {
                                console.error('reCAPTCHA v2 script not loaded properly');

                                return;
                            }

                            grecaptcha.ready ? grecaptcha.ready(execute) : execute();
                        }

                        // api.js is loaded async/defer, so it may land after
                        // this script runs.
                        if (typeof grecaptcha !== 'undefined' && grecaptcha.render) {
                            whenReady();
                        } else {
                            var waited = 0;
                            var timer = setInterval(function () {
                                waited += 100;
                                if (typeof grecaptcha !== 'undefined' && grecaptcha.render) {
                                    clearInterval(timer);
                                    whenReady();
                                } else if (waited >= 10000) {
                                    clearInterval(timer);
                                    console.error('reCAPTCHA v2 script not loaded properly');
                                }
                            }, 100);
                        }
                    })();
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
