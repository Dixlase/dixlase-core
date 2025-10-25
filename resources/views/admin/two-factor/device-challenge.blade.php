@extends('layouts.auth')

@section('title', 'デバイス認証')
@section('icon', 'fas fa-mobile-alt')
@section('header', __('auth.two_factor.device.title'))
@section('description', __('auth.two_factor.device.prompt'))

@section('content')
<div class="text-center">
    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 dark:bg-blue-900 mb-6">
        <i class="fas fa-envelope text-3xl text-blue-600 dark:text-blue-400 animate-pulse"></i>
    </div>
    
    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
        {{ __('auth.two_factor.device.waiting_title') }}
    </h3>
    
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
        {{ __('auth.two_factor.device.waiting_message') }}
    </p>
    
    <div id="status-message" class="mb-6 p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
        <p class="text-sm text-gray-700 dark:text-gray-300">
            <i class="fas fa-spinner fa-spin mr-2"></i>
            承認待機中...
        </p>
    </div>
    
    @if(!empty($availableMethods))
        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                別の認証方法を使用:
            </p>
            @foreach($availableMethods as $method)
                <a href="{{ $method['url'] }}" 
                   class="inline-block px-4 py-2 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                    {{ $method['label'] }}
                </a>
            @endforeach
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusMessage = document.getElementById('status-message');
    let pollInterval;
    
    // 5秒ごとに承認状態をチェック
    function checkApprovalStatus() {
        fetch('{{ route('admin.device-auth.check') }}')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.status === 'approved') {
                    // 承認された
                    clearInterval(pollInterval);
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
    
    // 10分後にタイムアウト
    setTimeout(() => {
        clearInterval(pollInterval);
        statusMessage.innerHTML = `
            <p class="text-sm text-yellow-700 dark:text-yellow-300">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                タイムアウトしました。もう一度ログインしてください。
            </p>
        `;
    }, 600000); // 10分
});
</script>
@endsection
