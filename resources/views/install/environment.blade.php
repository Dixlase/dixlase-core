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

    // プロトコルを切り替える
    document.addEventListener('DOMContentLoaded', function () {
        let appUrlInput = document.getElementById('app_url');
        let forceSslCheckbox = document.getElementById('force_ssl');
        let protocolDisplay = document.getElementById('protocol_display');

        function updateProtocol() {
            let currentValue = appUrlInput.value;

            if (forceSslCheckbox.checked) {
                // ✅ チェックが入っているとき: `http://` → `https://`
                protocolDisplay.innerText = "https://";
                if (currentValue.startsWith("http://")) {
                    appUrlInput.value = currentValue.replace(/^http:/, "https:");
                }
            } else {
                // ✅ チェックが外れているとき: `https://` → `http://`
                protocolDisplay.innerText = "http://";
                if (currentValue.startsWith("https://")) {
                    appUrlInput.value = currentValue.replace(/^https:/, "http:");
                }
            }
        }

        // 初回ロード時にも適用
        updateProtocol();

        // ✅ チェックボックスの変更時にプロトコルを変更
        forceSslCheckbox.addEventListener('change', updateProtocol);
    });
</script>

@endsection