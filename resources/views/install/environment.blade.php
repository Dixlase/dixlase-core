@extends('layouts.install')

@section('title', __('install.environment_title'))
@section('header', __('install.environment_header'))
@section('description', __('install.environment_description'))

@section('content')

@php
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

    <!-- 環境設定セクション -->
    <section aria-labelledby="env-settings-heading">
        <h2 id="env-settings-heading" class="sr-only">{{ __('install.environment_settings') }}</h2>
        
        <fieldset class="space-y-4">
            <legend class="sr-only">{{ __('install.application_environment') }}</legend>
            
            <div>
                <x-form.label for="app_env" :text="__('install.app_env')" :required="true" />
                @php
                    $envOptions = [
                        'local' => 'install.app_env_options.local',
                        'staging' => 'install.app_env_options.staging',
                        'production' => 'install.app_env_options.production',
                    ];
                @endphp
                <x-form.select
                    id="app_env"
                    name="app_env"
                    :options="$envOptions"
                    :value="old('app_env', session('install_data.app_env', 'local'))"
                    onchange="toggleDebugMode()"
                    class="input-lg"
                />
            </div>

            <div>
                <x-form.label for="app_debug" :text="__('install.app_debug')" />
                <x-form.toggle
                    name="app_debug"
                    id="app_debug"
                    :checked="old('app_debug', session('install_data.app_debug', '1')) == '1'"
                    :label="__('install.enable_debug')"
                />
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" id="debug-note">{{ __('install.app_debug_note') }}</p>
            </div>
        </fieldset>
    </section>

    <!-- URL設定セクション -->
    <section aria-labelledby="url-settings-heading">
        <h2 id="url-settings-heading" class="sr-only">{{ __('install.url_settings') }}</h2>
        
        <fieldset class="space-y-4">
            <legend class="sr-only">{{ __('install.application_url_configuration') }}</legend>
            
            <div>
                <x-form.label for="app_url" :text="__('install.app_url')" :required="true" />
                <div class="flex items-center">
                    <span id="protocol_display" class="p-2 bg-gray-200 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-l-lg text-gray-700 dark:text-gray-300 text-sm">
                        {{ session('install_data.force_ssl', false) ? 'https://' : 'http://' }}
                    </span>
                    <x-form.text
                        name="app_url"
                        id="app_url"
                        :value="old('app_url', $hostAndPort)"
                        :required="true"
                        class="input-full rounded-r-lg rounded-l-none"
                    />
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('install.app_url_note') }}</p>
            </div>

            <x-form.toggle
                name="force_ssl"
                id="force_ssl"
                :checked="session('install_data.force_ssl', false)"
                :label="__('install.force_ssl')"
            />
        </fieldset>
    </section>

    <!-- 管理画面URL設定セクション -->
    <section aria-labelledby="admin-url-heading">
        <h2 id="admin-url-heading" class="sr-only">{{ __('install.admin_url_configuration') }}</h2>
        
        <fieldset>
            <legend class="sr-only">{{ __('install.admin_panel_url') }}</legend>
            
            <div>
                <x-form.label for="admin_url" :text="__('install.admin_url')" />
                <div class="flex items-center">
                    <span id="admin_url_prefix" class="p-2 bg-gray-200 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-l-lg text-gray-700 dark:text-gray-300 text-sm"></span>
                    <x-form.text
                        name="admin_url"
                        id="admin_url"
                        :value="old('admin_url', session('install_data.admin_url', 'admin'))"
                        class="input-full rounded-r-lg rounded-l-none"
                    />
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('install.admin_url_security_note') }}</p>
            </div>
        </fieldset>
    </section>



    <!-- タイムゾーン設定セクション -->
    <section aria-labelledby="timezone-heading">
        <h2 id="timezone-heading" class="sr-only">{{ __('install.timezone_configuration') }}</h2>
        
        <fieldset>
            <legend class="sr-only">{{ __('install.application_timezone') }}</legend>
            
            <div>
                <x-form.label for="app_timezone" :text="is_array(__('install.timezone')) ? __('install.timezone.label') : __('install.timezone')" :required="true" />
            @php
                // 現在のタイムゾーンを取得（セッションがあればそれを使い、なければブラウザのタイムゾーンを検出）
                $currentTz = old('app_timezone', session('install_data.app_timezone', ''));
                if (empty($currentTz)) {
                    // ブラウザのタイムゾーンを検出
                    echo '<script @cspNonce>
                        try {
                            const userTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                            document.cookie = `user_timezone=${userTimeZone};path=/;samesite=lax`;
                        } catch (e) {
                            console.error("タイムゾーンの検出に失敗しました:", e);
                        }
                    </script>';
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
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('install.timezone_note') }}</p>
            </div>
        </fieldset>
    </section>

    <!-- フォームナビゲーション -->
    <nav aria-label="{{ __('install.form_navigation') }}" class="flex justify-between mt-6">
        <a href="{{ route('install.settings') }}"
            class="bg-gray-500 dark:bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-600 dark:hover:bg-gray-700 transition">
            {{ __('install.back') }}
        </a>
        <x-form.button
            type="submit"
            variant="primary"
            :label="__('install.next')"
        />
    </nav>
</form>

<script @cspNonce>
    // デバッグモードの有効・無効を切り替える
    function toggleDebugMode() {
        let envSelect = document.getElementById('app_env');
        let debugCheckbox = document.getElementById('app_debug');
        let debugNote = document.getElementById('debug-note');

        if (envSelect.value === 'production') {
            debugCheckbox.disabled = true;
            debugCheckbox.checked = false;
            debugNote.style.display = 'block';
        } else {
            debugCheckbox.disabled = false;
            debugNote.style.display = 'none';
        }
    }

    // ページ読み込み時に実行
    document.addEventListener('DOMContentLoaded', toggleDebugMode);

    document.addEventListener('DOMContentLoaded', function () {
        const appUrlInput = document.getElementById('app_url');
        const forceSslCheckbox = document.getElementById('force_ssl');
        const protocolDisplay = document.getElementById('protocol_display');
        const adminUrlPrefix = document.getElementById('admin_url_prefix');

        function updateUrls() {
            const protocol = forceSslCheckbox.checked ? 'https://' : 'http://';
            const appUrl = appUrlInput.value.trim().replace(/^(https?:\/\/)?/, ''); // プロトコルを除去し、空白も削除

            // アプリケーションURLのプロトコル表示を更新
            protocolDisplay.innerText = protocol;
            
            // 管理画面URLのプレフィックスを更新（アプリケーションURL + スラッシュ）
            if (appUrl) {
                adminUrlPrefix.innerText = protocol + appUrl + '/';
            } else {
                adminUrlPrefix.innerText = protocol;
            }
        }

        // 初回ロード時にURLを更新
        updateUrls();

        // SSL設定変更時にリアルタイム更新
        forceSslCheckbox.addEventListener('change', function() {
            updateUrls();
        });

        // アプリケーションURL入力時にリアルタイム更新
        appUrlInput.addEventListener('input', function() {
            updateUrls();
        });

        // アプリケーションURL入力フィールドのフォーカス時とブラー時にも更新
        appUrlInput.addEventListener('focus', updateUrls);
        appUrlInput.addEventListener('blur', updateUrls);
    });
</script>

@endsection