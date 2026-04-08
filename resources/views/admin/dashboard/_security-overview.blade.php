{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

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

<section>
    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
        <i class="fas fa-shield-alt mr-2"></i>{{ __('admin/dashboard.security_overview') }}
    </h2>

    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($securityOverview as $item)
            <div class="flex items-start gap-3 p-4 rounded-lg border
                @if($item['status'] === 'warning')
                    border-yellow-300 dark:border-yellow-600 bg-yellow-50 dark:bg-yellow-900/20
                @elseif($item['status'] === 'recommendation')
                    border-amber-300 dark:border-amber-600 bg-amber-50 dark:bg-amber-900/20
                @else
                    border-green-300 dark:border-green-600 bg-green-50 dark:bg-green-900/20
                @endif
            ">
                <span class="mt-0.5 text-lg
                    @if($item['status'] === 'warning')
                        text-yellow-600 dark:text-yellow-400
                    @elseif($item['status'] === 'recommendation')
                        text-amber-600 dark:text-amber-400
                    @else
                        text-green-600 dark:text-green-400
                    @endif
                ">
                    <i class="{{ $item['icon'] }}"></i>
                </span>
                <div class="min-w-0">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item['label'] }}</h3>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">{{ $item['description'] }}</p>
                </div>
                <span class="ml-auto flex-shrink-0">
                    @if($item['status'] === 'warning')
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                            {{ __('admin/dashboard.status_warning') }}
                        </span>
                    @elseif($item['status'] === 'recommendation')
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                            {{ __('admin/dashboard.status_recommendation') }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            {{ __('admin/dashboard.status_ok') }}
                        </span>
                    @endif
                </span>
            </div>
        @endforeach

        {{-- メール状態 --}}
        <div class="flex items-start gap-3 p-4 rounded-lg border
            @if($mailStatus['status'] === 'warning')
                border-yellow-300 dark:border-yellow-600 bg-yellow-50 dark:bg-yellow-900/20
            @elseif($mailStatus['status'] === 'recommendation')
                border-amber-300 dark:border-amber-600 bg-amber-50 dark:bg-amber-900/20
            @else
                border-green-300 dark:border-green-600 bg-green-50 dark:bg-green-900/20
            @endif
        ">
            <span class="mt-0.5 text-lg
                @if($mailStatus['status'] === 'warning')
                    text-yellow-600 dark:text-yellow-400
                @elseif($mailStatus['status'] === 'recommendation')
                    text-amber-600 dark:text-amber-400
                @else
                    text-green-600 dark:text-green-400
                @endif
            ">
                <i class="{{ $mailStatus['icon'] }}"></i>
            </span>
            <div class="min-w-0">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $mailStatus['label'] }}</h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">{{ $mailStatus['description'] }}</p>
            </div>
            <span class="ml-auto flex-shrink-0">
                @if($mailStatus['status'] === 'warning')
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                        {{ __('admin/dashboard.status_warning') }}
                    </span>
                @elseif($mailStatus['status'] === 'recommendation')
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                        {{ __('admin/dashboard.status_recommendation') }}
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        {{ __('admin/dashboard.status_ok') }}
                    </span>
                @endif
            </span>
        </div>

        {{-- CAPTCHA状態 --}}
        <div class="flex items-start gap-3 p-4 rounded-lg border
            @if($captchaStatus['status'] === 'recommendation')
                border-amber-300 dark:border-amber-600 bg-amber-50 dark:bg-amber-900/20
            @else
                border-green-300 dark:border-green-600 bg-green-50 dark:bg-green-900/20
            @endif
        ">
            <span class="mt-0.5 text-lg
                @if($captchaStatus['status'] === 'recommendation')
                    text-amber-600 dark:text-amber-400
                @else
                    text-green-600 dark:text-green-400
                @endif
            ">
                <i class="{{ $captchaStatus['icon'] }}"></i>
            </span>
            <div class="min-w-0">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $captchaStatus['label'] }}</h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">{{ $captchaStatus['description'] }}</p>
            </div>
            <span class="ml-auto flex-shrink-0">
                @if($captchaStatus['status'] === 'recommendation')
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                        {{ __('admin/dashboard.status_recommendation') }}
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        {{ __('admin/dashboard.status_ok') }}
                    </span>
                @endif
            </span>
        </div>
    </div>
</section>
