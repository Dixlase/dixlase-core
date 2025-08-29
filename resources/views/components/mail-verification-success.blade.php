@php
    $translations = __('mail.verification_success');
    $alreadyVerified = $alreadyVerified ?? false;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $translations['title'] }} - {{ config('app.name', 'MySoftware') }}</title>
    
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
                    @if($alreadyVerified)
                        {{ __('mail.verification_success.already_verified_heading') }}
                    @else
                        {{ __('mail.verification_success.heading') }}
                    @endif
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    @if($alreadyVerified)
                        {{ __('mail.verification_success.already_verified_description') }}
                    @else
                        {{ __('mail.verification_success.description') }}
                    @endif
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
                            {{ __('mail.verification_success.next_steps_title') }}
                        </h3>
                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                            <ol class="list-decimal list-inside space-y-1">
                                @if(isset($isInstall) && $isInstall)
                                    @foreach(__('mail.verification_success.next_steps_install') as $step)
                                        <li>{{ $step }}</li>
                                    @endforeach
                                @else
                                    @foreach(__('mail.verification_success.next_steps') as $step)
                                        <li>{{ $step }}</li>
                                    @endforeach
                                @endif
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            @if(!isset($isInstall) || !$isInstall)
            <!-- 警告メッセージ（管理画面のみ） -->
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                            {{ __('mail.verification_success.important_notice_title') }}
                        </h3>
                        <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                            {{ __('mail.verification_success.important_notice') }}
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <!-- ボタン -->
            <div class="flex justify-center">
                <button onclick="closeWindow()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-200">
                    {{ __('mail.verification_success.close_button') }}
                </button>
            </div>
        </div>
    </div>

    <script>
        function closeWindow() {
            // 親ウィンドウにメッセージを送信
            if (window.opener) {
                window.opener.postMessage({
                    type: 'mail_receive_test_completed',
                    message: '{{ __('mail.verification_success.completed_message') }}'
                }, window.location.origin);
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

        // ページ読み込み時の処理
        window.addEventListener('load', function() {
            console.log('=== メール認証成功ページ読み込み完了 ===');
            
            // セッションストレージに受信テスト完了を記録
            try {
                sessionStorage.setItem('mail_receive_test_completed', 'true');
                sessionStorage.setItem('mail_receive_test_date', new Date().toLocaleString());
                console.log('✅ セッションストレージに受信テスト完了を記録しました');
            } catch (e) {
                console.error('❌ セッションストレージへの保存に失敗しました:', e);
            }
            
            // 親ウィンドウをリロード（可能な場合）
            if (window.opener && !window.opener.closed) {
                try {
                    console.log('親ウィンドウをリロードします...');
                    window.opener.location.reload();
                    console.log('✅ 親ウィンドウのリロード完了');
                } catch (e) {
                    console.error('❌ 親ウィンドウのリロードに失敗しました:', e);
                }
            } else {
                console.log('❌ 親ウィンドウが存在しないか閉じられています');
                console.log('💡 元のページに戻ってリロードしてください');
                
                // 代替案：BroadcastChannelを使用してタブ間通信
                try {
                    const channel = new BroadcastChannel('mail_test_channel');
                    channel.postMessage({
                        type: 'mail_receive_test_completed',
                        timestamp: new Date().toISOString()
                    });
                    console.log('✅ BroadcastChannelでメッセージを送信しました');
                    channel.close();
                } catch (e) {
                    console.error('❌ BroadcastChannelの送信に失敗しました:', e);
                }
            }
        });
    </script>
</body>
</html>
