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
    <form id="front-page-edit-form"
          action="{{ route('admin.front.edit.update') }}"
          method="POST"
          class="min-w-0 overflow-hidden"
          x-data="splitPaneEditor({
              defaultStorageType: '{{ old('storage_type', $storageType) }}',
              fileStorageBasePath: '{{ $fileStorageBasePath }}',
              editorType: '{{ $editorType }}',
              editorTypeValue: '{{ $editorTypeValue }}',
              previewUrl: '{{ $previewUrl }}',
              previewFrameUrl: '{{ $previewFrameUrl }}'
          })">
        @csrf
        @method('PUT')

        {{-- ===== エディタータイプ + 言語バッジ + プレビュートグル ===== --}}
        <div class="flex flex-wrap items-center gap-3 mb-4 text-xs">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                <i class="{{ $editorTypeIcon }}" style="color: {{ $editorTypeColor }}"></i>
                {{ $editorTypeLabel }}
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                <i class="fas fa-globe"></i>
                {{ $langName }}
            </span>
            <button type="button" @click="togglePreview()"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md border transition-colors"
                :class="previewVisible
                    ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border-blue-200 dark:border-blue-700'
                    : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-600 hover:text-gray-700 dark:hover:text-gray-300'"
                :title="previewVisible ? '{{ __('admin/front.edit.preview_hide') }}' : '{{ __('admin/front.edit.preview_show') }}'">
                <i class="fas" :class="previewVisible ? 'fa-eye' : 'fa-eye-slash'"></i>
                <span x-text="previewVisible ? '{{ __('admin/front.edit.preview_hide') }}' : '{{ __('admin/front.edit.preview_show') }}'"></span>
            </button>
        </div>

        {{-- ===== スプリットペインコンテナ ===== --}}
        <div x-ref="splitContainer"
             class="flex gap-4 overflow-hidden"
             :class="[
                 isHorizontal ? 'flex-row' : 'flex-col',
                 (isDragging || isResizingPreview) ? 'select-none' : ''
             ]">

            {{-- ===== エディタペイン ===== --}}
            <div x-ref="editorPane"
                 class="w-full min-w-0"
                 :class="isHorizontal && previewVisible ? 'overflow-y-auto' : ''"
                 :style="isHorizontal && previewVisible ? { width: (splitRatio * 100) + '%', maxHeight: 'calc(100vh - 160px)' } : {}">

                <div class="space-y-4">
                    {{-- タブナビゲーション（HTML エディタ時のみ） --}}
                    @if ($isHtmlEditor)
                        <x-content-editor.tabs />
                    @endif

                    {{-- Content タブ (non-GUI editors) --}}
                    @if(!($isGuiEditor ?? false))
                    <div x-show="activeTab === 'content'">
                        <x-form-textarea
                            id="content"
                            name="content"
                            :value="$body"
                            rows="6"
                            :placeholder="__('admin/front.edit.content_placeholder')"
                            class="font-mono text-sm !bg-gray-950 !text-gray-200 !border-gray-600 focus:!border-blue-500"
                        />
                        <x-form-error name="content" />
                    </div>
                    @endif

                    {{-- GUI エディター --}}
                    @if($isGuiEditor ?? false)
                    <div>
                        @if($guiEditorInfo ?? null)
                            @include($guiEditorInfo->viewName, [
                                'contentFieldName' => 'content',
                                'editorInfo' => $guiEditorInfo,
                                'initialContent' => $body,
                            ])
                        @else
                            <div class="p-4 bg-yellow-900/30 border border-yellow-700 rounded-lg">
                                <div class="flex items-start">
                                    <i class="fas fa-exclamation-triangle text-yellow-500 mt-1 mr-3"></i>
                                    <div>
                                        <p class="text-yellow-200 text-sm">
                                            {{ __('common.content_editor.gui_unavailable') }}
                                        </p>
                                        <x-form-textarea
                                            id="content"
                                            name="content"
                                            :value="$body"
                                            rows="8"
                                            class="mt-3 font-mono text-sm"
                                            readonly
                                        />
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    @endif

                    @if ($isHtmlEditor)
                        {{-- CSS タブ --}}
                        <div x-show="activeTab === 'css'" x-cloak>
                            <x-form-textarea
                                id="custom_css"
                                name="custom_css"
                                :value="$customCss ?? ''"
                                rows="6"
                                :placeholder="__('admin/front.edit.custom_css_placeholder')"
                                class="font-mono text-sm !bg-gray-950 !text-gray-200 !border-gray-600 focus:!border-blue-500 !overflow-hidden !resize-none"
                                data-auto-resize
                            />
                            <x-form-error name="custom_css" />
                        </div>

                        {{-- JavaScript タブ --}}
                        <div x-show="activeTab === 'js'" x-cloak>
                            <x-form-textarea
                                id="custom_js"
                                name="custom_js"
                                :value="$customJs ?? ''"
                                rows="6"
                                :placeholder="__('admin/front.edit.custom_js_placeholder')"
                                class="font-mono text-sm !bg-gray-950 !text-gray-200 !border-gray-600 focus:!border-blue-500 !overflow-hidden !resize-none"
                                data-auto-resize
                            />
                            <x-form-error name="custom_js" />
                        </div>
                    @endif
                </div>
            </div>

            @include('admin.front.partials.preview-section')
        </div>

        {{-- ===== 縦並び時のフローティングボタン ===== --}}
        <div x-show="!isHorizontal && previewVisible" x-cloak class="fixed bottom-20 right-4 z-40 flex flex-col gap-2">
            <button type="button" @click="scrollToEditor()"
                    class="p-3 rounded-full bg-blue-600 text-white shadow-lg hover:bg-blue-700 transition-colors"
                    title="{{ __('admin/front.edit.scroll_to_editor') }}">
                <i class="fas fa-edit text-sm"></i>
            </button>
            <button type="button" @click="scrollToPreview()"
                    class="p-3 rounded-full bg-blue-600 text-white shadow-lg hover:bg-blue-700 transition-colors"
                    title="{{ __('admin/front.edit.scroll_to_preview') }}">
                <i class="fas fa-eye text-sm"></i>
            </button>
        </div>

        {{-- ===== 右サイドバートグルボタン ===== --}}
        <button type="button"
                @click="toggleRightSidebar()"
                class="hidden sm:flex fixed top-14 right-0 z-50 backdrop-blur-sm dark:bg-gray-900/75 bg-white/75 text-blue-400 dark:text-white px-1.5 py-4 rounded-l-lg shadow-md border border-r-0 border-gray-300 dark:border-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800"
                :class="{
                    'translate-x-0': rightSidebarCollapsed,
                    '-translate-x-80': !rightSidebarCollapsed
                }"
                :style="rightSidebarReady ? 'transition: transform 200ms ease-in-out' : ''"
                :aria-label="rightSidebarCollapsed
                    ? '{{ __('admin/front.edit.sidebar_open') }}'
                    : '{{ __('admin/front.edit.sidebar_close') }}'">
            <i class="fas text-sm" :class="rightSidebarCollapsed ? 'fa-chevron-left' : 'fa-chevron-right'"></i>
        </button>

        {{-- ===== 右サイドバー ===== --}}
        <div class="space-y-6 fixed top-12 right-0 bottom-0 w-80 z-50 overflow-y-auto bg-white/75 dark:bg-gray-900/75 backdrop-blur-sm border-l border-gray-200 dark:border-gray-600 shadow-md px-6 py-6"
             :class="{
                 'translate-x-80': rightSidebarCollapsed,
                 'translate-x-0': !rightSidebarCollapsed
             }"
             :style="rightSidebarReady ? 'transition: transform 300ms ease-in-out' : ''">

            {{-- 保存方法 --}}
            <x-content-editor.storage-info
                :storageOptions="$storageOptions"
                :storageType="old('storage_type', $storageType)"
                :showJsCss="true"
            />

            {{-- リセット --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-red-300 dark:border-red-700/50 p-6">
                <h3 class="text-sm font-semibold text-red-600 dark:text-red-400 mb-2">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    {{ __('admin/front.edit.reset_section_title') }}
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    {{ __('admin/front.edit.reset_description') }}
                </p>
                <x-form-button type="button" variant="danger" size="sm" icon="fas fa-undo"
                    @click="openModal('resetFrontPageEditModal')">
                    {{ __('admin/front.edit.reset_button') }}
                </x-form-button>
            </div>

        </div>
    </form>

    {{-- リセット確認モーダル --}}
    <x-ui-modal id="resetFrontPageEditModal"
        :title="__('admin/front.edit.reset_confirm_title')"
        :message="__('admin/front.edit.reset_confirm')"
        :confirm-label="__('admin/front.edit.reset_button')"
        :cancel-label="__('common.cancel')"
        icon-type="danger"
        confirm-color="red"
        form="front-page-reset-form" />

    <form id="front-page-reset-form"
          action="{{ route('admin.front.destroy') }}"
          method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
@endsection

@section('save')
    <x-admin.save-button
        id_confirmation="confirmFrontPageEditModal"
        :label="__('common.save')"
        :title="__('admin/front.edit.confirm_title')"
        :message="__('admin/front.edit.confirm_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="front-page-edit-form"
    />
@endsection

@push('styles')
<style @cspNonce>
/* メインコンテンツがflexbox min-width:autoで縮小しない問題を修正 */
#admin-main-content { min-width: 0; }
</style>
@endpush

@if($guiEditorAssetHtml ?? '')
    @push('head')
        {!! $guiEditorAssetHtml !!}
    @endpush
@endif
