{{-- パーシャル用変数のデフォルト値設定 --}}
@php
    $resendAction = $resendAction ?? null;
    $title = $title ?? 'デバイス認証';
    $prompt = $prompt ?? 'デバイスでの認証を確認してください';
    $context = $context ?? 'admin';
    $pollInterval = $pollInterval ?? 2000;
    $maxRetries = $maxRetries ?? 30;
    $autoStart = $autoStart ?? false;
    $expireMinutes = $expireMinutes ?? 10;
    $resendIntervalSeconds = $resendIntervalSeconds ?? 60;
@endphp

<div id="device-auth-container">
    <div class="text-center">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
            {{ $title }}
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            {!! $prompt !!}
        </p>
    </div>
    <!-- 認証待機状態 -->
    <div id="device-waiting" class="text-center">
        <button id="start-device-auth" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600">
            {{ __('two_fa.device.start_button') }}
        </button>
    </div>

    <!-- 認証進行中状態 -->
    <div id="device-processing" class="text-center hidden">
        <div class="flex justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 dark:border-blue-400"></div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">
            {{ __('two_fa.device.expire_label') }}: <span id="expire-time">{{ $expireMinutes }}分</span>
        </p>
        
        @if($resendAction)
        <!-- 再送信ボタン -->
        <div class="mt-4">
            <button 
                type="button" 
                id="resend-device-button"
                class="text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
            >
                {{ __('two_fa.device.resend_button') }}
            </button>
            <span id="resend-device-countdown" class="text-xs text-gray-500 dark:text-gray-400 ml-2 hidden"></span>
        </div>
        @endif
    </div>

    <!-- 認証成功状態 -->
    <div id="device-success" class="text-center hidden">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900 mb-4">
            <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
            {{ __('two_fa.device.success_title') }}
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ __('two_fa.device.success_message') }}
        </p>
    </div>

    <!-- 認証失敗状態 -->
    <div id="device-error" class="text-center hidden">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 dark:bg-red-900 mb-4">
            <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
            {{ __('two_fa.device.error_title') }}
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4" id="device-error-message">
            {{ __('two_fa.device.error_message') }}
        </p>
        <button id="retry-device-auth" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600">
            {{ __('two_fa.device.retry_button') }}
        </button>
    </div>

    <!-- タイムアウト状態 -->
    <div id="device-timeout" class="text-center hidden">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-orange-100 dark:bg-orange-900 mb-4">
            <svg class="h-8 w-8 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
            {{ __('two_fa.device.timeout_title') }}
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            {{ __('two_fa.device.timeout_message') }}
        </p>
        <button id="retry-timeout-auth" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600">
            {{ __('two_fa.device.retry_button') }}
        </button>
    </div>
</div>

