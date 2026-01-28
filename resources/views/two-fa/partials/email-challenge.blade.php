{{-- パーシャル用変数のデフォルト値設定 --}}
@php
    $resendAction = $resendAction ?? null;
    $title = $title ?? '認証コード入力';
    $prompt = $prompt ?? '送信された認証コードを入力してください';
    $submitText = $submitText ?? '認証';
    $resendText = $resendText ?? '再送信';
    $expireMinutes = $expireMinutes ?? 10;
    $resendIntervalSeconds = $resendIntervalSeconds ?? 60;
    $codeLength = $codeLength ?? 6;
    $autoSubmit = $autoSubmit ?? true;
    $showExpireTime = $showExpireTime ?? true;
    $showResend = $showResend ?? true;
    $context = $context ?? 'admin';
@endphp

<div class="text-center">
    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
        {{ $title }}
    </h2>
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
        {{ $prompt }}
    </p>

    <form action="{{ $action }}" method="POST" id="two-factor-form">
        @csrf
        
        <!-- 認証コード入力 -->
        <div class="mb-4">
            <div class="flex justify-center space-x-2" id="code-inputs">
                @for($i = 0; $i < $codeLength; $i++)
                    <input 
                        type="text" 
                        maxlength="1" 
                        class="w-12 h-12 text-center text-lg font-bold border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        data-index="{{ $i }}"
                        autocomplete="off"
                        inputmode="numeric"
                        pattern="[0-9]*"
                    >
                @endfor
            </div>
            <input type="hidden" name="code" id="hidden-code">
            @error('code')
                <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>

        <!-- 有効期限表示 -->
        @if($showExpireTime)
            <div class="mb-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('two_fa.email.expire_label') }}: <span id="expire-time">{{ $expireMinutes }}分</span>
                </p>
            </div>
        @endif

        <!-- 送信ボタン -->
        <div class="mb-4">
            <button 
                type="submit" 
                id="submit-button"
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-blue-500 dark:hover:bg-blue-600"
                disabled
            >
                {{ $submitText }}
            </button>
        </div>

        <!-- 再送信ボタン -->
        @if($showResend && $resendAction)
            <div class="text-center">
                <button 
                    type="button" 
                    id="resend-button"
                    class="text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    onclick="resendCode()"
                >
                    {{ $resendText }}
                </button>
                <span id="resend-countdown" class="text-xs text-gray-500 dark:text-gray-400 ml-2 hidden"></span>
            </div>
        @endif
    </form>
</div>

<script type="application/json" id="email-challenge-config">
{
    "resendAction": "{{ $resendAction }}",
    "csrfToken": "{{ csrf_token() }}",
    "codeLength": {{ $codeLength }},
    "expireMinutes": {{ $expireMinutes }},
    "resendIntervalSeconds": {{ $resendIntervalSeconds }},
    "autoSubmit": {{ $autoSubmit ? 'true' : 'false' }},
    "showExpireTime": {{ $showExpireTime ? 'true' : 'false' }},
    "showResend": {{ $showResend ? 'true' : 'false' }},
    "translations": {
        "minutes_suffix": "{{ __('two_fa.email.minutes_suffix') }}",
        "seconds_suffix": "{{ __('two_fa.email.seconds_suffix') }}",
        "expired": "{{ __('two_fa.email.expired') }}",
        "resend_failed": "{{ __('two_fa.email.resend_failed') }}",
        "network_error": "{{ __('two_fa.email.network_error') }}"
    }
}
</script>
