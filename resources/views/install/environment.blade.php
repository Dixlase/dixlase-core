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

@extends('layouts.install')

@section('title', __('install/step2.environment_title'))
@section('header', __('install/step2.environment_header'))
@section('description', __('install/step2.environment_description'))

@section('content')

@php
    $isSimpleMode = (int) session('install_data.install_mode', 0) === 0;

    // HTTPS判定（リバースプロキシ経由にも対応）
    $isHttps = session('install_data.force_ssl', false)
        || request()->isSecure()
        || request()->header('X-Forwarded-Proto') === 'https';
    $protocol = $isHttps ? 'https://' : 'http://';

    // ✅ セッションから保存済みのapp_url（プロトコルなし）を取得
    $savedAppUrl = session('install_data.app_url');

    if ($savedAppUrl) {
        // セッションに保存されている場合はそのまま使用（プロトコルなしで保存されている）
        $hostAndPort = old('app_url', $savedAppUrl);
    } else {
        // 初回アクセス時は現在のURLからホストとポートを取得
        $currentUrl = request()->getSchemeAndHttpHost();
        $parsedHost = parse_url($currentUrl, PHP_URL_HOST);
        $parsedPort = parse_url($currentUrl, PHP_URL_PORT);
        $hostAndPort = old('app_url', $parsedHost . ($parsedPort ? ':' . $parsedPort : ''));
    }
