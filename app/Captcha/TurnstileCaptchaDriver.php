<?php
/*
This file is part of MySoftware.

Copyright (C) 2025 exc-D inc.
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

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TurnstileCaptchaDriver implements CaptchaDriver
{
    private string $siteKey;
    private string $secretKey;

    public function __construct(string $siteKey, string $secretKey)
    {
        $this->siteKey = $siteKey;
        $this->secretKey = $secretKey;
    }

    public function verify(string $token, ?string $remoteIp = null): CaptchaResult
    {
        try {
            $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $this->secretKey,
                'response' => $token,
                'remoteip' => $remoteIp,
            ]);

            if (!$response->successful()) {
                Log::error('Turnstile API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return new CaptchaResult(false, 'API request failed');
            }

            $data = $response->json();

            if (!isset($data['success'])) {
                Log::error('Invalid Turnstile API response format', ['response' => $data]);
                return new CaptchaResult(false, 'Invalid API response format');
            }

            if ($data['success']) {
                return new CaptchaResult(true, 'Verification successful');
            }

            $errorCodes = $data['error-codes'] ?? [];
            $errorMessage = $this->getErrorMessage($errorCodes);
            
            Log::warning('Turnstile verification failed', [
                'error_codes' => $errorCodes,
                'error_message' => $errorMessage
            ]);

            return new CaptchaResult(false, $errorMessage);

        } catch (\Exception $e) {
            Log::error('Turnstile verification exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new CaptchaResult(false, 'Verification failed due to system error');
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

        return sprintf('<div%s></div>', $attributeString);
    }

    public function getScriptUrl(): string
    {
        return 'https://challenges.cloudflare.com/turnstile/v0/api.js';
    }

    private function getErrorMessage(array $errorCodes): string
    {
        $errorMessages = [
            'missing-input-secret' => 'The secret parameter is missing',
            'invalid-input-secret' => 'The secret parameter is invalid or malformed',
            'missing-input-response' => 'The response parameter is missing',
            'invalid-input-response' => 'The response parameter is invalid or malformed',
            'bad-request' => 'The request is invalid or malformed',
            'timeout-or-duplicate' => 'The response is no longer valid: either is too old or has been used previously',
            'internal-error' => 'An internal error happened while validating the response',
        ];

        if (empty($errorCodes)) {
            return 'Unknown verification error';
        }

        $messages = [];
        foreach ($errorCodes as $code) {
            $messages[] = $errorMessages[$code] ?? "Unknown error code: {$code}";
        }

        return implode(', ', $messages);
    }
}
