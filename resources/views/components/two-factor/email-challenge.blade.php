@props([
    'action',
    'resendAction' => null,
    'title' => '認証コード入力',
    'prompt' => '送信された認証コードを入力してください',
    'submitText' => '認証',
    'resendText' => '再送信',
    'expireMinutes' => 10,
    'resendIntervalSeconds' => 60,
    'codeLength' => 6,
    'autoSubmit' => true,
    'showExpireTime' => true,
    'showResend' => true,
    'context' => 'admin'
])

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
                    {{ __('two-factor.email.expire_label') }}: <span id="expire-time">{{ $expireMinutes }}分</span>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('#code-inputs input');
    const hiddenInput = document.getElementById('hidden-code');
    const submitButton = document.getElementById('submit-button');
    const resendButton = document.getElementById('resend-button');
    const form = document.getElementById('two-factor-form');
    
    let resendCountdown = 0;
    let countdownInterval = null;
    let expireInterval = null;

    // フラッシュメッセージ表示関数（既存のflash-messageコンポーネントと同じスタイル）
    function showFlashMessage(message, type = 'success') {
        // 既存のメッセージを削除
        const existingMessage = document.querySelector('.flash-message-dynamic');
        if (existingMessage) {
            existingMessage.remove();
        }

        // メッセージ要素を作成
        const messageDiv = document.createElement('div');
        messageDiv.className = `flash-message-dynamic mb-6 p-4 font-semibold rounded-xl ${
            type === 'success' 
                ? 'text-green-800 bg-green-100 border border-green-200 dark:text-green-200 dark:bg-green-900 dark:border-green-700' 
                : 'text-red-800 bg-red-100 border border-red-200 dark:text-red-200 dark:bg-red-900 dark:border-red-700'
        }`;
        messageDiv.textContent = message;

        // フォームの前に挿入
        form.parentNode.insertBefore(messageDiv, form);

        // 5秒後に自動削除
        setTimeout(() => {
            messageDiv.style.transition = 'opacity 0.5s';
            messageDiv.style.opacity = '0';
            setTimeout(() => messageDiv.remove(), 500);
        }, 5000);
    }

    // コード入力処理
    inputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
            const value = e.target.value.replace(/[^0-9]/g, '');
            e.target.value = value;

            if (value && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }

            updateHiddenInput();
            updateSubmitButton();

            // 自動送信
            @if($autoSubmit)
            if (getCodeValue().length === {{ $codeLength }}) {
                setTimeout(() => form.submit(), 100);
            }
            @endif
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                inputs[index - 1].focus();
                inputs[index - 1].value = '';
                updateHiddenInput();
                updateSubmitButton();
            }
        });

        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text');
            const numbers = paste.replace(/[^0-9]/g, '').slice(0, {{ $codeLength }});
            
            for (let i = 0; i < numbers.length && i < inputs.length; i++) {
                inputs[i].value = numbers[i];
            }
            
            updateHiddenInput();
            updateSubmitButton();

            // 自動送信
            @if($autoSubmit)
            if (numbers.length === {{ $codeLength }}) {
                setTimeout(() => form.submit(), 100);
            }
            @endif
        });
    });

    function getCodeValue() {
        return Array.from(inputs).map(input => input.value).join('');
    }

    function updateHiddenInput() {
        hiddenInput.value = getCodeValue();
    }

    function updateSubmitButton() {
        const code = getCodeValue();
        submitButton.disabled = code.length !== {{ $codeLength }};
    }

    // 有効期限タイマーを開始する関数
    @if($showExpireTime)
    function startExpireTimer() {
        // 既存のタイマーをクリア
        if (expireInterval) {
            clearInterval(expireInterval);
        }
        
        let expireTime = {{ $expireMinutes }} * 60; // 秒に変換
        const expireElement = document.getElementById('expire-time');
        
        expireInterval = setInterval(() => {
            expireTime--;
            const minutes = Math.floor(expireTime / 60);
            const seconds = expireTime % 60;
            expireElement.textContent = `${minutes}分${seconds.toString().padStart(2, '0')}{{ __('two-factor.email.seconds_suffix') }}`;
            
            if (expireTime <= 0) {
                clearInterval(expireInterval);
                expireElement.textContent = '{{ __('two-factor.email.expired') }}';
                inputs.forEach(input => input.disabled = true);
                submitButton.disabled = true;
            }
        }, 1000);
    }
    @endif

    // 再送信機能
    @if($showResend && $resendAction)
    window.resendCode = function() {
        if (resendCountdown > 0) return;

        fetch('{{ $resendAction }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 成功メッセージを表示
                showFlashMessage(data.message, 'success');
                
                startResendCountdown({{ $resendIntervalSeconds }}); // 設定値の秒数間再送信を無効化
                
                // 有効期限タイマーをリセット
                @if($showExpireTime)
                startExpireTimer();
                @endif
                
                // 入力フィールドをクリアして有効化
                inputs.forEach(input => {
                    input.value = '';
                    input.disabled = false;
                });
                inputs[0].focus();
                updateHiddenInput();
                updateSubmitButton();
            } else {
                showFlashMessage(data.message || '{{ __('two-factor.email.resend_failed') }}', 'error');
            }
        })
        .catch(error => {
            console.error('Resend error:', error);
            showFlashMessage('{{ __('two-factor.email.network_error') }}', 'error');
        });
    };

    function startResendCountdown(seconds) {
        resendCountdown = seconds;
        resendButton.disabled = true;
        document.getElementById('resend-countdown').classList.remove('hidden');
        
        countdownInterval = setInterval(() => {
            resendCountdown--;
            document.getElementById('resend-countdown').textContent = `(${resendCountdown}{{ __('two-factor.email.seconds_suffix') }})`;
            
            if (resendCountdown <= 0) {
                clearInterval(countdownInterval);
                resendButton.disabled = false;
                document.getElementById('resend-countdown').classList.add('hidden');
            }
        }, 1000);
    }
    @endif

    // 最初の入力フィールドにフォーカス
    inputs[0].focus();

    // 有効期限カウントダウンを開始
    @if($showExpireTime)
    startExpireTimer();
    @endif
});
</script>
