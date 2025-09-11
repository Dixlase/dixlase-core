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
use App\Models\SecuritySetting;

class GoogleRecaptchaDriver implements CaptchaDriver
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'site_key' => SecuritySetting::get('captcha_google_site_key', ''),
            'secret_key' => SecuritySetting::get('captcha_google_secret_key', ''),
            'version' => SecuritySetting::get('captcha_google_version', 'v3'),
            'min_score' => (float) SecuritySetting::get('captcha_google_min_score', 0.5),
            'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
        ], $config);
    }

    public function renderScript(): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        $siteKey = $this->config['site_key'];
        $version = $this->config['version'];

        if ($version === 'v3') {
            return "<script src=\"https://www.google.com/recaptcha/api.js?render={$siteKey}\"></script>";
        } else {
            return "<script src=\"https://www.google.com/recaptcha/api.js\" async defer></script>";
        }
    }

    public function renderWidget(array $options = []): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        $siteKey = $this->config['site_key'];
        $version = $this->config['version'];
        $action = $options['action'] ?? 'submit';
        $callback = $options['callback'] ?? 'onRecaptchaCallback';

        if ($version === 'v3') {
            return "
                <script>
                    grecaptcha.ready(function() {
                        grecaptcha.execute('{$siteKey}', {action: '{$action}'}).then(function(token) {
                            document.getElementById('g-recaptcha-response').value = token;
                        });
                    });
                </script>
                <input type=\"hidden\" id=\"g-recaptcha-response\" name=\"g-recaptcha-response\" value=\"\">
            ";
        } else {
            return "<div class=\"g-recaptcha\" data-sitekey=\"{$siteKey}\" data-callback=\"{$callback}\"></div>";
        }
    }

    public function verify(Request $request): CaptchaResult
    {
        if (!$this->isEnabled()) {
            return new CaptchaResult(true, null, null, [], ['bypass' => true]);
        }

        $response = $request->input('g-recaptcha-response');
        
        if (empty($response)) {
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
                Log::warning('reCAPTCHA verification failed', [
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

            // For v3, check the score
            if ($this->config['version'] === 'v3') {
                $score = $result['score'] ?? 0;
                $action = $result['action'] ?? null;

                if ($score < $this->config['min_score']) {
                    Log::warning('reCAPTCHA score too low', [
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
            }

            return new CaptchaResult(true, null, null, [], []);

        } catch (\Exception $e) {
            Log::error('reCAPTCHA verification error', [
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
        return SecuritySetting::get('captcha_enabled', false) && 
               SecuritySetting::get('captcha_driver', '') === 'google' &&
               !empty($this->config['site_key']) && 
               !empty($this->config['secret_key']);
    }
}
