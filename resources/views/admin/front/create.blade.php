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
                <div x-show="isHtmlEditor" x-cloak class="border-b border-gray-200 dark:border-gray-600">
                    <nav class="flex -mb-px space-x-4">
                        <button type="button" @click="activeTab = 'content'"
                                class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
                                :class="activeTab === 'content'
                                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'">
                            <i class="fas fa-code mr-1.5"></i>{{ __('admin/front.create.tab_content') }}
                        </button>
                        <button type="button" @click="activeTab = 'css'"
                                class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
                                :class="activeTab === 'css'
                                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'">
                            <i class="fab fa-css3-alt mr-1.5"></i>{{ __('admin/front.create.tab_css') }}
                        </button>
                        <button type="button" @click="activeTab = 'js'"
                                class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
                                :class="activeTab === 'js'
                                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'">
                            <i class="fab fa-js mr-1.5"></i>{{ __('admin/front.create.tab_js') }}
                        </button>
                    </nav>
                </div>

                {{-- Content タブ --}}
                <div x-show="activeTab === 'content' || !isHtmlEditor">
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

                {{-- CSS タブ（HTML エディタ時のみ） --}}
                <div x-show="activeTab === 'css' && isHtmlEditor" x-cloak>
                    <x-form-label :for="'custom_css'" :text="__('admin/front.create.tab_css')" />
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
                    <x-form-label :for="'custom_js'" :text="__('admin/front.create.tab_js')" />
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

        {{-- ===== 右サイドバートグルボタン（Desktop のみ） ===== --}}
        <button type="button"
                @click="toggleRightSidebar()"
                class="hidden lg:flex fixed top-14 right-0 z-40 items-center backdrop-blur-sm dark:bg-gray-900/75 bg-white/75 text-blue-400 dark:text-white px-1.5 py-4 rounded-l-lg shadow-md border border-r-0 border-gray-300 dark:border-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800"
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
        <div class="mt-6 lg:mt-0 space-y-6 lg:fixed lg:top-12 lg:right-0 lg:bottom-0 lg:w-80 lg:z-30 lg:overflow-y-auto lg:bg-white/75 dark:lg:bg-gray-900/75 lg:backdrop-blur-sm lg:border-l lg:border-gray-200 dark:lg:border-gray-600 lg:shadow-md lg:px-6 lg:py-6"
             :class="{
                 'lg:translate-x-80': rightSidebarCollapsed,
                 'lg:translate-x-0': !rightSidebarCollapsed
             }"
             :style="rightSidebarReady ? 'transition: transform 300ms ease-in-out' : ''">

            {{-- 保存方法 --}}
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    {{ __('admin/front.create.storage_section') }}
                </h3>

                <div>
                    <x-form-label :text="__('admin/front.create.storage_type_label')" />
                    <x-form-select
                        name="storage_type"
                        :options="collect($storageOptions)->mapWithKeys(fn ($opt, $key) => [$key => $opt['label']])->all()"
                        :value="old('storage_type', $defaultStorageType)"
                        xModel="storageType"
                    />
                    <x-form-error name="storage_type" />
                </div>

                <div class="mt-2 text-sm space-y-1" x-show="isFileStorage" x-cloak>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">{{ __('admin/front.create.storage_file_path') }}</span>
                        <span class="font-mono text-blue-600 dark:text-blue-400 break-all" x-text="filePath"></span>
                    </div>
                    <div x-show="isHtmlEditor && jsFilePath" x-cloak>
                        <span class="text-gray-500 dark:text-gray-400">JS:</span>
                        <span class="font-mono text-blue-600 dark:text-blue-400 break-all" x-text="jsFilePath"></span>
                    </div>
                    <div x-show="isHtmlEditor && cssFilePath" x-cloak>
                        <span class="text-gray-500 dark:text-gray-400">CSS:</span>
                        <span class="font-mono text-blue-600 dark:text-blue-400 break-all" x-text="cssFilePath"></span>
                    </div>
                </div>
            </div>

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
