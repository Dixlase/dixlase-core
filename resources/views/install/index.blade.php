@extends('layouts.install')

@section('title', __('install.title'))

@section('content')
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
        
        // ページ読み込み時に実行
        document.addEventListener('DOMContentLoaded', function() {
            const availableLocales = @json($availableLocales);
            const currentLocale = '{{ $currentLocale }}';
            
            // 現在の言語がデフォルトのままで、クエリパラメータで言語指定がない場合にのみ自動検出を実行
            if (currentLocale === config('app.fallback_locale') && !new URLSearchParams(window.location.search).has('lang')) {
                const detectedLang = detectBrowserLanguage(availableLocales);
                if (detectedLang && detectedLang !== currentLocale) {
                    // 言語を変更するフォームを送信
                    const form = document.getElementById('language-form');
                    form.action = form.action.replace('__locale__', detectedLang);
                    form.submit();
                }
            }
        });
    </script>
    
    <!-- Language Selector -->
    <div class="flex justify-end mb-4">
        <div class="relative">
            <form id="language-form" action="{{ route('install.language', '__locale__') }}" method="GET">
                <select 
                    id="language-selector" 
                    class="appearance-none bg-white border border-gray-300 rounded-lg py-2 px-4 pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    onchange="this.form.action=this.form.action.replace('__locale__', this.value); this.form.submit()"
                >
                    @foreach($availableLocales as $locale)
                        <option value="{{ $locale }}" {{ $currentLocale === $locale ? 'selected' : '' }}>
                            {{ __('install.languages.' . $locale) }}
                        </option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/>
                    </svg>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const languageForm = document.getElementById('language-form');
            const languageSelector = document.getElementById('language-selector');
            
            languageForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const locale = languageSelector.value;
                
                // ローディング状態を表示
                languageSelector.disabled = true;
                
                // AJAXリクエストを送信
                fetch(`{{ route('install.language', '') }}/${locale}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // ページをリロードして変更を反映
                        window.location.reload();
                    } else {
                        alert(data.message || '言語の切り替えに失敗しました。');
                        languageSelector.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('エラーが発生しました。ページをリロードしてください。');
                    languageSelector.disabled = false;
                });
            });
        });
    </script>
    @endpush

    <h1 class="text-2xl font-bold text-gray-800 mb-4 text-center">
        {{ config('app.name') }}<br>
        {{ __('install.welcome') }}
    </h1>
    <p class="text-gray-600 mb-6">
        {{ __('install.description') }}
    </p>

    <!-- ✅ 環境チェック -->
    <div class="mb-6 p-4 bg-gray-100 rounded-lg">
        <h2 class="text-lg font-bold text-gray-800 mb-2">{{ __('install.server_requirements') }}</h2>
        <ul class="text-sm space-y-1">
            <li>
                <strong>PHP 8.2+</strong>:
                <span class="{{ $requirements['php'] ? 'text-green-600' : 'text-red-600' }}">
                    {{ $requirements['php'] ? __('install.ok') : __('install.failed') }}
                </span>
            </li>
            @foreach ($requirements['required_extensions'] as $ext => $status)
                <li>
                    <strong>{{ $ext }}</strong> ({{ __('install.required') }}):
                    <span class="{{ $status ? 'text-green-600' : 'text-red-600' }}">
                        {{ $status ? __('install.ok') : __('install.failed') }}
                    </span>
                </li>
            @endforeach
            @foreach ($requirements['optional_extensions'] as $ext => $status)
                <li>
                    <strong>{{ $ext }}</strong> ({{ __('install.optional') }}):
                    <span class="{{ $status ? 'text-green-600' : 'text-yellow-600' }}">
                        {{ $status ? __('install.ok') : __('install.not_required') }}
                    </span>
                </li>
            @endforeach
            <li>
                <strong>{{ __('install.permissions.storage') }}</strong>:
                <span class="{{ $requirements['permissions']['storage'] ? 'text-green-600' : 'text-red-600' }}">
                    {{ $requirements['permissions']['storage'] ? __('install.ok') : __('install.failed') }}
                </span>
            </li>
            <li>
                <strong>{{ __('install.permissions.cache') }}</strong>:
                <span class="{{ $requirements['permissions']['bootstrap/cache'] ? 'text-green-600' : 'text-red-600' }}">
                    {{ $requirements['permissions']['bootstrap/cache'] ? __('install.ok') : __('install.failed') }}
                </span>
            </li>
        </ul>
    </div>

    <!-- ✅ インストールボタン -->
    @php
        $hasRequiredIssues = !$requirements['php'] || 
                           in_array(false, $requirements['required_extensions']) || 
                           in_array(false, $requirements['permissions']);
    @endphp
    <a href="{{ $hasRequiredIssues ? '#' : route('install.settings') }}"
       class="block bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition text-center
              {{ $hasRequiredIssues ? 'opacity-50 cursor-not-allowed' : '' }}"
       {{ $hasRequiredIssues ? 'disabled' : '' }}>
        {{ __('install.start_button') }}
    </a>
    
    @if($hasRequiredIssues)
        <p class="text-sm text-red-600 mt-2">
            {{ __('install.required_issues') }}
        </p>
    @endif

@endsection