@endphp
<form action="{{ route('install.environment.store') }}" method="POST" class="space-y-6">
    @csrf

    @if(!$isSimpleMode)
    <!-- 環境設定セクション（詳細モードのみ） -->
    <section aria-labelledby="env-settings-heading">
        <h2 id="env-settings-heading" class="sr-only">{{ __('install/step2.environment_settings') }}</h2>

        <fieldset class="space-y-4">
            <legend class="sr-only">{{ __('install/step2.application_environment') }}</legend>

            <div>
                <x-form-label for="app_env" :text="__('install/step2.app_env')" :required="true" />
                @php
                    $envOptions = [
                        'local' => __('install/step2.app_env_options.local'),
                        'staging' => __('install/step2.app_env_options.staging'),
                        'production' => __('install/step2.app_env_options.production'),
                    ];
                @endphp
                <x-form-select
                    id="app_env"
                    name="app_env"
                    :options="$envOptions"
                    :value="old('app_env', session('install_data.app_env', 'production'))"
                />
            </div>

            <div>
                <x-form-label for="app_debug" :text="__('install/step2.app_debug')" />
                <x-form-toggle
                    name="app_debug"
                    id="app_debug"
                    :checked="old('app_debug', session('install_data.app_debug', '0')) == '1'"
                    :label="__('install/step2.enable_debug')"
                />
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" id="debug-note">{{ __('install/step2.app_debug_note') }}</p>
            </div>
        </fieldset>
    </section>
    @endif

    <!-- URL設定セクション -->
    <section aria-labelledby="url-settings-heading">
        <h2 id="url-settings-heading" class="sr-only">{{ __('install/step2.url_settings') }}</h2>
        
        <fieldset class="space-y-4">
            <legend class="sr-only">{{ __('install/step2.application_url_configuration') }}</legend>
            
            <div>
                <x-form-label for="app_url" :text="__('install/step2.app_url')" :required="true" />
                <div class="flex items-center">
                    <span id="protocol_display" class="p-2 bg-gray-200 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-l-lg text-gray-700 dark:text-gray-300 text-sm">
                        {{ $protocol }}
                    </span>
                    <x-form-text
                        name="app_url"
                        id="app_url"
                        :value="old('app_url', $hostAndPort)"
                        :required="true"
                        class="input-full rounded-r-lg rounded-l-none"
                    />
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('install/step2.app_url_note') }}</p>
            </div>

            @if(!$isSimpleMode)
            <x-form-toggle
                name="force_ssl"
                id="force_ssl"
                :checked="session('install_data.force_ssl', $isHttps)"
                :label="__('install/step2.force_ssl')"
            />
            @endif
        </fieldset>
    </section>

    @if($isSimpleMode)
    <!-- 管理画面URL表示セクション（簡単モード：読み取り専用） -->
    <section aria-labelledby="admin-url-heading" x-data="{
        fullUrl: '{{ $protocol }}{{ $hostAndPort }}/{{ session('install_data.admin_url', 'admin') }}',
        copied: false,
        copyUrl() {
            navigator.clipboard.writeText(this.fullUrl);
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 2000);
        }
    }">
        <h2 id="admin-url-heading" class="sr-only">{{ __('install/step2.admin_url_configuration') }}</h2>

        <div>
            <x-form-label :text="__('install/step2.admin_url')" />
            <div class="flex items-center">
                <span class="p-2 bg-gray-200 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 text-sm" x-text="fullUrl"></span>
                <button type="button" @click="copyUrl()" class="ml-2 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition">
                    <i class="far" :class="copied ? 'fa-check-circle text-green-500 dark:text-green-400' : 'fa-copy'"></i>
                </button>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('install/step2.admin_url_auto_generated') }}</p>
        </div>
    </section>
    @else
    <!-- 管理画面URL設定セクション（詳細モード） -->
    <section aria-labelledby="admin-url-heading">
        <h2 id="admin-url-heading" class="sr-only">{{ __('install/step2.admin_url_configuration') }}</h2>

        @php
            $adminUrlPrefixes = config('admin.url.admin_url_prefixes', ['admin']);
            $currentAdminUrl = old('admin_url_prefix', '') !== ''
                ? old('admin_url_prefix') . '-' . old('admin_url_suffix', '')
                : session('install_data.admin_url', 'admin');
            $parts = explode('-', $currentAdminUrl, 2);
            $currentPrefix = in_array($parts[0], $adminUrlPrefixes) ? $parts[0] : $adminUrlPrefixes[0];
            $currentSuffix = $parts[1] ?? '';
            $prefixOptions = array_combine($adminUrlPrefixes, $adminUrlPrefixes);
        @endphp

        <div x-data="{
            prefix: '{{ $currentPrefix }}',
            suffix: '{{ $currentSuffix }}',
            baseUrl: '{{ $protocol }}{{ $hostAndPort }}',
            get fullUrl() { return this.baseUrl + '/' + this.prefix + '-' + this.suffix; },
            copied: false,
            copyUrl() {
                navigator.clipboard.writeText(this.fullUrl);
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);
            }
        }">
            <x-form-label for="admin_url_suffix" :text="__('install/step2.admin_url')" :required="true" />
            <div class="flex items-center">
                <span id="admin_base_url" class="p-2 bg-gray-200 dark:bg-gray-700 border border-r-0 border-gray-300 dark:border-gray-600 rounded-l-lg text-gray-700 dark:text-gray-300 text-sm whitespace-nowrap">{{ $protocol }}{{ $hostAndPort }}/</span>
                <select id="admin_url_prefix" name="admin_url_prefix"
                    x-init="prefix = $el.value"
                    @change="prefix = $event.target.value"
                    class="p-2 bg-gray-50 dark:bg-gray-800 border border-l-0 border-r-0 border-gray-300 dark:border-gray-500 text-sm dark:text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    @foreach($prefixOptions as $val => $label)
                        <option value="{{ $val }}" {{ $currentPrefix === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <span class="p-2 bg-gray-200 dark:bg-gray-700 border-y border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm">-</span>
                <x-form-text
                    name="admin_url_suffix"
                    id="admin_url_suffix"
                    :value="$currentSuffix"
                    :required="true"
                    class="input-full rounded-r-lg rounded-l-none"
                    placeholder="xxxx"
                    x-model="suffix"
                />
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                URL: <span x-text="fullUrl"></span>
                <button type="button" @click="copyUrl()" class="ml-1 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition">
                    <i class="far" :class="copied ? 'fa-check-circle text-green-500 dark:text-green-400' : 'fa-copy'"></i>
                </button>
            </p>
            <x-form-error field="admin_url_prefix" />
            <x-form-error field="admin_url_suffix" />
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('install/step2.admin_url_security_note') }}</p>
        </div>
    </section>
    @endif



    <!-- タイムゾーン設定セクション -->
    <section aria-labelledby="timezone-heading">
        <h2 id="timezone-heading" class="sr-only">{{ __('install/step2.timezone_configuration') }}</h2>
        
        <fieldset>
            <legend class="sr-only">{{ __('install/step2.application_timezone') }}</legend>
            
            <div>
                <x-form-label for="app_timezone" :text="is_array(__('install/step2.timezone')) ? __('install/step2.timezone.label') : __('install/step2.timezone')" :required="true" />
            @php
                // 現在のタイムゾーンを取得（セッションがあればそれを使い、なければクッキーから取得）
                $currentTz = old('app_timezone', session('install_data.app_timezone', ''));
                if (empty($currentTz)) {
                    // PHPでクッキーから取得を試みる
                    $currentTz = $_COOKIE['user_timezone'] ?? 'Asia/Tokyo'; // デフォルトは東京
                }
            @endphp
            @php
                // Get all available timezone identifiers
                $timezones = timezone_identifiers_list();
                
                // Get the timezone translations from the dedicated timezones.php file
                $timezoneTranslations = trans('timezones');
                
                // Get the translated timezone names
                $translatedTimezones = [];
                foreach ($timezones as $timezone) {
                    // Use the translation if available, otherwise use the timezone identifier
                    if (is_array($timezoneTranslations) && array_key_exists($timezone, $timezoneTranslations)) {
                        $translatedTimezones[$timezone] = $timezoneTranslations[$timezone];
                    } else {
                        $translatedTimezones[$timezone] = $timezone;
                    }
                }
                asort($translatedTimezones);
            @endphp
            
                @php
                    $timezoneOptions = [];
                    foreach($translatedTimezones as $timezone => $translatedName) {
                        $timezoneOptions[$timezone] = $translatedName;
                    }
                @endphp
                <select name="app_timezone" id="app_timezone" 
                    class="input-common input-full">
                    @foreach($timezoneOptions as $timezone => $translatedName)
                        <option value="{{ $timezone }}" {{ $currentTz === $timezone ? 'selected' : '' }}>
                            {{ $translatedName }}
                        </option>
                    @endforeach
                </select>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('install/step2.timezone_note') }}</p>
            </div>
        </fieldset>
    </section>

    <!-- フォームナビゲーション -->
    <nav aria-label="{{ __('install/common.form_navigation') }}" class="flex justify-center mt-6">
        <a href="{{ route('install.settings') }}"
            class="bg-gray-500 dark:bg-gray-600 text-white py-2 px-4 mx-4 rounded-lg hover:bg-gray-600 dark:hover:bg-gray-700 transition">
            {{ __('install/common.back') }}
        </a>
        <x-form-button
            type="submit"
            variant="primary"
            :label="__('install/common.next')"
            class="mx-4"
        />
    </nav>
</form>

@endsection