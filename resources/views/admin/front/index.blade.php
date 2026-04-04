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

@extends('layouts.admin')

@section('content')
    @if ($frontPage)
        {{-- コンテンツ存在時: ステータスカード --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                {{ __('admin/front.index.content_exists_title') }}
            </h2>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/front.index.language') }}</dt>
                    <dd class="mt-1 font-medium text-gray-900 dark:text-white">{{ $langName }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/front.index.editor_type') }}</dt>
                    <dd class="mt-1 font-medium text-gray-900 dark:text-white">{{ $editorTypeLabel }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/front.index.storage_type') }}</dt>
                    <dd class="mt-1 font-medium text-gray-900 dark:text-white">{{ $storageTypeLabel }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/front.index.last_updated') }}</dt>
                    <dd class="mt-1 font-medium text-gray-900 dark:text-white">{{ $frontPage->updated_at->format('Y-m-d H:i') }}</dd>
                </div>
            </dl>

            <div class="mt-6 flex flex-wrap gap-3">
                <x-form-button
                    type="link"
                    variant="primary"
                    icon="fas fa-edit"
                    :href="route('admin.front.edit')"
                >
                    {{ __('admin/front.index.edit_button') }}
                </x-form-button>

                <x-form-button
                    type="button"
                    variant="danger"
                    icon="fas fa-undo"
                    @click="openModal('resetFrontPageModal')"
                >
                    {{ __('admin/front.index.reset_button') }}
                </x-form-button>
            </div>
        </div>
    @else
        {{-- コンテンツ未存在時: 空ステート --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
            <div class="mx-auto w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                <i class="fas fa-file-alt text-2xl text-gray-400 dark:text-gray-500"></i>
            </div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                {{ __('admin/front.index.no_content_title') }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                {{ __('admin/front.index.no_content_description') }}
            </p>
            <x-form-button
                type="link"
                variant="primary"
                icon="fas fa-plus"
                :href="route('admin.front.create')"
            >
                {{ __('admin/front.index.create_button') }}
            </x-form-button>
        </div>
    @endif

    {{-- Theme Preview (iframe) --}}
    @if ($frontPage && !empty($previewContent))
        <div class="mt-6 min-w-0 overflow-hidden" x-data="{
            previewFrameUrl: '{{ $previewFrameUrl }}',
            previewDevice: 'desktop',
            freeWidth: 1440,
            freeHeight: 900,
            _previewContainerWidth: 0,
            get currentPreviewWidth() {
                const presets = { mobile: 375, tablet: 768, desktop: 1440 };
                return this.previewDevice === 'free' ? this.freeWidth : (presets[this.previewDevice] ?? 1440);
            },
            get currentPreviewHeight() {
                const presets = { mobile: 667, tablet: 1024, desktop: 900 };
                return this.previewDevice === 'free' ? this.freeHeight : (presets[this.previewDevice] ?? 900);
            },
            get previewScale() {
                if (this._previewContainerWidth <= 0 || this.currentPreviewWidth <= this._previewContainerWidth) return 1;
                return this._previewContainerWidth / this.currentPreviewWidth;
            },
            get scaledPreviewWidth() { return Math.round(this.currentPreviewWidth * this.previewScale); },
            get scaledPreviewHeight() { return Math.round(this.currentPreviewHeight * this.previewScale); },
            setPreviewDevice(d) { this.previewDevice = d; },
            init() {
                this.$nextTick(() => {
                    const c = this.$refs.previewContainer;
                    if (!c) return;
                    new ResizeObserver(entries => {
                        for (const e of entries) this._previewContainerWidth = e.contentRect.width;
                    }).observe(c);
                });
            }
        }">
            {{-- プレビューヘッダー --}}
            <div class="flex flex-wrap items-center justify-between px-4 py-2 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-t-lg gap-2">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        <i class="fas fa-eye mr-1"></i>{{ __('components/content-editor.preview_title') }}
                    </span>
                    <span x-show="previewScale < 1" x-cloak
                          class="text-[10px] text-gray-400 dark:text-gray-500"
                          x-text="Math.round(previewScale * 100) + '%'"></span>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex items-center bg-gray-100 dark:bg-gray-800 rounded-lg p-0.5 gap-0.5">
                        <button type="button" @click="setPreviewDevice('mobile')"
                            :class="previewDevice === 'mobile' ? 'bg-white dark:bg-gray-600 shadow-sm text-blue-600 dark:text-blue-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                            class="px-2.5 py-1.5 rounded-md transition-all text-xs"
                            title="{{ __('components/content-editor.device_mobile') }} (375×667)">
                            <i class="fas fa-mobile-alt"></i>
                        </button>
                        <button type="button" @click="setPreviewDevice('tablet')"
                            :class="previewDevice === 'tablet' ? 'bg-white dark:bg-gray-600 shadow-sm text-blue-600 dark:text-blue-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                            class="px-2.5 py-1.5 rounded-md transition-all text-xs"
                            title="{{ __('components/content-editor.device_tablet') }} (768×1024)">
                            <i class="fas fa-tablet-alt"></i>
                        </button>
                        <button type="button" @click="setPreviewDevice('desktop')"
                            :class="previewDevice === 'desktop' ? 'bg-white dark:bg-gray-600 shadow-sm text-blue-600 dark:text-blue-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                            class="px-2.5 py-1.5 rounded-md transition-all text-xs"
                            title="{{ __('components/content-editor.device_desktop') }} (1440×900)">
                            <i class="fas fa-desktop"></i>
                        </button>
                        <button type="button" @click="setPreviewDevice('free')"
                            :class="previewDevice === 'free' ? 'bg-white dark:bg-gray-600 shadow-sm text-blue-600 dark:text-blue-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                            class="px-2.5 py-1.5 rounded-md transition-all text-xs"
                            title="{{ __('components/content-editor.device_free') }}">
                            <i class="fas fa-expand-arrows-alt"></i>
                        </button>
                    </div>

                    <div class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                        <template x-if="previewDevice === 'free'">
                            <div class="flex items-center gap-1">
                                <input type="number" x-model.number="freeWidth" min="200" max="3840"
                                       class="w-16 px-1.5 py-0.5 text-xs rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                <span>×</span>
                                <input type="number" x-model.number="freeHeight" min="200" max="3840"
                                       class="w-16 px-1.5 py-0.5 text-xs rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                <span>px</span>
                            </div>
                        </template>
                        <template x-if="previewDevice !== 'free'">
                            <span x-text="currentPreviewWidth + '×' + currentPreviewHeight + 'px'"></span>
                        </template>
                    </div>
                </div>
            </div>

            {{-- プレビューコンテナ --}}
            <div x-ref="previewContainer"
                 class="relative overflow-hidden bg-gray-100 dark:bg-gray-900 border border-t-0 border-gray-200 dark:border-gray-700 rounded-b-lg"
                 :style="'height: ' + scaledPreviewHeight + 'px'">
                <div :style="'width: ' + scaledPreviewWidth + 'px; height: ' + scaledPreviewHeight + 'px; margin: 0 auto;'">
                    <iframe :src="previewFrameUrl"
                            class="bg-white"
                            :style="'width: ' + currentPreviewWidth + 'px; height: ' + currentPreviewHeight + 'px; transform: scale(' + previewScale + '); transform-origin: top left;'"
                            sandbox="allow-scripts allow-same-origin allow-forms"
                            title="{{ __('components/content-editor.preview_title') }}">
                    </iframe>
                </div>
            </div>
        </div>
    @endif

    @if ($frontPage)
        <x-ui-modal
            id="resetFrontPageModal"
            :title="__('admin/front.index.reset_confirm_title')"
            :message="__('admin/front.index.reset_confirm')"
            :confirm-label="__('admin/front.index.reset_button')"
            :cancel-label="__('common.cancel')"
            icon-type="danger"
            confirm-color="red"
            form="front-page-reset-form"
        />

        <form id="front-page-reset-form"
              action="{{ route('admin.front.destroy') }}"
              method="POST"
              style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    @endif
@endsection

@push('styles')
<style @cspNonce>
#admin-main-content { min-width: 0; }
</style>
@endpush
