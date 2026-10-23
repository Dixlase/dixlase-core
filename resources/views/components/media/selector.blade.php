{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes as <x-media.selector />

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE-COMMERCIAL, or contact info@dixlase.org).

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

@props([
    'id' => 'mediaSelectorModal',
    'inputId' => 'media_id',
    'previewId' => 'media_preview',
    'multiple' => false,
    'allowedTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
    // Ignored: the selector uploads in place now; kept for existing callers
    'confirmUploadNavigation' => false,
])

@php
    // The same permission the media.store route checks (check.menu.edit:media)
    $canUpload = \App\Helpers\AdminHelper::canEditMenu('media')
        && (! \App\Helpers\AdminModeHelper::isSimpleMode() || \App\Helpers\AdminModeHelper::isMenuEditable('media'));
    $uploadExtensions = $canUpload
        ? (array) app(\App\Contracts\Repositories\MediaSettingRepositoryInterface::class)->get('allowed_file_types', [])
        : [];
@endphp

<!-- Media selection modal -->
<div id="{{ $id }}" 
     class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/50 dark:bg-gray-900/70"
     data-api-url="{{ route('admin.media.api') }}"
     data-error-message="{{ __('common.error_loading_media') }}"
     data-no-media-message="{{ __('common.no_media_found') }}"
     data-previous-label="{{ __('common.previous') }}"
     data-next-label="{{ __('common.next') }}"
     data-page-label="{{ __('common.page_of') }}"
     @if($canUpload)
     x-data="mediaSelectorUpload"
     data-upload-url="{{ route('admin.media.store') }}"
     data-upload-failed="{{ __('components/media-selector.upload_failed') }}"
     data-upload-network-error="{{ __('components/media-selector.upload_network_error') }}"
     @dragover="dragOver($event)"
     @dragleave="dragLeave($event)"
     @drop="drop($event)"
     @endif
     aria-labelledby="{{ $id }}-title"
     role="dialog"
     aria-modal="true">
    <div class="flex w-full items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Modal content -->
        <div class="inline-block max-w-6xl mx-8 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl dark:bg-gray-800 mb-8 mt-36 mx-10sm:align-middle">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 id="{{ $id }}-title" class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ __('common.select_media') }}
                </h3>
                <div class="flex items-center gap-3">
                    @if($canUpload)
                        <button type="button"
                            @click="togglePanel()"
                            :aria-expanded="panelOpen"
                            aria-controls="{{ $id }}-upload"
                            class="px-3 py-1.5 text-sm text-white bg-green-600 rounded-lg hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 inline-flex items-center">
                            <i class="fas fa-upload mr-1.5"></i>{{ __('common.upload') }}
                        </button>
                    @endif
                    <button type="button"
                            @click="closeMediaSelector('{{ $id }}')"
                            class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            @if($canUpload)
                {{-- In-modal upload. Opens from the Upload button, or by itself when files are dragged over the modal. Each file shows its progress or the server's reason for refusing it. --}}
                <div id="{{ $id }}-upload" x-show="panelOpen" x-cloak class="px-6 pt-4">
                    <div class="flex flex-col items-center justify-center gap-2 px-4 py-6 text-center border-2 border-dashed rounded-lg transition-colors"
                         :class="dropZoneClass()">
                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400" aria-hidden="true"></i>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ __('components/media-selector.upload_drop_here') }}
                            <button type="button" @click="chooseFiles()"
                                    class="font-medium text-blue-600 hover:underline dark:text-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded">
                                {{ __('components/media-selector.upload_choose_files') }}
                            </button>
                        </p>
                        @if(! empty($uploadExtensions))
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('components/media-selector.upload_allowed_types', ['types' => collect($uploadExtensions)->implode(', ')]) }}
                            </p>
                        @endif
                        <input type="file" x-ref="uploadInput" multiple class="hidden"
                               accept="{{ collect($uploadExtensions)->map(fn ($ext) => '.'.$ext)->implode(',') }}"
                               @change="picked($event)">
                    </div>

                    <ul x-show="hasItems()" class="mt-3 space-y-2 text-sm" aria-live="polite">
                        <template x-for="item in items" :key="item.key">
                            <li class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span class="min-w-0 flex-1 truncate text-gray-700 dark:text-gray-200" x-text="item.name"></span>
                                <span x-show="isUploading(item)" class="w-24 h-1.5 shrink-0 overflow-hidden rounded bg-gray-200 dark:bg-gray-700">
                                    <span class="block h-full bg-blue-600 transition-all" :style="progressWidth(item)"></span>
                                </span>
                                <i x-show="isSuccess(item)" class="fas fa-check shrink-0 text-green-600 dark:text-green-400" aria-hidden="true"></i>
                                <span x-show="isError(item)" class="basis-full text-xs text-red-600 dark:text-red-400" x-text="item.error"></span>
                            </li>
                        </template>
                    </ul>
                    <div x-show="hasFinished()" class="mt-2 flex justify-end gap-4 text-xs">
                        <button type="button" @click="clearFinished()" class="text-gray-500 hover:underline dark:text-gray-400">
                            {{ __('components/media-selector.upload_clear_list') }}
                        </button>
                        <button type="button" @click="closePanel()" class="text-gray-500 hover:underline dark:text-gray-400">
                            {{ __('components/media-selector.upload_close_panel') }}
                        </button>
                    </div>
                </div>
            @endif

            <!-- Search and filter -->
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

            <!-- Media grid -->
            <div id="{{ $id }}-scroll" class="px-6 py-4" style="max-height: 60vh; overflow-y: auto;">
                <div id="{{ $id }}-grid" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    <!-- Loaded dynamically by JavaScript -->
                    <div class="col-span-full flex items-center justify-center py-12">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin text-4xl text-gray-400 mb-4"></i>
                            <p class="text-gray-500 dark:text-gray-400">{{ __('common.loading') }}...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex flex-wrap items-center justify-between gap-3 sm:grid sm:grid-cols-[1fr_auto_1fr] px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-600 dark:text-gray-400 sm:col-start-1 sm:row-start-1">
                    <span id="{{ $id }}-selected-count">0</span> {{ __('common.items_selected') }}
                </div>

                {{-- Page navigation lives in the footer, not under the grid, so it is visible without scrolling. From sm up the footer is a grid with two equal outer columns, so the navigation sits at the centre of the modal rather than midway between the count and the buttons; each item names its column, so the buttons stay right when the navigation is hidden. On a narrow screen the navigation takes a row of its own above the count and the buttons. --}}
                <div id="{{ $id }}-pagination" class="hidden order-first w-full sm:order-none sm:w-auto sm:col-start-2 sm:row-start-1">
                    <!-- Generated dynamically by JavaScript -->
                </div>
                <div class="flex gap-3 sm:col-start-3 sm:row-start-1 sm:justify-self-end">
                    <button type="button" 
                            @click="closeMediaSelector('{{ $id }}')"
                            class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="button" 
                            @click="confirmMediaSelection('{{ $id }}', '{{ $inputId }}', '{{ $previewId }}', {{ $multiple ? 'true' : 'false' }})"
                            class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        {{ __('common.select') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
