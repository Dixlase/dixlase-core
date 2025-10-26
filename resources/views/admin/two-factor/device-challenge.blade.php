@extends('layouts.auth')

@section('title', 'デバイス認証')
@section('icon', 'fas fa-mobile-alt')
@section('header', __('two-factor.device.title'))
@section('description')
    {!! __('two-factor.device.prompt') !!}
@endsection

@section('content')
<div class="text-center">
    <div id="status-message" class="mb-4 p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
        <p class="text-sm text-gray-700 dark:text-gray-300">
            <i class="fas fa-spinner fa-spin mr-2"></i>
            承認待機中...
        </p>
    </div>

    <!-- 有効期限表示 -->
    <div class="mb-4">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            有効期限: <span id="expire-time">10分</span>
        </p>
    </div>

    <!-- 再送信ボタン -->
    <div class="mb-6 text-center">
        <button 
            type="button" 
            id="resend-button"
            class="text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
            onclick="resendDeviceAuth()"
        >
            認証メールを再送信
        </button>
        <span id="resend-countdown" class="text-xs text-gray-500 dark:text-gray-400 ml-2 hidden"></span>
    </div>
    
    @if(!empty($availableMethods))
        <div class="mt-6 text-center">
            <x-two-factor.alternative-methods
                :methods="$availableMethods"
                :current-method="$currentMethod"
                context="admin"
            />
        </div>
    @endif

    <div class="mt-6 text-center">
        <a href="{{ route('admin.login') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
            ← {{ __('two-factor.back_to_login') }}
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusMessage = document.getElementById('status-message');
    const resendButton = document.getElementById('resend-button');
    const resendCountdown = document.getElementById('resend-countdown');
    const expireTimeElement = document.getElementById('expire-time');
    let pollInterval;
    let expireTime = 600; // 10分 = 600秒
    let resendCooldown = 0;

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

        // status-messageの前に挿入
        statusMessage.parentNode.insertBefore(messageDiv, statusMessage);

        // 5秒後に自動削除
        setTimeout(() => {
            messageDiv.style.transition = 'opacity 0.5s';
            messageDiv.style.opacity = '0';
            setTimeout(() => messageDiv.remove(), 500);
        }, 5000);
    }
    
    // 有効期限のカウントダウン
    function updateExpireTime() {
        expireTime--;
        const minutes = Math.floor(expireTime / 60);
        const seconds = expireTime % 60;
        expireTimeElement.textContent = `${minutes}分${seconds}秒`;
        
        if (expireTime <= 0) {
            clearInterval(pollInterval);
            statusMessage.innerHTML = `
                <p class="text-sm text-red-700 dark:text-red-300">
                    <i class="fas fa-times-circle mr-2"></i>
                    認証リクエストが期限切れです。もう一度ログインしてください。
                </p>
            `;
        }
    }
    
    // 1秒ごとに有効期限を更新
    const expireInterval = setInterval(updateExpireTime, 1000);
    
    // 再送信のカウントダウン
    function updateResendCooldown() {
        if (resendCooldown > 0) {
            resendCooldown--;
            resendCountdown.textContent = `(${resendCooldown}秒後に再送信可能)`;
            resendCountdown.classList.remove('hidden');
            resendButton.disabled = true;
        } else {
            resendCountdown.classList.add('hidden');
            resendButton.disabled = false;
        }
    }
    
    // 再送信カウントダウンのインターバル
    const resendInterval = setInterval(updateResendCooldown, 1000);
    
    // 5秒ごとに承認状態をチェック
    function checkApprovalStatus() {
        fetch('{{ route('admin.device-auth.check') }}')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.status === 'approved') {
                    // 承認された
                    clearInterval(pollInterval);
                    clearInterval(expireInterval);
                    clearInterval(resendInterval);
                    statusMessage.innerHTML = `
                        <p class="text-sm text-green-700 dark:text-green-300">
                            <i class="fas fa-check-circle mr-2"></i>
                            承認されました！ログイン中...
                        </p>
                    `;
                    
                    // ダッシュボードにリダイレクト
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else if (data.status === 'expired') {
                    // 期限切れ
                    clearInterval(pollInterval);
                    clearInterval(expireInterval);
                    clearInterval(resendInterval);
                    statusMessage.innerHTML = `
                        <p class="text-sm text-red-700 dark:text-red-300">
                            <i class="fas fa-times-circle mr-2"></i>
                            認証リクエストが期限切れです。もう一度ログインしてください。
                        </p>
                    `;
                }
            })
            .catch(error => {
                console.error('Polling error:', error);
            });
    }
    
    // 初回チェック
    checkApprovalStatus();
    
    // 5秒ごとにポーリング
    pollInterval = setInterval(checkApprovalStatus, 5000);
});

// 認証メール再送信
function resendDeviceAuth() {
    const statusMessage = document.getElementById('status-message');
    const resendButton = document.getElementById('resend-button');
    const resendCountdown = document.getElementById('resend-countdown');
    
    // フラッシュメッセージ表示関数（DOMContentLoaded内の関数を再定義）
    function showFlashMessage(message, type = 'success') {
        const existingMessage = document.querySelector('.flash-message-dynamic');
        if (existingMessage) {
            existingMessage.remove();
        }

        const messageDiv = document.createElement('div');
        messageDiv.className = `flash-message-dynamic mb-6 p-4 font-semibold rounded-xl ${
            type === 'success' 
                ? 'text-green-800 bg-green-100 border border-green-200 dark:text-green-200 dark:bg-green-900 dark:border-green-700' 
                : 'text-red-800 bg-red-100 border border-red-200 dark:text-red-200 dark:bg-red-900 dark:border-red-700'
        }`;
        messageDiv.textContent = message;

        statusMessage.parentNode.insertBefore(messageDiv, statusMessage);

        setTimeout(() => {
            messageDiv.style.transition = 'opacity 0.5s';
            messageDiv.style.opacity = '0';
            setTimeout(() => messageDiv.remove(), 500);
        }, 5000);
    }
    
    resendButton.disabled = true;
    resendButton.textContent = '送信中...';
    
    fetch('{{ route('admin.device-auth.resend') }}', {
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
            
            resendButton.textContent = '認証メールを再送信';
            // 60秒のクールダウン
            let countdown = 60;
            resendCountdown.textContent = `(${countdown}秒後に再送信可能)`;
            resendCountdown.classList.remove('hidden');
            
            const countdownInterval = setInterval(() => {
                countdown--;
                if (countdown > 0) {
                    resendCountdown.textContent = `(${countdown}秒後に再送信可能)`;
                } else {
                    clearInterval(countdownInterval);
                    resendCountdown.classList.add('hidden');
                    resendButton.disabled = false;
                }
            }, 1000);
        } else {
            resendButton.textContent = '認証メールを再送信';
            resendButton.disabled = false;
            showFlashMessage(data.message || '再送信に失敗しました', 'error');
        }
    })
    .catch(error => {
        console.error('Resend error:', error);
        resendButton.textContent = '認証メールを再送信';
        resendButton.disabled = false;
        showFlashMessage('ネットワークエラーが発生しました', 'error');
    });
}
</script>
@endsection
