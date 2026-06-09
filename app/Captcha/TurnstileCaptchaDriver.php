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

/*
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

namespace App\Captcha;

use App\Helpers\CaptchaHelper;
use App\Services\CaptchaFailoverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TurnstileCaptchaDriver implements CaptchaDriver
{
    private string $siteKey;

    private string $secretKey;

    public function __construct(array $config = [])
    {
        $this->siteKey = $config['site_key'] ?? CaptchaHelper::getSiteKey();
        $this->secretKey = $config['secret_key'] ?? CaptchaHelper::getSecretKey();
    }

    public function verify(Request $request): CaptchaResult
    {
        try {
            // Try Turnstile token with multiple key names
            $token = $request->input('cf-turnstile-response')
                  ?? $request->input('turnstile-response')
                  ?? $request->input('g-recaptcha-response');
            $remoteIp = $request->ip();

            if (empty($token)) {
                return new CaptchaResult(false, null, null, ['CAPTCHA token is missing']);
            }

            $timeout = config('security.external_services.captcha_timeout', 10);

            $response = Http::timeout($timeout)
                ->asForm()
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $this->secretKey,
                    'response' => $token,
                    'remoteip' => $remoteIp,
                ]);

            if (! $response->successful()) {
                Log::error('Turnstile API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return new CaptchaResult(false, null, null, ['API request failed']);
            }

            $data = $response->json();

            if (! isset($data['success'])) {
                Log::error('Invalid Turnstile API response format', ['response' => $data]);

                return new CaptchaResult(false, null, null, ['Invalid API response format']);
            }

            if ($data['success']) {
                // Record success
                CaptchaFailoverService::recordSuccess('turnstile');

                return new CaptchaResult(true);
            }

            $errorCodes = $data['error-codes'] ?? [];
            $errorMessage = $this->getErrorMessage($errorCodes);

            Log::warning('Turnstile verification failed', [
                'error_codes' => $errorCodes,
                'error_message' => $errorMessage,
            ]);

            return new CaptchaResult(false, null, null, [$errorMessage]);
        } catch (\Exception $e) {
            Log::error('Turnstile verification exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Record failure (triggers automatic failover)
            CaptchaFailoverService::recordFailure('turnstile', $e->getMessage());

            // Get failure behavior from settings
            $onFailure = config('security.external_services.captcha_on_failure', 'fail_closed');

            if ($onFailure === 'fail_open') {
                Log::warning('Turnstile verification failed but fail_open is configured, allowing request');

                return new CaptchaResult(
                    true,
                    null,
                    null,
                    [],
                    ['bypass' => true, 'reason' => 'fail_open_on_error', 'exception' => $e->getMessage()]
                );
            }

            return new CaptchaResult(false, null, null, ['Verification failed due to system error']);
        }
    }

    public function getSiteKey(): string
    {
        return $this->siteKey;
    }

    public function getDriverName(): string
    {
        return 'turnstile';
    }

    public function renderWidget(array $attributes = []): string
    {
        if (! $this->isEnabled()) {
            return '';
        }

        $defaultAttributes = [
            'class' => 'cf-turnstile',
            'data-sitekey' => $this->siteKey,
            'data-theme' => 'auto',
            'data-size' => 'normal',
        ];

        $attributes = array_merge($defaultAttributes, $attributes);

        $attributeString = '';
        foreach ($attributes as $key => $value) {
            $attributeString .= sprintf(' %s="%s"', $key, htmlspecialchars($value));
        }

        // Get CSP nonce if available
        $nonce = '';
        if (function_exists('csp_nonce')) {
            $nonceValue = csp_nonce();
            $nonce = $nonceValue ? ' nonce="'.$nonceValue.'"' : '';
        }

        $scriptTag = '<script src="'.$this->getScriptUrl().'" async defer'.$nonce.'></script>';
        $widgetTag = sprintf('<div%s></div>', $attributeString);

        return $scriptTag."\n".$widgetTag;
    }

    public function getScriptUrl(): string
    {
        return 'https://challenges.cloudflare.com/turnstile/v0/api.js';
    }

    private function getErrorMessage(array $errorCodes): string
    {
        if (empty($errorCodes)) {
            return __('admin/settings/security/turnstile_errors.unknown-error');
        }

        $messages = [];
        foreach ($errorCodes as $code) {
            $translationKey = "admin.settings.security.turnstile_errors.{$code}";
            $messages[] = __($translationKey, [], null, $translationKey);
        }

        return implode(', ', $messages);
    }

    public function renderScript(): string
    {
        if (! $this->isEnabled()) {
            return '';
        }

        return '<script src="'.$this->getScriptUrl().'" async defer></script>';
    }

    public function rules(): array
    {
        return [
            'cf-turnstile-response' => 'required|string',
        ];
    }

    public function isEnabled(): bool
    {
        $captchaDriver = CaptchaHelper::getDriver();

        return CaptchaHelper::isEnabled() &&
               $captchaDriver === 'turnstile';
    }

    /**
     * Cloudflare Turnstile loads its widget script and iframe from
     * challenges.cloudflare.com and posts verification requests to the same
     * origin.
     */
    public function cspDirectives(): array
    {
        return [
            'script-src' => ['https://challenges.cloudflare.com'],
            'frame-src' => ['https://challenges.cloudflare.com'],
            'connect-src' => ['https://challenges.cloudflare.com'],
        ];
    }
}
