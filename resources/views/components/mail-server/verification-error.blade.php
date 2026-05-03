{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact office@exc-d.com).

Unless you have entered into a commercial license agreement, this
file is governed by the AGPL terms below.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@php
    $translations = __('mail-server/verification.verification_error');
    $errorType = $errorType ?? 'invalid_token';
    $errorMessage = $errorMessage ?? '';
    
    // Server-side dark mode detection (Strict CSP compliant)
    $prefersDark = request()->cookie('prefers_dark') === '1';
    $htmlClass = $prefersDark ? 'dark' : '';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $htmlClass }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>{{ $translations['title'] }} - {{ config('app.name', 'MySoftware') }}</title>
    
    <!-- Tailwind CSS + Verification Scripts (Strict CSP) -->
    {!! load_mail_verification_assets('error') !!}
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <main class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8" role="main">
        <article class="max-w-md w-full space-y-8">
            <!-- エラーメッセージヘッダー -->
            <header class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 dark:bg-red-900/20" aria-hidden="true">
                    <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                
                <h1 class="mt-6 text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $translations['heading'] }}
                </h1>
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
                <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg" role="alert">
                    <p class="text-sm text-red-700 dark:text-red-300">
                        {{ $errorMessage }}
                    </p>
                </div>
                @endif
            </header>

            <!-- 対処方法セクション -->
            <section aria-labelledby="solution-heading" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0" aria-hidden="true">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h2 id="solution-heading" class="text-sm font-medium text-blue-800 dark:text-blue-200">
                            {{ $translations['solution_title'] }}
                        </h2>
                        <ol class="mt-2 text-sm text-blue-700 dark:text-blue-300 list-decimal list-inside space-y-1">
                            @foreach($translations['solution_steps'] as $step)
                                <li>{{ $step }}</li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </section>

            <!-- アクションボタン -->
            <nav class="flex justify-center" aria-label="{{ __('mail-server/verification.verification_error.actions') }}">
                <button 
                    type="button"
                    id="close-verification-btn"
                    data-message="{{ $translations['error_occurred'] }}"
                    class="bg-gray-600 dark:bg-gray-500 hover:bg-gray-700 dark:hover:bg-gray-600 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-200">
                    {{ $translations['close_button'] }}
                </button>
            </nav>
        </article>
    </main>
</body>
</html>
