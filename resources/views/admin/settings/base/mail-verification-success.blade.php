<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('admin.settings.base.mail_verification_success.title') }} - {{ config('app.name', 'MySoftware') }}</title>
    
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
                    {{ __('admin.settings.base.mail_verification_success.heading') }}
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('admin.settings.base.mail_verification_success.description') }}
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
                            {{ __('admin.settings.base.mail_verification_success.next_steps_title') }}
                        </h3>
                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                            <ol class="list-decimal list-inside space-y-1">
                                <li>{{ __('admin.settings.base.mail_verification_success.next_steps.close_window') }}</li>
                                <li>{{ __('admin.settings.base.mail_verification_success.next_steps.save_settings') }}</li>
                                <li>{{ __('admin.settings.base.mail_verification_success.next_steps.data_saved') }}</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 警告メッセージ -->
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                            {{ __('admin.settings.base.mail_verification_success.important_notice_title') }}
                        </h3>
                        <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                            {{ __('admin.settings.base.mail_verification_success.important_notice') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- ボタン -->
            <div class="flex justify-center">
                <button onclick="window.close()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-200">
                    {{ __('admin.settings.base.mail_verification_success.close_button') }}
                </button>
            </div>
        </div>
    </div>

    <script>
        // 5秒後に自動でウィンドウを閉じる
        setTimeout(function() {
            if (window.opener) {
                // 親ウィンドウが存在する場合のみ自動で閉じる
                window.close();
            }
        }, 5000);

        // ページ読み込み時に親ウィンドウにメッセージを送信（可能な場合）
        window.addEventListener('load', function() {
            if (window.opener && !window.opener.closed) {
                try {
                    // 親ウィンドウに受信テスト完了を通知
                    window.opener.postMessage({
                        type: 'mail_receive_test_completed',
                        message: '{{ __('admin.settings.base.mail_verification_success.description') }} {{ __('admin.settings.base.mail_verification_success.next_steps.save_settings') }}'
                    }, '*');
                } catch (e) {
                    console.log('親ウィンドウへのメッセージ送信に失敗しました:', e);
                }
            }
        });
    </script>
</body>
</html>
