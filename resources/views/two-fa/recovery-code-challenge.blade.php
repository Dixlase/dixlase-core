@extends('layouts.auth')

@section('title', __('two_fa.recovery_code.title'))
@section('icon')
["fas fa-life-ring", "fas fa-key"]
@endsection
@section('header', __('two_fa.recovery_code.title'))
@section('description', __('two_fa.recovery_code.prompt'))

@section('content')
    @php
        $formAction = $action ?? route('admin.two-fa.recovery-code.confirm');
        $contextValue = $context ?? 'admin';
    @endphp
    <form method="POST" action="{{ $formAction }}" class="space-y-6" id="recoveryCodeForm">
        @csrf

        <!-- 回復コード入力 -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                {{ __('two_fa.recovery_code.code_label') }}
            </label>
            
            <!-- 5桁×4ブロックの入力フィールド -->
            <div class="flex items-center justify-center gap-2">
                <input
                    type="text"
                    id="code1"
                    maxlength="5"
                    class="w-20 px-3 py-2 text-center text-lg font-mono border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('recovery_code') border-red-500 @enderror"
                    placeholder="12345"
                    required
                    autofocus
                    autocomplete="off"
                    inputmode="numeric"
                    pattern="[0-9]*"
                >
                <span class="text-2xl text-gray-400 dark:text-gray-500">-</span>
                <input
                    type="text"
                    id="code2"
                    maxlength="5"
                    class="w-20 px-3 py-2 text-center text-lg font-mono border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('recovery_code') border-red-500 @enderror"
                    placeholder="67890"
                    required
                    autocomplete="off"
                    inputmode="numeric"
                    pattern="[0-9]*"
                >
                <span class="text-2xl text-gray-400 dark:text-gray-500">-</span>
                <input
                    type="text"
                    id="code3"
                    maxlength="5"
                    class="w-20 px-3 py-2 text-center text-lg font-mono border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('recovery_code') border-red-500 @enderror"
                    placeholder="12345"
                    required
                    autocomplete="off"
                    inputmode="numeric"
                    pattern="[0-9]*"
                >
                <span class="text-2xl text-gray-400 dark:text-gray-500">-</span>
                <input
                    type="text"
                    id="code4"
                    maxlength="5"
                    class="w-20 px-3 py-2 text-center text-lg font-mono border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white @error('recovery_code') border-red-500 @enderror"
                    placeholder="67890"
                    required
                    autocomplete="off"
                    inputmode="numeric"
                    pattern="[0-9]*"
                >
            </div>

            <!-- 隠しフィールド（実際に送信される値） -->
            <input type="hidden" name="recovery_code" id="recovery_code">

            @error('recovery_code')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400 text-center">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-center">
                {{ __('two_fa.recovery_code.format_hint') }}
            </p>
        </div>

        <!-- 送信ボタン -->
        <div>
            <button
                type="submit"
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600"
            >
                {{ __('two_fa.recovery_code.submit') }}
            </button>
        </div>
    </form>

    <!-- Passkeyデバイス未登録警告 -->
    @if($showPasskeyDeviceWarning ?? false)
        <x-message 
            type="warning" 
            :message="'<strong>' . __('two_fa.passkey_device_not_registered_title') . '</strong><br>' . __('two_fa.passkey_device_not_registered_message')" 
        />
    @endif

    <!-- 別の認証方法に切り替える -->
    @include('two-fa.partials.alternative-methods', [
        'methods' => $availableMethods ?? [],
        'currentMethod' => null,
        'context' => $contextValue,
        'recoveryCodeRoute' => null,
        'showRecoveryCode' => false,
    ])
@endsection

@section('back_link')
    @php
        $backRoute = $loginRoute ?? route('admin.login');
    @endphp
    <a href="{{ $backRoute }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
        ← {{ __('two_fa.back_to_login') }}
    </a>
@endsection

@push('scripts')
<script src="{{ asset('build/assets/components/two-fa/js/recovery-code-challenge.js') }}" @cspNonce></script>
<script @cspNonce>
document.addEventListener('DOMContentLoaded', function() {
    window.initRecoveryCodeChallenge({
        translations: {
            format_hint: '{{ __("two_fa.recovery_code.format_hint") }}'
        }
    });
});
</script>
@endpush