<script @cspNonce>
document.addEventListener('DOMContentLoaded', function() {
    let challengeId = null;
    let pollInterval = null;
    let countdownInterval = null;
    let expireInterval = null;
    let retryCount = 0;
    let expireTime = {{ $expireMinutes }} * 60; // 設定値（分）を秒に変換
    const resendButton = document.getElementById('resend-device-button');
    const resendCountdown = document.getElementById('resend-device-countdown');
    const expireTimeElement = document.getElementById('expire-time');

    // デバイス認証チャレンジを開始
    function startDeviceChallenge() {
        showProcessing();
        retryCount = 0;
        remainingTime = 60;

        fetch('{{ $challengeAction }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                challengeId = data.challenge_id;
                startPolling();
                startCountdown();
            } else {
                showError(data.message || '{{ __('two_fa.device.challenge_start_failed') }}');
            }
        })
        .catch(error => {
            console.error('Device challenge error:', error);
            showError('{{ __('two_fa.device.network_error') }}');
        });
    }

    // 認証状態をポーリング
    function startPolling() {
        pollInterval = setInterval(() => {
            fetch('{{ $verifyAction }}', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.status === 'approved') {
                    // 承認された
                    stopPolling();
                    stopCountdown();
                    showSuccess();
                    
                    // ダッシュボードにリダイレクト
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else if (data.status === 'expired') {
                    // 期限切れ
                    stopPolling();
                    stopCountdown();
                    showTimeout();
                }
                // pending状態の場合は継続してポーリング
            })
            .catch(error => {
                console.error('Polling error:', error);
                retryCount++;
                if (retryCount >= {{ $maxRetries }}) {
                    stopPolling();
                    stopCountdown();
                    showError('{{ __('two_fa.device.network_error') }}');
                }
            });
        }, {{ $pollInterval }});
    }

    // 有効期限のカウントダウン開始
    function startCountdown() {
        expireInterval = setInterval(() => {
            expireTime--;
            const minutes = Math.floor(expireTime / 60);
            const seconds = expireTime % 60;
            if (expireTimeElement) {
                expireTimeElement.textContent = `${minutes}分${seconds}秒`;
            }
            
            if (expireTime <= 0) {
                stopPolling();
                stopCountdown();
                showTimeout();
            }
        }, 1000);
    }

    // ポーリング停止
    function stopPolling() {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    }

    // カウントダウン停止
    function stopCountdown() {
        if (expireInterval) {
            clearInterval(expireInterval);
            expireInterval = null;
        }
    }

    // 各状態表示関数
    function showWaiting() {
        hideAllStates();
        document.getElementById('device-waiting').classList.remove('hidden');
    }

    function showProcessing() {
        hideAllStates();
        document.getElementById('device-processing').classList.remove('hidden');
    }

    function showSuccess() {
        hideAllStates();
        document.getElementById('device-success').classList.remove('hidden');
    }

    function showError(message) {
        hideAllStates();
        document.getElementById('device-error').classList.remove('hidden');
        document.getElementById('device-error-message').textContent = message;
    }

    function showTimeout() {
        hideAllStates();
        document.getElementById('device-timeout').classList.remove('hidden');
    }

    function hideAllStates() {
        document.getElementById('device-waiting').classList.add('hidden');
        document.getElementById('device-processing').classList.add('hidden');
        document.getElementById('device-success').classList.add('hidden');
        document.getElementById('device-error').classList.add('hidden');
        document.getElementById('device-timeout').classList.add('hidden');
    }

    // 再送信機能
    @if($resendAction)
    function resendDeviceAuth() {
        if (!resendButton) return;
        
        resendButton.disabled = true;
        resendButton.textContent = '{{ __('two_fa.device.sending') }}';
        
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
                resendButton.textContent = '{{ __('two_fa.device.resend_button') }}';
                
                // 再送信クールダウン
                let countdown = {{ $resendIntervalSeconds }};
                resendCountdown.textContent = `(${countdown}{{ __('two_fa.device.seconds_suffix') }}後に再送信可能)`;
                resendCountdown.classList.remove('hidden');
                
                const countdownInterval = setInterval(() => {
                    countdown--;
                    if (countdown > 0) {
                        resendCountdown.textContent = `(${countdown}{{ __('two_fa.device.seconds_suffix') }}後に再送信可能)`;
                    } else {
                        clearInterval(countdownInterval);
                        resendCountdown.classList.add('hidden');
                        resendButton.disabled = false;
                    }
                }, 1000);
            } else {
                resendButton.textContent = '{{ __('two_fa.device.resend_button') }}';
                resendButton.disabled = false;
                alert(data.message || '再送信に失敗しました');
            }
        })
        .catch(error => {
            console.error('Resend error:', error);
            resendButton.textContent = '{{ __('two_fa.device.resend_button') }}';
            resendButton.disabled = false;
            alert('ネットワークエラーが発生しました');
        });
    }
    
    if (resendButton) {
        resendButton.addEventListener('click', resendDeviceAuth);
    }
    @endif

    // イベントリスナー
    document.getElementById('start-device-auth').addEventListener('click', startDeviceChallenge);
    document.getElementById('retry-device-auth').addEventListener('click', function() {
        showWaiting();
        startDeviceChallenge();
    });
    document.getElementById('retry-timeout-auth').addEventListener('click', function() {
        showWaiting();
        startDeviceChallenge();
    });

    // ページを離れる時にポーリングを停止
    window.addEventListener('beforeunload', function() {
        stopPolling();
        stopCountdown();
    });

    // 初期状態は待機状態
    @if($autoStart)
    // 自動開始モード：画面表示時に自動的にポーリングを開始（チャレンジは既に生成済み）
    showProcessing();
    startPolling();
    startCountdown();
    @else
    showWaiting();
    @endif
});
</script>
