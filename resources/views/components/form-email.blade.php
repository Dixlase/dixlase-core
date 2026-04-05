{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-form-email />

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

@props([
    'name' => 'email',
    'id' => 'email',
    'value' => '',
    'required' => true,
    'showConfirmation' => false,
    
    // 変更時のみ確認欄を表示（true = 入力時に確認欄を表示, false = 常に表示）
    'showConfirmationOnChange' => false,
])

<div x-data="emailInput({
        originalEmail: '{{ $value }}',
        showConfirmation: {{ $showConfirmation ? 'true' : 'false' }},
        showConfirmationOnChange: {{ $showConfirmationOnChange ? 'true' : 'false' }}
    })"
    data-match-success="{{ __('components/form-email.match_success') }}"
    data-match-error="{{ __('components/form-email.match_error') }}"
    class="space-y-4">
    
    <!-- メインのメールアドレス入力 -->
    <fieldset>
        <legend>{{ __('common.email') }}</legend>
        <input type="email"
            id="{{ $id }}"
            name="{{ $name }}"
            x-model="email"
            @if($required) required @endif
            autocomplete="email"
            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-800 dark:border-gray-500 dark:text-white"
        />
    </fieldset>

    @if($showConfirmation)
        <!-- メールアドレス確認入力 -->
        <fieldset x-show="shouldShowConfirmation" x-transition>
            <legend>{{ __('components/form-email.confirmation_label') }}</legend>
            <div class="relative">
                <input type="email"
                    id="{{ $id }}_confirmation"
                    name="{{ $name }}_confirmation"
                    x-model="emailConfirmation"
                    :required="shouldShowConfirmation && {{ $required ? 'true' : 'false' }}"
                    autocomplete="off"
                    @paste.prevent
                    @copy.prevent
                    @cut.prevent
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-800 dark:border-gray-500 dark:text-white"
                    aria-describedby="{{ $id }}-confirmation-help"
                />
                <!-- メールアドレス一致判定アイコン -->
                <div x-show="showMatchIndicator"
                    x-transition
                    class="absolute top-0 right-2 h-full flex items-center"
                    role="status"
                    aria-live="polite">
                    <i :class="matchIconClass" :aria-label="matchIconLabel"></i>
                </div>
            </div>
            <p id="{{ $id }}-confirmation-help" class="help-text">{{ __('components/form-email.confirmation_help') }}</p>
        </fieldset>
    @endif
</div>
