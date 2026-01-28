{{-- パーシャル用変数のデフォルト値設定 --}}
@php
    $context = $context ?? 'admin';
    $hasPasskeyDevices = $hasPasskeyDevices ?? true;
    $dashboardRoute = $dashboardRoute ?? null; // コントローラーから渡される
@endphp

<div id="passkey-auth-container">
    <!-- 認証待機状態 -->
    <div id="passkey-waiting" class="text-center">
        <button id="start-passkey-auth" 
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white {{ $hasPasskeyDevices ? 'bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600' : 'bg-gray-400 cursor-not-allowed dark:bg-gray-600' }}"
                {{ !$hasPasskeyDevices ? 'disabled' : '' }}>
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
            </svg>
            {{ __('two_fa.passkey.start_auth') }}
        </button>
    </div>

    <!-- 認証進行中状態 -->
    <div id="passkey-processing" class="text-center hidden">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 dark:bg-yellow-900 mb-4">
            <svg class="animate-pulse h-8 w-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
            {{ __('two_fa.passkey.waiting_title') }}
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ __('two_fa.passkey.waiting_message') }}
        </p>
    </div>

    <!-- 認証成功状態 -->
    <div id="passkey-success" class="text-center hidden">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900 mb-4">
            <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
            {{ __('two_fa.passkey.success_title') }}
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ __('two_fa.passkey.success_message') }}
        </p>
    </div>

    <!-- 認証失敗状態 -->
    <div id="passkey-error" class="text-center hidden">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 dark:bg-red-900 mb-4">
            <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
            {{ __('two_fa.passkey.error_title') }}
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4" id="passkey-error-message">
            {{ __('two_fa.passkey.error_message') }}
        </p>
        <button id="retry-passkey-auth" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600">
            {{ __('two_fa.passkey.retry') }}
        </button>
    </div>

    <!-- 未サポート状態 -->
    <div id="passkey-unsupported" class="text-center hidden">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
            <svg class="h-8 w-8 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
            {{ __('two_fa.passkey.unsupported_title') }}
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            {{ __('two_fa.passkey.unsupported_message') }}
        </p>
    </div>
</div>

<script type="application/json" id="passkey-challenge-config">
{
    "challengeAction": "{{ $challengeAction }}",
    "verifyAction": "{{ $verifyAction }}",
    "csrfToken": "{{ csrf_token() }}",
    "dashboardRoute": "{{ $dashboardRoute ? route($dashboardRoute) : '#' }}",
    "translations": {
        "challenge_failed": "{{ __('two_fa.passkey.challenge_failed') }}",
        "network_error": "{{ __('two_fa.passkey.network_error') }}",
        "no_challenge_data": "{{ __('two_fa.passkey.no_challenge_data') }}",
        "verification_failed": "{{ __('two_fa.passkey.verification_failed') }}",
        "auth_cancelled": "{{ __('two_fa.passkey.auth_cancelled') }}",
        "invalid_state": "{{ __('two_fa.passkey.invalid_state') }}",
        "auth_failed": "{{ __('two_fa.passkey.auth_failed') }}"
    }
}
</script>
