@props([
    'challengeAction',
    'verifyAction',
    'title' => 'デバイス認証',
    'prompt' => 'デバイスでの認証を確認してください',
    'context' => 'admin',
    'pollInterval' => 2000,
    'maxRetries' => 30
])

<div id="device-auth-container">
    <!-- 認証待機状態 -->
    <div id="device-waiting" class="text-center">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
            {{ $title }}
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            {!! $prompt !!}
        </p>
        <button id="start-device-auth" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600">
            {{ __('two-factor.device.start_button') }}
        </button>
        <div class="mt-4 bg-gray-50 dark:bg-gray-700 rounded-md p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ __('two-factor.device.help_text') }}
            </p>
        </div>
    </div>

    <!-- 認証進行中状態 -->
    <div id="device-processing" class="text-center hidden">
        <div class="flex justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 dark:border-blue-400"></div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">
            {{ __('two-factor.device.remaining_time') }}: <span id="countdown">60</span>{{ __('two-factor.device.seconds_suffix') }}
        </p>
    </div>

    <!-- 認証成功状態 -->
    <div id="device-success" class="text-center hidden">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900 mb-4">
            <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
            {{ __('two-factor.device.success_title') }}
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ __('two-factor.device.success_message') }}
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
            {{ __('two-factor.device.error_title') }}
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4" id="device-error-message">
            {{ __('two-factor.device.error_message') }}
        </p>
        <button id="retry-device-auth" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600">
            {{ __('two-factor.device.retry_button') }}
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
            {{ __('two-factor.device.timeout_title') }}
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            {{ __('two-factor.device.timeout_message') }}
        </p>
        <button id="retry-timeout-auth" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600">
            {{ __('two-factor.device.retry_button') }}
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let challengeId = null;
    let pollInterval = null;
    let countdownInterval = null;
    let retryCount = 0;
    let remainingTime = 60;

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
                showError(data.message || '{{ __('two-factor.device.challenge_start_failed') }}');
            }
        })
        .catch(error => {
            console.error('Device challenge error:', error);
            showError('{{ __('two-factor.device.network_error') }}');
        });
    }

    // 認証状態をポーリング
    function startPolling() {
        pollInterval = setInterval(() => {
            if (!challengeId) return;

            fetch('{{ $verifyAction }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    challenge_id: challengeId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.status === 'completed') {
                        stopPolling();
                        stopCountdown();
                        showSuccess();
                        // 認証成功後、適切なページにリダイレクト
                        setTimeout(() => {
                            @if($context === 'admin')
                            window.location.href = '{{ route("admin.dashboard") }}';
                            @else
                            window.location.reload();
                            @endif
                        }, 2000);
                    } else if (data.status === 'failed') {
                        stopPolling();
                        stopCountdown();
                        showError(data.message || '{{ __('two-factor.device.auth_denied') }}');
                    } else if (data.status === 'expired') {
                        stopPolling();
                        stopCountdown();
                        showTimeout();
                    }
                    // pending状態の場合は継続してポーリング
                } else {
                    retryCount++;
                    if (retryCount >= {{ $maxRetries }}) {
                        stopPolling();
                        stopCountdown();
                        showTimeout();
                    }
                }
            })
            .catch(error => {
                console.error('Polling error:', error);
                retryCount++;
                if (retryCount >= {{ $maxRetries }}) {
                    stopPolling();
                    stopCountdown();
                    showError('{{ __('two-factor.device.network_error') }}');
                }
            });
        }, {{ $pollInterval }});
    }

    // カウントダウン開始
    function startCountdown() {
        const countdownElement = document.getElementById('countdown');
        countdownInterval = setInterval(() => {
            remainingTime--;
            if (countdownElement) {
                countdownElement.textContent = remainingTime;
            }
            
            if (remainingTime <= 0) {
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
        if (countdownInterval) {
            clearInterval(countdownInterval);
            countdownInterval = null;
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
    showWaiting();
});
</script>
