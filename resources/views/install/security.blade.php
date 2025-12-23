@extends('layouts.install')

@section('title', __('install.security_title'))
@section('header', __('install.security_header'))
@section('description')
    {!! __('install.security_description') !!}
@endsection

@section('content')

<form action="{{ route('install.security.store') }}" method="POST" class="space-y-6">
    @csrf

    <!-- IP制限設定セクション -->
    <section aria-labelledby="ip-restrictions-heading">
        <h2 id="ip-restrictions-heading" class="text-lg font-bold text-gray-900 dark:text-gray-100">
            {{ __('install.ip_restrictions') }}
        </h2>
        
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
            {{ __('install.ip_address_format_instruction') }}
        </p>
        <pre class="text-xs bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 p-3 rounded-lg mt-2 border border-gray-200 dark:border-gray-700">127.0.0.1
192.168.1.1
203.0.113.45</pre>

        <!-- 管理画面IP制限 -->
        <fieldset class="mt-6 space-y-4">
            <legend class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">
                {{ __('install.admin_panel_ip_restrictions') }}
            </legend>
            
            <!-- 許可IPアドレス -->
            <div class="space-y-2">
                <x-form.toggle
                    name="enable_allowed_admin_ips"
                    id="enable_allowed_admin_ips"
                    :checked="old('enable_allowed_admin_ips', session('install_data.enable_allowed_admin_ips', '0')) == '1'"
                    :label="__('install.enable_allowed_admin_ips')"
                />
                <x-form.textarea
                    name="allowed_admin_ips"
                    id="allowed_admin_ips"
                    rows="3"
                    :value="old('allowed_admin_ips', session('install_data.allowed_admin_ips', '127.0.0.1'))"
                    placeholder="127.0.0.1"
                    :disabled="old('enable_allowed_admin_ips', session('install_data.enable_allowed_admin_ips', '0')) != '1'"
                    class="input-full"
                />
            </div>

            <!-- ブロックIPアドレス -->
            <div class="space-y-2">
                <x-form.toggle
                    name="enable_blocked_admin_ips"
                    id="enable_blocked_admin_ips"
                    :checked="old('enable_blocked_admin_ips', session('install_data.enable_blocked_admin_ips', '0')) == '1'"
                    :label="__('install.enable_blocked_admin_ips')"
                />
                <x-form.textarea
                    name="blocked_admin_ips"
                    id="blocked_admin_ips"
                    rows="3"
                    :value="old('blocked_admin_ips', session('install_data.blocked_admin_ips', ''))"
                    :disabled="old('enable_blocked_admin_ips', session('install_data.enable_blocked_admin_ips', '0')) != '1'"
                    class="input-full"
                />
            </div>
        </fieldset>

        <!-- フロント画面IP制限 -->
        <fieldset class="mt-6 space-y-4">
            <legend class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">
                {{ __('install.front_panel_ip_restrictions') }}
            </legend>
            
            <!-- 許可IPアドレス -->
            <div class="space-y-2">
                <x-form.toggle
                    name="enable_allowed_front_ips"
                    id="enable_allowed_front_ips"
                    :checked="old('enable_allowed_front_ips', session('install_data.enable_allowed_front_ips', '0')) == '1'"
                    :label="__('install.enable_allowed_front_ips')"
                />
                <x-form.textarea
                    name="allowed_front_ips"
                    id="allowed_front_ips"
                    rows="3"
                    :value="old('allowed_front_ips', session('install_data.allowed_front_ips', ''))"
                    :disabled="old('enable_allowed_front_ips', session('install_data.enable_allowed_front_ips', '0')) != '1'"
                    class="input-full"
                />
            </div>

            <!-- ブロックIPアドレス -->
            <div class="space-y-2">
                <x-form.toggle
                    name="enable_blocked_front_ips"
                    id="enable_blocked_front_ips"
                    :checked="old('enable_blocked_front_ips', session('install_data.enable_blocked_front_ips', '0')) == '1'"
                    :label="__('install.enable_blocked_front_ips')"
                />
                <x-form.textarea
                    name="blocked_front_ips"
                    id="blocked_front_ips"
                    rows="3"
                    :value="old('blocked_front_ips', session('install_data.blocked_front_ips', ''))"
                    :disabled="old('enable_blocked_front_ips', session('install_data.enable_blocked_front_ips', '0')) != '1'"
                    class="input-full"
                />
            </div>
        </fieldset>
    </section>

    <!-- フォームナビゲーション -->
    <nav aria-label="{{ __('install.form_navigation') }}" class="flex justify-between mt-6">
        <a href="{{ route('install.mail') }}"
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
document.addEventListener('DOMContentLoaded', function() {
    // ✅ チェックボックスの有効・無効を制御
    function toggleTextarea(checkboxId, textareaId) {
        let checkbox = document.getElementById(checkboxId);
        let textarea = document.getElementById(textareaId);
        textarea.disabled = !checkbox.checked;

        checkbox.addEventListener('change', function() {
            textarea.disabled = !this.checked;
        });
    }

    toggleTextarea('enable_allowed_admin_ips', 'allowed_admin_ips');
    toggleTextarea('enable_blocked_admin_ips', 'blocked_admin_ips');
    toggleTextarea('enable_allowed_front_ips', 'allowed_front_ips');
    toggleTextarea('enable_blocked_front_ips', 'blocked_front_ips');
});
</script>

@endsection