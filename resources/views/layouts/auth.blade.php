<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') | {{ config('app.name') }}</title>

    <!-- Dark Mode Detection Script -->
    <script @cspNonce>
        // デバイスの外観モードを検出してHTMLクラスに適用
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        
        // 外観モード変更の監視
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            if (e.matches) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        });
    </script>

    <!-- Scripts -->
    {!! load_auth_assets() !!}
</head>
<body class="bg-gray-100 dark:bg-black flex items-center justify-center min-h-screen transition-colors duration-300">
    <div class="flex flex-col items-center w-full max-w-lg min-w-[400px]">

        <!-- ロゴ -->
        <img src="{{ asset('assets/images/logo.svg') }}" alt="{{ config('app.name') }}" class="w-32 h-auto mx-auto mb-4">

        <div class="bg-white dark:bg-gray-900 shadow-lg rounded-lg p-12 w-full mb-4 transition-colors duration-300">
            @hasSection('icon')
                @php
                    $iconSection = trim(View::yieldContent('icon'));
                    // アイコンが配列形式（JSON）かチェック
                    $icons = json_decode($iconSection, true);
                    if (!is_array($icons)) {
                        // 配列でない場合は単一アイコンとして扱う
                        $icons = [$iconSection];
                    }
                @endphp
                <div class="mx-auto flex items-center justify-center gap-2 mb-6">
                    @foreach($icons as $icon)
                        <div class="flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 dark:bg-blue-900">
                            <i class="{{ $icon }} text-2xl text-blue-600 dark:text-blue-400"></i>
                        </div>
                    @endforeach
                </div>
            @endif
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white mb-4 text-center">@yield('header')</h1>
            <div class="text-gray-600 dark:text-gray-300 mb-6 text-center">@yield('description')</div>
            <x-flash-message />
            @yield('content')
        </div>

        @hasSection('back_link')
            <div class="mt-4 text-center">
                @yield('back_link')
            </div>
        @endif
    </div>
    
    @stack('scripts')
</body>
</html>
