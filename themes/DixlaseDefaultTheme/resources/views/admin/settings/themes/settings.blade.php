{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
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

@extends('admin::partials.layout')

@section('content')
<div class="max-w-4xl mx-auto mt-12">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ __('Dixlase Default Theme Settings') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Customize your theme colors and appearance') }}
        </p>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-400 rounded-lg">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.settings.themes.settings.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- カラー設定 --}}
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                {{ __('Color Settings') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- プライマリカラー --}}
                <div>
                    <label for="primary_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Primary Color') }}
                    </label>
                    <div class="flex items-center space-x-3">
                        <input type="color" 
                               id="primary_color" 
                               name="primary_color" 
                               value="{{ old('primary_color', $settings->primary_color ?? '#3b82f6') }}"
                               class="h-10 w-20 rounded border border-gray-300 dark:border-gray-600">
                        <input type="text" 
                               value="{{ old('primary_color', $settings->primary_color ?? '#3b82f6') }}"
                               class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                               readonly>
                    </div>
                </div>

                {{-- セカンダリカラー --}}
                <div>
                    <label for="secondary_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Secondary Color') }}
                    </label>
                    <div class="flex items-center space-x-3">
                        <input type="color" 
                               id="secondary_color" 
                               name="secondary_color" 
                               value="{{ old('secondary_color', $settings->secondary_color ?? '#6b7280') }}"
                               class="h-10 w-20 rounded border border-gray-300 dark:border-gray-600">
                        <input type="text" 
                               value="{{ old('secondary_color', $settings->secondary_color ?? '#6b7280') }}"
                               class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                               readonly>
                    </div>
                </div>

                {{-- アクセントカラー --}}
                <div>
                    <label for="accent_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Accent Color') }}
                    </label>
                    <div class="flex items-center space-x-3">
                        <input type="color" 
                               id="accent_color" 
                               name="accent_color" 
                               value="{{ old('accent_color', $settings->accent_color ?? '#10b981') }}"
                               class="h-10 w-20 rounded border border-gray-300 dark:border-gray-600">
                        <input type="text" 
                               value="{{ old('accent_color', $settings->accent_color ?? '#10b981') }}"
                               class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                               readonly>
                    </div>
                </div>
            </div>
        </div>

        {{-- 一般設定 --}}
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                {{ __('General Settings') }}
            </h3>

            <div class="space-y-4">
                {{-- ロゴテキスト --}}
                <div>
                    <label for="logo_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Logo Text') }}
                    </label>
                    <input type="text" 
                           id="logo_text" 
                           name="logo_text" 
                           value="{{ old('logo_text', $settings->logo_text ?? config('app.name')) }}"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                           required>
                </div>

                {{-- 検索表示 --}}
                <div class="flex items-center">
                    <input type="checkbox" 
                           id="show_search" 
                           name="show_search" 
                           value="1"
                           {{ old('show_search', $settings->show_search ?? true) ? 'checked' : '' }}
                           class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                    <label for="show_search" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                        {{ __('Show search in header') }}
                    </label>
                </div>

                {{-- フッターテキスト --}}
                <div>
                    <label for="footer_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Footer Text') }}
                    </label>
                    <textarea id="footer_text" 
                              name="footer_text" 
                              rows="3"
                              class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('footer_text', $settings->footer_text ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- 保存ボタン --}}
        <div class="flex justify-end space-x-3">
            <a href="{{ route('admin.settings.themes.index') }}" 
               class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                {{ __('Cancel') }}
            </a>
            <button type="submit" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                {{ __('Save Settings') }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
// カラーピッカーの値をテキストフィールドに同期
document.querySelectorAll('input[type="color"]').forEach(colorInput => {
    const textInput = colorInput.nextElementSibling;
    
    colorInput.addEventListener('input', function() {
        textInput.value = this.value;
    });
});
</script>
@endpush
@endsection
