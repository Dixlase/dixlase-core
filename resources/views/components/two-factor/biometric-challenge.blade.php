@props([
    'challengeAction',
    'verifyAction',
    'title' => '生体認証',
    'prompt' => '生体認証を使用してログインしてください',
    'context' => 'admin'
])

<div id="biometric-auth-container">
    <!-- 認証待機状態 -->
    <div id="biometric-waiting" class="text-center">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 dark:bg-blue-900 mb-4">
            <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
            {{ $title }}
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            {{ $prompt }}
        </p>
        <button id="start-biometric-auth" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
            </svg>
            認証を開始
        </button>
        <div class="mt-4 bg-gray-50 dark:bg-gray-700 rounded-md p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Touch ID、Face ID、Windows Hello等の生体認証を使用してください。
            </p>
        </div>
    </div>

    <!-- 認証進行中状態 -->
    <div id="biometric-processing" class="text-center hidden">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 dark:bg-yellow-900 mb-4">
            <svg class="animate-pulse h-8 w-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
            生体認証を実行中...
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            デバイスの生体認証センサーを使用してください。
        </p>
    </div>

    <!-- 認証成功状態 -->
    <div id="biometric-success" class="text-center hidden">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900 mb-4">
            <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
            認証成功
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            生体認証が完了しました。リダイレクトしています...
        </p>
    </div>

    <!-- 認証失敗状態 -->
    <div id="biometric-error" class="text-center hidden">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 dark:bg-red-900 mb-4">
            <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
            認証失敗
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4" id="biometric-error-message">
            生体認証に失敗しました。再試行してください。
        </p>
        <button id="retry-biometric-auth" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600">
            再試行
        </button>
    </div>

    <!-- 未サポート状態 -->
    <div id="biometric-unsupported" class="text-center hidden">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
            <svg class="h-8 w-8 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
            生体認証未対応
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            お使いのデバイスまたはブラウザは生体認証に対応していません。
        </p>
    </div>
</div>

<script>
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

    // 生体認証チャレンジを開始
    function startBiometricChallenge() {
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
                performBiometricAuth();
            } else {
                showError(data.message || 'チャレンジの開始に失敗しました');
            }
        })
        .catch(error => {
            console.error('Biometric challenge error:', error);
            showError('ネットワークエラーが発生しました');
        });
    }

    // WebAuthn認証を実行
    function performBiometricAuth() {
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
                // 認証成功後、適切なページにリダイレクト
                setTimeout(() => {
                    @if($context === 'admin')
                    window.location.href = '{{ route("admin.dashboard") }}';
                    @else
                    window.location.reload();
                    @endif
                }, 2000);
            } else {
                showError(data.message || '認証の検証に失敗しました');
            }
        })
        .catch(error => {
            console.error('Biometric authentication error:', error);
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
        document.getElementById('biometric-waiting').classList.remove('hidden');
    }

    function showProcessing() {
        hideAllStates();
        document.getElementById('biometric-processing').classList.remove('hidden');
    }

    function showSuccess() {
        hideAllStates();
        document.getElementById('biometric-success').classList.remove('hidden');
    }

    function showError(message) {
        hideAllStates();
        document.getElementById('biometric-error').classList.remove('hidden');
        document.getElementById('biometric-error-message').textContent = message;
    }

    function showUnsupported() {
        hideAllStates();
        document.getElementById('biometric-unsupported').classList.remove('hidden');
    }

    function hideAllStates() {
        document.getElementById('biometric-waiting').classList.add('hidden');
        document.getElementById('biometric-processing').classList.add('hidden');
        document.getElementById('biometric-success').classList.add('hidden');
        document.getElementById('biometric-error').classList.add('hidden');
        document.getElementById('biometric-unsupported').classList.add('hidden');
    }

    // イベントリスナー
    document.getElementById('start-biometric-auth').addEventListener('click', startBiometricChallenge);
    document.getElementById('retry-biometric-auth').addEventListener('click', function() {
        showWaiting();
        startBiometricChallenge();
    });

    // 初期化
    if (!checkWebAuthnSupport()) {
        showUnsupported();
    } else {
        showWaiting();
    }
});
</script>
