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
    <form id="front-page-create-form"
          action="{{ route('admin.front.store') }}"
          method="POST"
          x-data="frontPageCreate({
              defaultLang: '{{ old('lang', $defaultLang) }}',
              defaultEditorType: '{{ old('editor_type', 'markdown') }}',
              defaultStorageType: '{{ old('storage_type', $defaultStorageType) }}',
              fileStorageBasePath: '{{ $fileStorageBasePath }}',
              templates: {{ Js::from($templates) }},
              previewFrameUrl: '{{ $previewFrameUrl }}',
              previewUrl: '{{ $previewUrl }}'
          })">
        @csrf

        {{-- ===== プレビュートグル ===== --}}
        <div class="flex flex-wrap items-center gap-3 mb-4 text-xs">
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
                 :style="isHorizontal && previewVisible ? 'width: ' + (splitRatio * 100) + '%; max-height: calc(100vh - 160px)' : ''">

                <div class="space-y-4">
                    {{-- 言語選択 --}}
                    <div>
                        <x-form-label :for="'lang'" :text="__('admin/front.create.lang_label')" />
                        <x-form-select
                            id="lang"
                            name="lang"
                            :options="$languages"
                            :value="old('lang', $defaultLang)"
                            x-model="lang"
                        />
                        <x-form-error name="lang" />
                    </div>

                    {{-- エディタータイプ --}}
                    <div>
                        <x-form-label :text="__('admin/front.create.editor_type_label')" class="mb-3" />
                        <x-form-radio-card-group
                            name="editor_type"
                            :options="$editorCardOptions"
                            :value="old('editor_type', 'markdown')"
                            :columns="3"
                            xModel="editorType"
                        />
                        <x-form-error name="editor_type" />
                    </div>

                    {{-- タブナビゲーション（HTML エディタ時のみ） --}}
                    <div x-show="isHtmlEditor" x-cloak>
                        <x-content-editor.tabs />
                    </div>

                    {{-- Content タブ --}}
                    <div x-show="(activeTab === 'content' || !isHtmlEditor) && editorType !== 'gui'">
                        <x-form-textarea
                            id="content"
                            name="content"
                            :value="old('content', '')"
                            rows="6"
                            :placeholder="__('admin/front.create.content_placeholder')"
                            class="font-mono text-sm !bg-gray-950 !text-gray-200 !border-gray-600 focus:!border-blue-500"
                        />
                        <x-form-error name="content" />
                    </div>

                    {{-- GUI エディター（x-if で DOM から除外し name="content" の重複を防ぐ） --}}
                    <template x-if="editorType === 'gui'">
                        <div>
                            @if($guiEditorInfo ?? null)
                                @include($guiEditorInfo->viewName, [
                                    'contentFieldName' => 'content',
                                    'editorInfo' => $guiEditorInfo,
                                    'initialContent' => '',
                                ])
                            @else
                                <div class="p-8 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-center">
                                    <i class="fas fa-paint-brush text-4xl text-gray-400 mb-4"></i>
                                    <p class="text-gray-600 dark:text-gray-400">
                                        {{ __('common.content_editor.gui_coming_soon') }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </template>

                    {{-- CSS タブ（HTML エディタ時のみ） --}}
                    <div x-show="activeTab === 'css' && isHtmlEditor" x-cloak>
                        <x-form-textarea
                            id="custom_css"
                            name="custom_css"
                            :value="old('custom_css', '')"
                            rows="6"
                            :placeholder="__('admin/front.create.custom_css_placeholder')"
                            class="font-mono text-sm !bg-gray-950 !text-gray-200 !border-gray-600 focus:!border-blue-500 !overflow-hidden !resize-none"
                            data-auto-resize
                        />
                        <x-form-error name="custom_css" />
                    </div>

                    {{-- JavaScript タブ（HTML エディタ時のみ） --}}
                    <div x-show="activeTab === 'js' && isHtmlEditor" x-cloak>
                        <x-form-textarea
                            id="custom_js"
                            name="custom_js"
                            :value="old('custom_js', '')"
                            rows="6"
                            :placeholder="__('admin/front.create.custom_js_placeholder')"
                            class="font-mono text-sm !bg-gray-950 !text-gray-200 !border-gray-600 focus:!border-blue-500 !overflow-hidden !resize-none"
                            data-auto-resize
                        />
                        <x-form-error name="custom_js" />
                    </div>
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
                    ? '{{ __('admin/front.create.sidebar_open') }}'
                    : '{{ __('admin/front.create.sidebar_close') }}'">
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
                :storageType="old('storage_type', $defaultStorageType)"
                :showJsCss="true"
            />

        </div>
    </form>
@endsection

@section('save')
    <x-admin.save-button
        id_confirmation="confirmFrontPageCreateModal"
        :label="__('common.save')"
        :title="__('admin/front.create.confirm_title')"
        :message="__('admin/front.create.confirm_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="front-page-create-form"
    />
@endsection

@if($guiEditorAssetHtml ?? '')
    @push('head')
        {!! $guiEditorAssetHtml !!}
    @endpush
@endif
