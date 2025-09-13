{{--
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
--}}

@props(['action' => 'submit', 'callback' => 'onRecaptchaCallback', 'formName' => null])

@php
    use App\Models\SecuritySetting;
    use App\Models\CaptchaFormSetting;
    
    // CAPTCHA設定を取得
    $captchaEnabled = SecuritySetting::get('captcha_enabled', false);
    $captchaDriver = SecuritySetting::get('captcha_driver', 'google');
    
    // フォーム固有のCAPTCHA設定をチェック
    $formCaptchaEnabled = $formName ? CaptchaFormSetting::isEnabledFor($formName) : true;
    
    $shouldShowCaptcha = $captchaEnabled && $formCaptchaEnabled;
@endphp

@if($shouldShowCaptcha)
    <div class="captcha-container flex justify-center items-center h-24">
        @if($captchaDriver === 'google_enterprise')
            <!-- Google reCAPTCHA Enterprise -->
            @php
                $siteKey = SecuritySetting::get('captcha_google_enterprise_site_key', '');
            @endphp
            <script src="https://www.google.com/recaptcha/enterprise.js?render={{ $siteKey }}"></script>
            <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response" value="">
            <script>
                grecaptcha.enterprise.ready(function() {
                    grecaptcha.enterprise.execute('{{ $siteKey }}', {action: '{{ $action }}'}).then(function(token) {
                        document.getElementById('g-recaptcha-response').value = token;
                    });
                });
            </script>
            
        @elseif($captchaDriver === 'google')
            <!-- Standard Google reCAPTCHA -->
            @php
                $siteKey = SecuritySetting::get('captcha_google_site_key', '');
                $version = SecuritySetting::get('captcha_google_version', 'v3');
            @endphp
            
            @if($version === 'v3')
                <script src="https://www.google.com/recaptcha/api.js?render={{ $siteKey }}"></script>
                <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response" value="">
                <script>
                    grecaptcha.ready(function() {
                        grecaptcha.execute('{{ $siteKey }}', {action: '{{ $action }}'}).then(function(token) {
                            document.getElementById('g-recaptcha-response').value = token;
                        });
                    });
                </script>
            @else
                <!-- reCAPTCHA v2 -->
                <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                <div class="g-recaptcha" data-sitekey="{{ $siteKey }}" data-callback="{{ $callback }}"></div>
            @endif
            
        @elseif($captchaDriver === 'turnstile')
            <!-- Cloudflare Turnstile -->
            @php
                $siteKey = SecuritySetting::get('captcha_turnstile_site_key', '');
            @endphp
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            <div class="cf-turnstile" data-sitekey="{{ $siteKey }}" data-theme="light" data-size="normal"></div>
        @endif
    </div>
    
    <!-- CAPTCHA エラー表示 -->
    @error('captcha')
        <div class="text-red-600 text-sm mt-1">
            {{ $message }}
        </div>
    @enderror
@endif
