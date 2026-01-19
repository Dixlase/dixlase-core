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

@props([
    'id' => 'mediaSelectorModal',
    'inputId' => 'media_id',
    'previewId' => 'media_preview',
    'multiple' => false,
    'allowedTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml']
])

<!-- メディア選択モーダル -->
<div id="{{ $id }}" 
     class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900 bg-opacity-50 dark:bg-opacity-70"
     data-api-url="{{ route('admin.media.api') }}"
     data-error-message="{{ __('common.error_loading_media') }}"
     data-no-media-message="{{ __('common.no_media_found') }}"
     aria-labelledby="{{ $id }}-title"
     role="dialog"
     aria-modal="true">
    <div class="flex w-full items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- モーダルコンテンツ -->
        <div class="inline-block max-w-6xl mx-8 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl dark:bg-gray-800 mb-8 mt-36 mx-10sm:align-middle">
            <!-- ヘッダー -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 id="{{ $id }}-title" class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ __('common.select_media') }}
                </h3>
                <button type="button" 
                        onclick="closeMediaSelector('{{ $id }}')"
                        class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- 検索とフィルター -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="flex-1">
                        <input type="text" 
                               id="{{ $id }}-search"
                               placeholder="{{ __('common.search') }}..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div class="flex gap-2">
                        <select id="{{ $id }}-type-filter" 
                                class="px-4 py-2 border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="">{{ __('common.all_types') }}</option>
                            <option value="image">{{ __('common.images') }}</option>
                            <option value="video">{{ __('common.videos') }}</option>
                            <option value="document">{{ __('common.documents') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- メディアグリッド -->
            <div class="px-6 py-4" style="max-height: 60vh; overflow-y: auto;">
                <div id="{{ $id }}-grid" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    <!-- JavaScriptで動的に読み込み -->
                    <div class="col-span-full flex items-center justify-center py-12">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin text-4xl text-gray-400 mb-4"></i>
                            <p class="text-gray-500 dark:text-gray-400">{{ __('common.loading') }}...</p>
                        </div>
                    </div>
                </div>

                <!-- ページネーション -->
                <div id="{{ $id }}-pagination" class="mt-6 hidden">
                    <!-- JavaScriptで動的に生成 -->
                </div>
            </div>

            <!-- フッター -->
            <div class="flex items-center justify-between px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <span id="{{ $id }}-selected-count">0</span> {{ __('common.items_selected') }}
                </div>
                <div class="flex gap-3">
                    <button type="button" 
                            onclick="closeMediaSelector('{{ $id }}')"
                            class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="button" 
                            onclick="confirmMediaSelection('{{ $id }}', '{{ $inputId }}', '{{ $previewId }}', {{ $multiple ? 'true' : 'false' }})"
                            class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        {{ __('common.select') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@once

@push('styles')
<style>
.media-selector-item.selected .relative {
    @apply ring-2 ring-blue-600;
}
</style>
@endpush
@endonce
