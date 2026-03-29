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
              templates: {{ Js::from($templates) }}
          })">
        @csrf

        {{-- ===== メインコンテンツエリア ===== --}}
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
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
                    <x-form-label :for="'content'" :text="__('admin/front.create.content_label')" />
                    <x-form-textarea
                        id="content"
                        name="content"
                        :value="old('content', '')"
                        rows="20"
                        :placeholder="__('admin/front.create.content_placeholder')"
                        class="font-mono text-sm"
                    />
                    <x-form-error name="content" />
                </div>

                {{-- GUI エディター --}}
                <div x-show="editorType === 'gui'" x-cloak>
                    @if($guiEditorInfo ?? null)
                        @include($guiEditorInfo->viewName, [
                            'contentFieldName' => 'content',
                            'editorInfo' => $guiEditorInfo,
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

                {{-- CSS タブ（HTML エディタ時のみ） --}}
                <div x-show="activeTab === 'css' && isHtmlEditor" x-cloak>
                    <x-form-label :for="'custom_css'" :text="__('components/content-editor.tab_css')" />
                    <x-form-textarea
                        id="custom_css"
                        name="custom_css"
                        :value="old('custom_css', '')"
                        rows="20"
                        :placeholder="__('admin/front.create.custom_css_placeholder')"
                        class="font-mono text-sm"
                    />
                    <x-form-error name="custom_css" />
                </div>

                {{-- JavaScript タブ（HTML エディタ時のみ） --}}
                <div x-show="activeTab === 'js' && isHtmlEditor" x-cloak>
                    <x-form-label :for="'custom_js'" :text="__('components/content-editor.tab_js')" />
                    <x-form-textarea
                        id="custom_js"
                        name="custom_js"
                        :value="old('custom_js', '')"
                        rows="20"
                        :placeholder="__('admin/front.create.custom_js_placeholder')"
                        class="font-mono text-sm"
                    />
                    <x-form-error name="custom_js" />
                </div>
            </div>
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
