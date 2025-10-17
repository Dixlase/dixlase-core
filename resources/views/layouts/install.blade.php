<!DOCTYPE html>
<html lang="{{ $currentLocale ?? 'en' }}" x-data="installTheme()" x-init="init()" :class="{ 'dark': isDark, 'light': !isDark }">
<head>
    <script>
        // Alpine.js ダークモード検出関数
        function installTheme() {
            return {
                isDark: false,
                
                init() {
                    // PCのダークモード設定を検出
                    this.isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    
                    // ダークモード設定の変更を監視
                    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                        this.isDark = e.matches;
                    });
                }
            }
        }
        
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
            'resources/src/common/scss/style.scss'
        ])
    @else
        {{-- 本番環境ではmanifest.jsonを読み込み --}}
        @vite(['resources/src/common/js/app.js', 'resources/src/common/scss/style.scss'], 'build')
    @endif


</head>
<body class="bg-gray-100 dark:bg-gray-900 flex items-center justify-center min-h-screen transition-colors duration-200">
    <div class="flex flex-col items-center w-full max-w-xl min-w-[400px] my-10">

        <!-- Site Logo -->
        <div class="mb-4">
            <img src="{{ asset('assets/images/logo.svg') }}" alt="{{ env('APP_NAME') }}" class="w-32 h-auto mx-auto">
        </div>

        <!-- Main Installation Container -->
        <main class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8 max-w-xl w-full transition-colors duration-200" role="main">

            <!-- Installation Header -->
            <header class="mb-6">
                <!-- Step Progress and Language Selector -->
                <div class="flex justify-between items-center w-full max-w-xl mb-4">
                    <!-- Step Progress Indicator -->
                    @if(isset($current_step) && isset($total_steps))
                        <nav aria-label="{{ __('install.installation_progress') }}" class="text-gray-600 dark:text-gray-300">
                            {{ __('install.step_of_total', ['current' => $current_step, 'total' => $total_steps]) }}
                        </nav>
                    @else
                        <div aria-hidden="true"></div>
                    @endif

                    <!-- Language Selector -->
                    @if(isset($availableLocales) && isset($currentLocale))
                        <div aria-label="{{ __('install.language_selection') }}">
                            <form id="language-form" action="{{ route('install.language', ['locale' => '__locale__']) }}" method="POST">
                                @csrf
                                @php
                                    $languageOptions = [];
                                    foreach($availableLocales as $locale) {
                                        $languageOptions[$locale] = 'install.languages.' . $locale;
                                    }
                                @endphp
                                <x-form.select
                                    id="language-selector"
                                    name="locale"
                                    :options="$languageOptions"
                                    :value="$currentLocale"
                                    :useDefaultClass="false"
                                    class="appearance-none bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg py-2 px-4 pr-8 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400"
                                    style="-webkit-appearance: none; -moz-appearance: none; text-indent: 1px; text-overflow: ''; min-width: 150px;"
                                />
                            </form>
                        </div>
                    @endif
                </div>
                
                <!-- Page Title and Description -->
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white mb-4 text-center">@yield('header')</h1>
                    <p class="text-gray-600 dark:text-gray-300 text-center">
                        @yield('description')
                    </p>
                </div>
            </header>

            <!-- Error Messages -->
            @if(session('error'))
                <aside class="bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 p-3 mb-4 rounded-lg border border-red-200 dark:border-red-800" role="alert" aria-live="polite">
                    <strong class="sr-only">{{ __('install.error') }}:</strong>
                    {{ session('error') }}
                </aside>
            @endif

            @if(isset($errors) && $errors->any())
                <aside class="bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 p-3 mb-4 rounded-lg border border-red-200 dark:border-red-800" role="alert" aria-live="polite">
                    <strong class="font-semibold">{{ __('install.validation_errors') }}:</strong>
                    <ul class="list-disc list-inside mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </aside>
            @endif

            <!-- Page Content -->
            <article>
                @yield('content')
            </article>
        </main>
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