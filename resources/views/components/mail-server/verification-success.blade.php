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
    $translations = __('mail-server/verification.verification_success');
    $alreadyVerified = $alreadyVerified ?? false;
    
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
    {!! load_mail_verification_assets('success') !!}
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <main class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8" role="main">
        <article class="max-w-md w-full space-y-8">
            <!-- 成功メッセージヘッダー -->
            <header class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900/20" aria-hidden="true">
                    <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                
                <h1 class="mt-6 text-2xl font-bold text-gray-900 dark:text-white">
                    @if($alreadyVerified)
                        {{ __('mail-server/verification.verification_success.already_verified_heading') }}
                    @else
                        {{ __('mail-server/verification.verification_success.heading') }}
                    @endif
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    @if($alreadyVerified)
                        {{ __('mail-server/verification.verification_success.already_verified_description') }}
                    @else
                        {{ __('mail-server/verification.verification_success.description') }}
                    @endif
                </p>
            </header>

            <!-- 次のステップセクション -->
            <section aria-labelledby="next-steps-heading" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0" aria-hidden="true">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h2 id="next-steps-heading" class="text-sm font-medium text-blue-800 dark:text-blue-200">
                            {{ __('mail-server/verification.verification_success.next_steps_title') }}
                        </h2>
                        <ol class="mt-2 text-sm text-blue-700 dark:text-blue-300 list-decimal list-inside space-y-1">
                            @if(isset($isInstall) && $isInstall)
                                @foreach(__('mail-server/verification.verification_success.next_steps_install') as $step)
                                    <li>{{ $step }}</li>
                                @endforeach
                            @else
                                @foreach(__('mail-server/verification.verification_success.next_steps') as $step)
                                    <li>{{ $step }}</li>
                                @endforeach
                            @endif
                        </ol>
                    </div>
                </div>
            </section>

            @if(!isset($isInstall) || !$isInstall)
            <!-- 重要な注意事項セクション（管理画面のみ） -->
            <aside aria-labelledby="important-notice-heading" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0" aria-hidden="true">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h2 id="important-notice-heading" class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                            {{ __('mail-server/verification.verification_success.important_notice_title') }}
                        </h2>
                        <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                            {{ __('mail-server/verification.verification_success.important_notice') }}
                        </p>
                    </div>
                </div>
            </aside>
            @endif

            <!-- アクションボタン -->
            <nav class="flex justify-center" aria-label="{{ __('mail-server/verification.verification_success.actions') }}">
                <button 
                    type="button"
                    id="close-verification-btn"
                    data-message="{{ __('mail-server/verification.verification_success.completed_message') }}"
                    class="bg-blue-600 dark:bg-blue-500 hover:bg-blue-700 dark:hover:bg-blue-600 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-200">
                    {{ __('mail-server/verification.verification_success.close_button') }}
                </button>
            </nav>
        </article>
    </main>
</body>
</html>
