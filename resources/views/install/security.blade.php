@extends('layouts.install')

@section('title', __('install.security_title'))
@section('header', __('install.security_header'))
@section('description')
    {!! __('install.security_description') !!}
@endsection

@section('content')


<form action="{{ route('install.security.store') }}" method="POST" class="space-y-4">
    @csrf

    <hr class="my-6">
    <!-- ✅ IP制御のチェックボックス -->
    <h2 class="text-lg font-bold mt-6">{{ __('install.ip_restrictions') }}</h2>

    <p class="text-sm text-gray-500 mt-1">IPアドレスは1行に1つずつ入力してください。例:</p>
        <pre class="text-xs bg-gray-100 p-2 rounded mt-1">127.0.0.1
192.168.1.1
203.0.113.45</pre>

    <div>
        <label class="flex items-center">
            <input type="checkbox" name="enable_allowed_admin_ips" id="enable_allowed_admin_ips" class="mr-2"
                value="1" {{ old('enable_allowed_admin_ips', session('install_data.enable_allowed_admin_ips', '0')) == '1' ? 'checked' : '' }}>
            {{ __('install.enable_allowed_admin_ips') }}
        </label>
        <textarea name="allowed_admin_ips" id="allowed_admin_ips" rows="3"
            class="w-full p-2 border rounded-lg" placeholder="127.0.0.1"
            {{ old('enable_allowed_admin_ips', session('install_data.enable_allowed_admin_ips', '0')) == '1' ? '' : 'disabled' }}>{{ old('allowed_admin_ips', session('install_data.allowed_admin_ips', '127.0.0.1')) }}</textarea>

    </div>

    <div>
        <label class="flex items-center">
            <input type="checkbox" name="enable_blocked_admin_ips" id="enable_blocked_admin_ips" class="mr-2"
                value="1" {{ old('enable_blocked_admin_ips', session('install_data.enable_blocked_admin_ips', '0')) == '1' ? 'checked' : '' }}>
            {{ __('install.enable_blocked_admin_ips') }}
        </label>
        <textarea name="blocked_admin_ips" id="blocked_admin_ips" rows="3"
            class="w-full p-2 border rounded-lg"
            {{ old('enable_blocked_admin_ips', session('install_data.enable_blocked_admin_ips', '0')) == '1' ? '' : 'disabled' }}>
            {{ old('blocked_admin_ips', session('install_data.blocked_admin_ips', '')) }}
        </textarea>
    </div>

    <!-- ✅ フロント画面のIP制御 -->
    <div>
        <label class="flex items-center">
            <input type="checkbox" name="enable_allowed_front_ips" id="enable_allowed_front_ips" class="mr-2"
                value="1" {{ old('enable_allowed_front_ips', session('install_data.enable_allowed_front_ips', '0')) == '1' ? 'checked' : '' }}>
            {{ __('install.enable_allowed_front_ips') }}
        </label>
        <textarea name="allowed_front_ips" id="allowed_front_ips" rows="3"
            class="w-full p-2 border rounded-lg"
            {{ old('enable_allowed_front_ips', session('install_data.enable_allowed_front_ips', '0')) == '1' ? '' : 'disabled' }}>
            {{ old('allowed_front_ips', session('install_data.allowed_front_ips', '')) }}
        </textarea>
    </div>

    <div>
        <label class="flex items-center">
            <input type="checkbox" name="enable_blocked_front_ips" id="enable_blocked_front_ips" class="mr-2"
                value="1" {{ old('enable_blocked_front_ips', session('install_data.enable_blocked_front_ips', '0')) == '1' ? 'checked' : '' }}>
            {{ __('install.enable_blocked_front_ips') }}
        </label>
        <textarea name="blocked_front_ips" id="blocked_front_ips" rows="3"
            class="w-full p-2 border rounded-lg"
            {{ old('enable_blocked_front_ips', session('install_data.enable_blocked_front_ips', '0')) == '1' ? '' : 'disabled' }}>
            {{ old('blocked_front_ips', session('install_data.blocked_front_ips', '')) }}
        </textarea>
    </div>

    <div class="flex justify-between mt-6">
        <a href="{{ route('install.mail') }}"
            class="bg-gray-500 text-white py-2 px-4 rounded-lg hover:bg-gray-600">
            {{ __('install.back') }}
        </a>
        <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700">
            {{ __('install.next') }}
        </button>
    </div>
</form>

<script>
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