<!DOCTYPE html>
<html lang="{{ $currentLocale ?? 'en' }}">
<head>
    <script>
        // ブラウザの言語設定を取得して、利用可能な言語と照合する
        function detectBrowserLanguage(availableLocales) {
            // ブラウザの言語設定を取得
            const browserLanguages = navigator.languages || [navigator.language || navigator.userLanguage];
            
            // 利用可能な言語と照合
            for (const lang of browserLanguages) {
                // 完全一致を確認 (例: 'ja')
                if (availableLocales.includes(lang)) {
                    return lang;
                }
                
                // 言語コードのみで一致を確認 (例: 'ja-JP' から 'ja' を抽出)
                const langCode = lang.split('-')[0];
                if (availableLocales.includes(langCode)) {
                    return langCode;
                }
            }
            
            // デフォルトは 'en' を返す
            return 'en';
        }
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js" crossorigin="anonymous"></script>

        <!-- Scripts -->
    @if (app()->environment('local'))
        {{-- 開発環境ではリソースを直接読み込み --}}
        @vite([
            'resources/src/common/js/app.js',
            'resources/src/common/scss/app.scss'
        ])
    @else
        {{-- 本番環境ではmanifest.jsonを読み込み --}}
        @vite(['resources/src/common/js/app.js', 'resources/src/common/scss/app.scss'], 'build')
    @endif


</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="flex flex-col items-center w-full max-w-xl min-w-[400px]">

        <img src="{{ asset('assets/images/logo.svg') }}" alt="{{ env('APP_NAME') }}" class="w-32 h-auto mx-auto mb-4">

    
        <div class="bg-white shadow-lg rounded-lg p-8 max-w-xl w-full">

            <!-- Header with Step Counter and Language Selector -->
            <div class="flex justify-between items-center w-full max-w-xl mb-4">
                <!-- Step Counter -->
                @if(isset($current_step) && isset($total_steps))
                    <div class="text-gray-600">
                        {{ __('install.step_of_total', ['current' => $current_step, 'total' => $total_steps]) }}
                    </div>
                @else
                    <div></div> <!-- This empty div ensures the language selector stays on the right -->
                @endif

                <!-- Language Selector -->
                @if(isset($availableLocales) && isset($currentLocale))
                    <div class="relative">
                        <form id="language-form" action="{{ route('install.language', ['locale' => '__locale__']) }}" method="POST">
                            @csrf
                            <select 
                                id="language-selector" 
                                class="appearance-none bg-white border border-gray-300 rounded-lg py-2 px-4 pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                style="-webkit-appearance: none; -moz-appearance: none; text-indent: 1px; text-overflow: ''; min-width: 150px;"
                            >
                                @foreach($availableLocales as $locale)
                                    <option value="{{ $locale }}" {{ $currentLocale === $locale ? 'selected' : '' }}>
                                        {{ __('install.languages.' . $locale) }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                @endif
            </div>
            
            <h1 class="text-2xl font-bold text-gray-800 mb-4 text-center">@yield('header')</h1>
            <p class="text-gray-600 mb-6 text-center">
                @yield('description')
            </p>

            @if(session('error'))
                <div class="bg-red-100 text-red-600 p-3 mb-4 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            @if(isset($errors) && $errors->any())
                <div class="bg-red-100 text-red-600 p-3 mb-4 rounded-lg">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    @if(isset($availableLocales) && isset($currentLocale))
    <script>
        // ページ読み込み時に実行
        document.addEventListener('DOMContentLoaded', function() {
            const availableLocales = @json($availableLocales);
            const currentLocale = '{{ $currentLocale }}';
            
            // セッションが開始されているかチェック（セッションまたはクッキーに言語設定があるか）
            const hasExistingSession = sessionStorage.getItem('language_manually_changed') || 
                                     '{{ session("install_locale") }}' || 
                                     document.cookie.includes('install_locale=');
            
            // セッションが存在しない初回のみブラウザ言語を自動検出
            if (!hasExistingSession && currentLocale === '{{ config('app.fallback_locale') }}' && !new URLSearchParams(window.location.search).has('lang')) {
                const detectedLang = detectBrowserLanguage(availableLocales);
                if (detectedLang && detectedLang !== currentLocale) {
                    // 初回のみAJAXで言語を変更
                    changeLanguage(detectedLang);
                }
            }

            // 言語切り替えフォームの送信処理
            const languageForm = document.getElementById('language-form');
            const languageSelector = document.getElementById('language-selector');
            
            if (languageForm && languageSelector) {
                // セレクト変更で即時適用
                languageSelector.addEventListener('change', function(e) {
                    e.preventDefault();
                    const locale = languageSelector.value;
                    console.log('Language selector changed to:', locale);
                    languageSelector.disabled = true;
                    changeLanguage(locale);
                });

                // フォーム送信を完全に無効化
                languageForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    console.log('Form submit prevented');
                    return false;
                });
            }

            function changeLanguage(locale) {
                // 手動変更フラグを設定
                sessionStorage.setItem('language_manually_changed', 'true');
                
                fetch(`{{ url('/install/language') }}/${locale}`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({})
                })
                .then(async (response) => {
                    const contentType = response.headers.get('content-type') || '';
                    if (!response.ok) {
                        const text = await response.text().catch(() => '');
                        console.error('Language change failed. Status:', response.status, text);
                        throw new Error('Request failed');
                    }
                    if (contentType.includes('application/json')) {
                        return response.json();
                    } else {
                        // 予期せぬHTML等が返った場合でもリロードを試みる
                        return { success: true };
                    }
                })
                .then(data => {
                    console.log('Language change response:', data);
                    if (data && data.success) {
                        console.log('Language change successful, reloading page...');
                        window.location.reload();
                    } else {
                        console.error('Language change failed:', data);
                        alert((data && data.message) || '{{ __("Language switch failed.") }}');
                        if (languageSelector) languageSelector.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('{{ __("An error occurred. Please reload the page.") }}');
                    if (languageSelector) languageSelector.disabled = false;
                });
            }
        });
    </script>
    @endif
</body>
</html>