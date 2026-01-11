{{-- パーシャル用変数のデフォルト値設定 --}}
@php
    $context = $context ?? 'admin';
    $hasPasskeyDevices = $hasPasskeyDevices ?? true;
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

<script @cspNonce>
document.addEventListener('DOMContentLoaded', function() {
    let challengeData = null;

    // WebAuthn対応チェック
    function checkWebAuthnSupport() {
        if (!window.PublicKeyCredential) {
            showUnsupported();
            return false;
        }
        return true;
    }

    // Passkeyチャレンジを開始
    function startPasskeyChallenge() {
        if (!checkWebAuthnSupport()) {
            return;
        }

        showProcessing();

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
                challengeData = data.challenge;
                performPasskeyAuth();
            } else {
                showError(data.message || 'チャレンジの開始に失敗しました');
            }
        })
        .catch(error => {
            console.error('Passkey challenge error:', error);
            showError('ネットワークエラーが発生しました');
        });
    }

    // WebAuthn認証を実行
    function performPasskeyAuth() {
        if (!challengeData) {
            showError('チャレンジデータがありません');
            return;
        }

        // Base64URLデコード
        const challenge = Uint8Array.from(atob(challengeData.challenge.replace(/-/g, '+').replace(/_/g, '/')), c => c.charCodeAt(0));
        
        const allowCredentials = challengeData.allowCredentials.map(cred => ({
            id: Uint8Array.from(atob(cred.id.replace(/-/g, '+').replace(/_/g, '/')), c => c.charCodeAt(0)),
            type: cred.type,
            transports: cred.transports
        }));

        const publicKeyCredentialRequestOptions = {
            challenge: challenge,
            allowCredentials: allowCredentials,
            timeout: challengeData.timeout || 60000,
            userVerification: challengeData.userVerification || 'preferred'
        };

        navigator.credentials.get({
            publicKey: publicKeyCredentialRequestOptions
        })
        .then(credential => {
            // 認証結果をサーバーに送信
            const response = {
                id: credential.id,
                rawId: btoa(String.fromCharCode(...new Uint8Array(credential.rawId))),
                response: {
                    authenticatorData: btoa(String.fromCharCode(...new Uint8Array(credential.response.authenticatorData))),
                    clientDataJSON: btoa(String.fromCharCode(...new Uint8Array(credential.response.clientDataJSON))),
                    signature: btoa(String.fromCharCode(...new Uint8Array(credential.response.signature))),
                    userHandle: credential.response.userHandle ? btoa(String.fromCharCode(...new Uint8Array(credential.response.userHandle))) : null
                },
                type: credential.type
            };

            return fetch('{{ $verifyAction }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    challenge_id: challengeData.id,
                    response: response
                })
            });
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess();
                // 認証成功後、サーバーから返されたURLにリダイレクト
                setTimeout(() => {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        @if($context === 'admin')
                        window.location.href = '{{ route("admin.dashboard") }}';
                        @else
                        window.location.href = '{{ route("users-plugin::mypage.dashboard") }}';
                        @endif
                    }
                }, 2000);
            } else {
                showError(data.message || '認証の検証に失敗しました');
            }
        })
        .catch(error => {
            console.error('Passkey authentication error:', error);
            if (error.name === 'NotAllowedError') {
                showError('認証がキャンセルされました');
            } else if (error.name === 'InvalidStateError') {
                showError('認証の状態が無効です');
            } else if (error.name === 'NotSupportedError') {
                showUnsupported();
            } else {
                showError('生体認証に失敗しました');
            }
        });
    }

    // 各状態表示関数
    function showWaiting() {
        hideAllStates();
        document.getElementById('passkey-waiting').classList.remove('hidden');
    }

    function showProcessing() {
        hideAllStates();
        document.getElementById('passkey-processing').classList.remove('hidden');
    }

    function showSuccess() {
        hideAllStates();
        document.getElementById('passkey-success').classList.remove('hidden');
    }

    function showError(message) {
        hideAllStates();
        document.getElementById('passkey-error').classList.remove('hidden');
        document.getElementById('passkey-error-message').textContent = message;
    }

    function showUnsupported() {
        hideAllStates();
        document.getElementById('passkey-unsupported').classList.remove('hidden');
    }

    function hideAllStates() {
        document.getElementById('passkey-waiting').classList.add('hidden');
        document.getElementById('passkey-processing').classList.add('hidden');
        document.getElementById('passkey-success').classList.add('hidden');
        document.getElementById('passkey-error').classList.add('hidden');
        document.getElementById('passkey-unsupported').classList.add('hidden');
    }

    // イベントリスナー
    document.getElementById('start-passkey-auth').addEventListener('click', startPasskeyChallenge);
    document.getElementById('retry-passkey-auth').addEventListener('click', function() {
        showWaiting();
        startPasskeyChallenge();
    });

    // 初期化
    if (!checkWebAuthnSupport()) {
        showUnsupported();
    } else {
        showWaiting();
    }
});
</script>
