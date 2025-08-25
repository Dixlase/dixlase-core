@extends('layouts.install')

@section('title', __('install.environment_title'))
@section('header', __('install.environment_header'))
@section('description', __('install.environment_description'))

@section('content')

@php
    // ✅ 設定済みの APP_URL を取得（なければ現在の URL）
    $defaultAppUrl = old('app_url', session('install_data.app_url', request()->getSchemeAndHttpHost()));

    // ✅ `http://` または `https://` を除いたホストとポート番号を取得
    $parsedHost = parse_url($defaultAppUrl, PHP_URL_HOST);
    $parsedPort = parse_url($defaultAppUrl, PHP_URL_PORT);
    $hostAndPort = $parsedHost . ($parsedPort ? ':' . $parsedPort : '');
@endphp
<form action="{{ route('install.environment.store') }}" method="POST" class="space-y-4">
    @csrf

    <div>
        <label class="block text-gray-700">{{ __('install.app_env') }}</label>
        <select name="app_env" id="app_env" class="w-full p-2 border rounded-lg" onchange="toggleDebugMode()">
            <option value="local" {{ old('app_env', session('install_data.app_env', 'local')) === 'local' ? 'selected' : '' }}>
                {{ __('install.app_env_options.local') }}
            </option>
            <option value="staging" {{ old('app_env', session('install_data.app_env', 'local')) === 'staging' ? 'selected' : '' }}>
                {{ __('install.app_env_options.staging') }}
            </option>
            <option value="production" {{ old('app_env', session('install_data.app_env', 'local')) === 'production' ? 'selected' : '' }}>
                {{ __('install.app_env_options.production') }}
            </option>
        </select>
    </div>

    <div>
        <label class="block text-gray-700">{{ __('install.app_debug') }}</label>
        <label class="inline-flex items-center">
            <input type="checkbox" name="app_debug" id="app_debug" class="form-checkbox"
                value="1" {{ old('app_debug', session('install_data.app_debug', '1')) == '1' ? 'checked' : '' }}>
            <span class="ml-2">{{ __('install.enable_debug') }}</span>
        </label>
        <small class="text-gray-500" id="debug-note">{{ __('install.app_debug_note') }}</small>
    </div>

    <div>
        <label class="block text-gray-700">{{ __('install.app_url') }}</label>
        <div class="flex items-center">
            <span id="protocol_display" class="p-2 bg-gray-200 border rounded-l-lg">
                {{ session('install_data.force_ssl', false) ? 'https://' : 'http://' }}
            </span>
            <input type="text" name="app_url" id="app_url"
                value="{{ old('app_url', $hostAndPort) }}"
                class="w-full p-2 border rounded-r-lg" required>
        </div>
        <small class="text-gray-500">{{ __('install.app_url_note') }}</small>
    </div>

    <!-- ✅ SSL強制設定 -->
    <div class="flex items-center mt-2">
        <input type="checkbox" name="force_ssl" id="force_ssl" class="mr-2"
            value="1" {{ session('install_data.force_ssl', false) ? 'checked' : '' }}>
        <label for="force_ssl" class="text-gray-700">{{ __('install.force_ssl') }}</label>
    </div>

    <!-- ✅ 管理画面URLの設定 -->
    <div>
        <label class="block text-gray-700">{{ __('install.admin_url') }}</label>
        <div class="flex items-center">
            <span id="admin_url_prefix" class="p-2 bg-gray-200 border rounded-l-lg"></span>
            <input type="text" name="admin_url" id="admin_url"
                value="{{ old('admin_url', session('install_data.admin_url', 'admin')) }}"
                class="w-full p-2 border rounded-r-lg">
        </div>
        <small class="text-gray-500">{{ __('install.admin_url_security_note') }}</small>
    </div>



    <div class="mt-4">
        <label class="block text-gray-700 mb-2">{{ is_array(__('install.timezone')) ? __('install.timezone.label') : __('install.timezone') }}</label>
        <select name="app_timezone" id="app_timezone" class="w-full p-2 border rounded-lg">
            @php
                // 現在のタイムゾーンを取得（セッションがあればそれを使い、なければブラウザのタイムゾーンを検出）
                $currentTz = old('app_timezone', session('install_data.app_timezone', ''));
                if (empty($currentTz)) {
                    // ブラウザのタイムゾーンを検出
                    echo '<script>
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
            
            @foreach($translatedTimezones as $timezone => $translatedName)
                <option value="{{ $timezone }}" {{ $currentTz === $timezone ? 'selected' : '' }}>
                    {{ $translatedName }}
                </option>
            @endforeach
        </select>
        <small class="text-gray-500">{{ __('install.timezone_note') }}</small>
    </div>

    <div class="flex justify-between mt-6">
        <a href="{{ route('install.settings') }}"
            class="bg-gray-500 text-white py-2 px-4 rounded-lg hover:bg-gray-600 transition">
            {{ __('install.back') }}
        </a>
        <button type="submit"
                class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition">
            {{ __('install.next') }}
        </button>
    </div>
</form>

<script>
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