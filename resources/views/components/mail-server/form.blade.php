{{--
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
--}}

{{--
    メールサーバー設定フォーム共通コンポーネント
    
    @param array $settings - メール設定値
    @param array $mailers - メーラー選択肢 (オプション)
    @param array $encryptions - 暗号化選択肢 (オプション)
    @param string $context - 'install' または 'admin' (デフォルト: 'admin')
    @param string $admin_email - 管理者メールアドレス (インストール時のみ)
--}}

@php
    $context = $context ?? 'admin';
    $isInstall = $context === 'install';
    
    // デフォルト値の設定
    $defaultMailers = [
        'smtp' => 'SMTP',
        'sendmail' => 'Sendmail', 
        'log' => 'Log'
    ];
    
    $defaultEncryptions = [
        '' => 'None',
        'tls' => 'TLS',
        'ssl' => 'SSL'
    ];
    
    $mailers = $mailers ?? $defaultMailers;
    $encryptions = $encryptions ?? $defaultEncryptions;
@endphp

@if($isInstall)
{{-- CSP対応: data属性で設定を渡す --}}
@php
$mailServerFormConfig = [
    'routes' => [
        'resetTests' => route('install.mail.reset-tests')
    ],
    'translations' => [
        'testIncomplete' => 'メールテストが未完了です'
    ]
];
@endphp
<div data-mail-server-form-config='@json($mailServerFormConfig)' style="display:none;"></div>
@endif

<!-- Mailer -->
<div class="mt-4">
    @if($isInstall)
        <x-form-label for="mail_mailer" :text="__('mail-server/config.server_settings.mailer')" :required="true" />
        <x-form-select
            id="mail_mailer"
            name="mail_mailer"
            :options="$mailers"
            :value="old('mail_mailer', session('install_data.mail_mailer', 'smtp'))"
            class="w-full mail-setting-input"
            class="input-lg"
        />
    @else
        <x-form-label
            for="mail_mailer"
            :text="__('mail-server/config.server_settings.mailer')"
        />
        <x-form-select
            id="mail_mailer"
            name="mail_mailer"
            :options="$mailers"
            :value="old('mail_mailer', $settings['mail_mailer'])"
            class="input-full"
        />
    @endif
</div>

<!-- Host -->
<div class="mt-4">
    @if($isInstall)
        <x-form-label for="mail_host" :text="__('mail-server/config.server_settings.mail_host')" :required="true" />
        <x-form-text
            name="mail_host"
            id="mail_host"
            :value="old('mail_host', session('install_data.mail_host', 'mailpit'))"
            class="mail-setting-input"
            class="input-full"
        />
    @else
        <x-form-label
            for="mail_host"
            :text="__('mail-server/config.server_settings.mail_host')"
        />
        <x-form-text
            id="mail_host"
            name="mail_host"
            :value="old('mail_host', $settings['mail_host'])"
            class="input-full"
        />
    @endif
</div>

<!-- Port -->
<div class="mt-4">
    @if($isInstall)
        <x-form-label for="mail_port" :text="__('mail-server/config.server_settings.mail_port')" :required="true" />
        <x-form-text
            type="number"
            name="mail_port"
            id="mail_port"
            :value="old('mail_port', session('install_data.mail_port', '1025'))"
            class="input-full"
        />
    @else
        <x-form-label
            for="mail_port"
            :text="__('mail-server/config.server_settings.mail_port')"
        />
        <x-form-text
            id="mail_port"
            name="mail_port"
            :value="old('mail_port', $settings['mail_port'])"
            class="input-full"
        />
    @endif
</div>

<!-- Username -->
<div class="mt-4">
    @if($isInstall)
        <x-form-label for="mail_username" :text="__('mail-server/config.server_settings.mail_username')" />
        <x-form-text
            name="mail_username"
            id="mail_username"
            :value="old('mail_username', session('install_data.mail_username'))"
            class="input-full"
        />
    @else
        <x-form-label
            for="mail_username"
            :text="__('mail-server/config.server_settings.mail_username')"
        />
        <x-form-text
            id="mail_username"
            name="mail_username"
            :value="old('mail_username', $settings['mail_username'])"
            class="input-full"
        />
    @endif
</div>

<!-- Password -->
<div class="mt-4">
    @if($isInstall)
        <x-form-label for="mail_password" :text="__('mail-server/config.server_settings.mail_password')" />
        <x-form-text
            type="password"
            name="mail_password"
            id="mail_password"
            :value="old('mail_password')"
            autocomplete="off"
            class="input-full"
        />
    @else
        <x-form-label
            for="mail_password"
            :text="__('mail-server/config.server_settings.mail_password')"
        />
        <x-form-text
            type="password"
            id="mail_password"
            name="mail_password"
            :value="old('mail_password', $settings['mail_password'])"
            autocomplete="off"
            class="input-full"
        />
    @endif
</div>

<!-- Encryption -->
<div class="mt-4">
    @if($isInstall)
        <x-form-label for="mail_encryption" :text="__('mail-server/config.server_settings.mail_encryption')" :required="true" />
        <x-form-select
            id="mail_encryption"
            name="mail_encryption"
            :options="$encryptions"
            :value="old('mail_encryption', session('install_data.mail_encryption'))"
            class="input-full mail-setting-input"
        />
    @else
        <x-form-label
            for="mail_encryption"
            :text="__('mail-server/config.server_settings.mail_encryption')"
        />
        <x-form-select
            id="mail_encryption"
            name="mail_encryption"
            :options="$encryptions"
            :value="old('mail_encryption', $settings['mail_encryption'])"
            class="input-full"
        />
    @endif
</div>

<!-- From Address -->
<div class="mt-4">
    @if($isInstall)
        <x-form-label for="mail_from_address" :text="__('mail-server/config.server_settings.mail_from_address')" :required="true" />
        <x-form-text
            type="email"
            name="mail_from_address"
            id="mail_from_address"
            :value="old('mail_from_address', session('install_data.mail_from_address', $admin_email ?? ''))"
            class="input-full"
        />
    @else
        <x-form-label
            for="mail_from_address"
            :text="__('mail-server/config.server_settings.mail_from_address')"
        />
        <x-form-text
            id="mail_from_address"
            name="mail_from_address"
            :value="old('mail_from_address', $settings['mail_from_address'])"
            class="input-lg"
        />
    @endif
</div>
