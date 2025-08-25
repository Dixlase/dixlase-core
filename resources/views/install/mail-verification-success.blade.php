<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('install.mail_test_advanced.verification_success.title') }} - {{ config('app.name', 'MySoftware') }}</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <!-- 成功アイコン -->
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900/20">
                    <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                
                <!-- メッセージ -->
                <h2 class="mt-6 text-2xl font-bold text-gray-900 dark:text-white">
                    {{ __('install.mail_test_advanced.verification_success.heading') }}
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('install.mail_test_advanced.verification_success.description') }}
                </p>
            </div>

            <!-- 指示メッセージ -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                            {{ __('install.mail_test_advanced.verification_success.next_steps_title') }}
                        </h3>
                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                            <ol class="list-decimal list-inside space-y-1">
                                <li>{{ __('install.mail_test_advanced.verification_success.next_steps.close_window') }}</li>
                                <li>{{ __('install.mail_test_advanced.verification_success.next_steps.continue_install') }}</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ボタン -->
            <div class="flex justify-center">
                <button onclick="closeWindow()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-200">
                    {{ __('install.mail_test_advanced.verification_success.close_button') }}
                </button>
            </div>
        </div>
    </div>

    <script>
        function closeWindow() {
            console.log('=== closeWindow関数実行 ===');
            // 親ウィンドウにメッセージを送信
            if (window.opener) {
                console.log('closeWindow: 親ウィンドウにpostMessage送信中...');
                window.opener.postMessage({
                    type: 'mail_receive_test_completed',
                    message: '{{ __('install.mail_test_advanced.verification_success.completed_message') }}'
                }, window.location.origin);
                console.log('closeWindow: postMessage送信完了');
            }
            
            // ウィンドウを閉じる
            window.close();
        }

        // 5秒後に自動でウィンドウを閉じる
        setTimeout(function() {
            if (window.opener) {
                // 親ウィンドウが存在する場合のみ自動で閉じる
                window.close();
            }
        }, 5000);

        // ページ読み込み時に親ウィンドウにメッセージを送信（可能な場合）
        window.addEventListener('load', function() {
            console.log('=== メール認証成功ページ読み込み完了 ===');
            console.log('window.opener存在:', !!window.opener);
            console.log('window.opener.closed:', window.opener ? window.opener.closed : 'N/A');
            console.log('window.parent存在:', !!window.parent);
            console.log('window.parent === window:', window.parent === window);
            
            // 複数の方法でメッセージ送信を試行
            const message = {
                type: 'mail_receive_test_completed',
                message: '{{ __('install.mail_test_advanced.verification_success.completed_message') }}'
            };
            
            // 方法1: window.opener経由
            if (window.opener && !window.opener.closed) {
                try {
                    console.log('方法1: window.opener経由でpostMessage送信中...');
                    window.opener.postMessage(message, '*');
                    console.log('方法1: postMessage送信完了');
                } catch (e) {
                    console.log('方法1: 送信失敗:', e);
                }
            }
            
            // 方法2: window.parent経由（iframe内の場合）
            if (window.parent && window.parent !== window) {
                try {
                    console.log('方法2: window.parent経由でpostMessage送信中...');
                    window.parent.postMessage(message, '*');
                    console.log('方法2: postMessage送信完了');
                } catch (e) {
                    console.log('方法2: 送信失敗:', e);
                }
            }
            
            // 方法3: localStorage経由でのフォールバック
            try {
                console.log('方法3: localStorage経由でメッセージ保存中...');
                localStorage.setItem('mail_receive_test_completed', JSON.stringify({
                    timestamp: Date.now(),
                    message: message.message
                }));
                console.log('方法3: localStorage保存完了');
            } catch (e) {
                console.log('方法3: localStorage保存失敗:', e);
            }
            
            if (!window.opener && window.parent === window) {
                console.log('親ウィンドウが存在しません - 新しいタブで開かれた可能性があります');
            }
        });
    </script>
</body>
</html>
