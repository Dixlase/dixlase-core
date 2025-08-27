@php
    $translations = __('mail.verification_error');
    $errorType = $errorType ?? 'invalid_token';
    $errorMessage = $errorMessage ?? '';
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
                <!-- エラーアイコン -->
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 dark:bg-red-900/20">
                    <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                
                <!-- メッセージ -->
                <h2 class="mt-6 text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $translations['heading'] }}
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    @if($errorType === 'invalid_token')
                        {{ $translations['invalid_token_description'] }}
                    @elseif($errorType === 'verification_error')
                        {{ $translations['verification_error_description'] }}
                    @else
                        {{ $translations['general_error_description'] }}
                    @endif
                </p>
                
                @if($errorMessage)
                <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <p class="text-sm text-red-700 dark:text-red-300">
                        {{ $errorMessage }}
                    </p>
                </div>
                @endif
            </div>

            <!-- 対処方法 -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                            {{ $translations['solution_title'] }}
                        </h3>
                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                            <ol class="list-decimal list-inside space-y-1">
                                @foreach($translations['solution_steps'] as $step)
                                    <li>{{ $step }}</li>
                                @endforeach
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ボタン -->
            <div class="flex justify-center">
                <button onclick="closeWindow()" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-200">
                    {{ $translations['close_button'] }}
                </button>
            </div>
        </div>
    </div>

    <script>
        function closeWindow() {
            // 親ウィンドウにエラーメッセージを送信
            if (window.opener) {
                window.opener.postMessage({
                    type: 'mail_verification_error',
                    message: '{{ $translations['error_occurred'] }}'
                }, window.location.origin);
            }
            
            // ウィンドウを閉じる
            window.close();
        }

        // 10秒後に自動でウィンドウを閉じる
        setTimeout(function() {
            if (window.opener) {
                window.close();
            }
        }, 10000);
    </script>
</body>
</html>
