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
use App\Models\SecuritySetting;
use Google\Cloud\RecaptchaEnterprise\V1\RecaptchaEnterpriseServiceClient;
use Google\Cloud\RecaptchaEnterprise\V1\Event;
use Google\Cloud\RecaptchaEnterprise\V1\Assessment;
use Google\Cloud\RecaptchaEnterprise\V1\CreateAssessmentRequest;

class GoogleRecaptchaDriver implements CaptchaDriver
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $captchaDriver = CaptchaHelper::getDriver();
        
        if ($captchaDriver === 'google_enterprise') {
            $this->config = array_merge([
                'site_key' => CaptchaHelper::getSiteKey(),
                'secret_key' => CaptchaHelper::getSecretKey(),
                'version' => 'v3', // Enterprise always uses v3
                'min_score' => CaptchaHelper::getGoogleMinScore(),
                'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
                'project_id' => CaptchaHelper::getGoogleProjectId(),
                'use_enterprise' => true,
            ], $config);
        } else {
            $this->config = array_merge([
                'site_key' => CaptchaHelper::getSiteKey(),
                'secret_key' => CaptchaHelper::getSecretKey(),
                'version' => CaptchaHelper::getGoogleVersion(),
                'min_score' => CaptchaHelper::getGoogleMinScore(),
                'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
                'project_id' => '',
                'use_enterprise' => false,
            ], $config);
        }
        
        // デバッグ用ログ
        Log::info('GoogleRecaptchaDriver constructor debug', [
            'captchaDriver' => $captchaDriver,
            'site_key_from_helper' => CaptchaHelper::getSiteKey(),
            'config_site_key' => $this->config['site_key'] ?? 'not_set',
            'all_config' => $this->config
        ]);
    }

    public function renderScript(): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        $siteKey = $this->config['site_key'];
        $version = $this->config['version'];
        $useEnterprise = $this->config['use_enterprise'] ?? false;

        if ($version === 'v3') {
            if ($useEnterprise) {
                return "<script src=\"https://www.google.com/recaptcha/enterprise.js?render={$siteKey}\"></script>";
            } else {
                return "<script src=\"https://www.google.com/recaptcha/api.js?render={$siteKey}\"></script>";
            }
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
        $useEnterprise = $this->config['use_enterprise'] ?? false;

        $scriptTag = $this->renderScript();
        
        // デバッグ用ログ
        Log::info('GoogleRecaptchaDriver renderWidget debug', [
            'siteKey' => $siteKey,
            'version' => $version,
            'action' => $action,
            'useEnterprise' => $useEnterprise
        ]);
        
        if ($version === 'v3') {
            if ($useEnterprise) {
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
            } else {
                return $scriptTag . "
                    <input type=\"hidden\" id=\"g-recaptcha-response\" name=\"g-recaptcha-response\" value=\"\">
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            console.log('CAPTCHA Debug - Site Key:', '$siteKey');
                            console.log('CAPTCHA Debug - Action:', '$action');
                            if (typeof grecaptcha !== 'undefined') {
                                grecaptcha.ready(function() {
                                    console.log('CAPTCHA Debug - About to execute with site key:', '$siteKey');
                                    grecaptcha.execute('$siteKey', {action: '$action'}).then(function(token) {
                                        console.log('CAPTCHA Debug - Token received:', token.substring(0, 20) + '...');
                                        document.getElementById('g-recaptcha-response').value = token;
                                    }).catch(function(error) {
                                        console.error('CAPTCHA Debug - Execute failed:', error);
                                    });
                                });
                            } else {
                                console.error('reCAPTCHA v3 script not loaded properly');
                            }
                        });
                    </script>
                ";
            }
        } else {
            return $scriptTag . "<div class=\"g-recaptcha\" data-sitekey=\"$siteKey\" data-callback=\"$callback\"></div>";
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

        $captchaDriver = SecuritySetting::get('captcha_driver', 'google');
        
        // Use Enterprise API if driver is google_enterprise
        if ($captchaDriver === 'google_enterprise' && !empty($this->config['project_id'])) {
            return $this->verifyWithEnterpriseAPI($request, $response);
        }

        // Use standard API for google driver
        return $this->verifyWithStandardAPI($request, $response);
    }

    protected function verifyWithEnterpriseAPI(Request $request, string $token): CaptchaResult
    {
        try {
            // Google Cloud認証の確認
            $credentialsPath = env('GOOGLE_APPLICATION_CREDENTIALS');
            Log::info('reCAPTCHA Enterprise verification attempt', [
                'project_id' => $this->config['project_id'],
                'site_key' => substr($this->config['site_key'], 0, 10) . '...',
                'credentials_path' => $credentialsPath,
                'credentials_exists' => $credentialsPath ? file_exists($credentialsPath) : false
            ]);
            
            if (empty($credentialsPath) || !file_exists($credentialsPath)) {
                throw new \Exception('Google Cloud認証情報が設定されていません。GOOGLE_APPLICATION_CREDENTIALSを設定してください。');
            }
            
            // RecaptchaEnterpriseServiceClientクラスの存在確認
            if (!class_exists('Google\Cloud\RecaptchaEnterprise\V1\RecaptchaEnterpriseServiceClient')) {
                throw new \Exception('Google Cloud reCAPTCHA Enterprise SDKがインストールされていません。');
            }
            
            $client = new RecaptchaEnterpriseServiceClient();
            $projectName = $client->projectName($this->config['project_id']);

            $event = (new Event())
                ->setToken($token)
                ->setSiteKey($this->config['site_key'])
                ->setUserIpAddress($request->ip())
                ->setUserAgent($request->userAgent() ?? '');

            $assessment = (new Assessment())
                ->setEvent($event);

            $createRequest = (new CreateAssessmentRequest())
                ->setParent($projectName)
                ->setAssessment($assessment);

            Log::info('reCAPTCHA Enterprise API call', [
                'project_name' => $projectName,
                'site_key' => substr($this->config['site_key'], 0, 10) . '...',
                'token_length' => strlen($token),
                'user_ip' => $request->ip()
            ]);
            
            $response = $client->createAssessment($createRequest);
            
            Log::info('reCAPTCHA Enterprise API response', [
                'token_valid' => $response->getTokenProperties()->getValid(),
                'score' => $response->getRiskAnalysis()->getScore(),
                'action' => $response->getTokenProperties()->getAction(),
                'invalid_reason' => $response->getTokenProperties()->getValid() ? null : $response->getTokenProperties()->getInvalidReason()
            ]);

            if (!$response->getTokenProperties()->getValid()) {
                Log::warning('reCAPTCHA Enterprise verification failed', [
                    'reason' => $response->getTokenProperties()->getInvalidReason(),
                    'ip' => $request->ip(),
                ]);

                return new CaptchaResult(
                    false,
                    null,
                    null,
                    ['captcha' => 'reCAPTCHA verification failed'],
                    ['invalid_reason' => $response->getTokenProperties()->getInvalidReason()]
                );
            }

            $score = $response->getRiskAnalysis()->getScore();
            $action = $response->getTokenProperties()->getAction();

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
                'token_length' => strlen($token),
                'trace' => $e->getTraceAsString()
            ]);

            return new CaptchaResult(
                false,
                null,
                null,
                ['captcha' => 'reCAPTCHA verification error: ' . $e->getMessage()],
                ['exception' => $e->getMessage(), 'exception_class' => get_class($e)]
            );
        }
    }

    protected function verifyWithStandardAPI(Request $request, string $response): CaptchaResult
    {
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
        $captchaDriver = CaptchaHelper::getDriver();
        
        return CaptchaHelper::isEnabled() && 
               ($captchaDriver === 'google' || $captchaDriver === 'google_enterprise');
    }
}
