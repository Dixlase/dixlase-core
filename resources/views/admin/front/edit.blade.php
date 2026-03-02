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
          x-data="frontPageEditor({
              defaultStorageType: '{{ old('storage_type', $storageType) }}',
              fileStorageBasePath: '{{ $fileStorageBasePath }}',
              editorType: '{{ $editorType }}',
              langCode: '{{ $langCode }}'
          })">
        @csrf
        @method('PUT')

        {{-- ===== メインコンテンツエリア ===== --}}
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
                {{-- エディタータイプ（固定表示） --}}
                <div>
                    <x-form-label :text="__('admin/front.edit.editor_type_label')" />
                    <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
                        <i class="{{ $editorTypeIcon }} text-lg" style="color: {{ $editorTypeColor }}"></i>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">{{ $editorTypeLabel }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $editorTypeDescription }}</div>
                        </div>
                    </div>
                    <x-form-help-text :text="__('admin/front.edit.editor_type_locked_help')" />
                </div>

                {{-- 言語（固定表示） --}}
                <div>
                    <x-form-label :text="__('admin/front.edit.lang_label')" />
                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $langName }}</p>
                </div>

                {{-- コンテンツ --}}
                <div>
                    <x-form-label :for="'content'" :text="__('admin/front.edit.content_label')" />
                    <x-form-textarea
                        id="content"
                        name="content"
                        :value="$body"
                        rows="20"
                        :placeholder="__('admin/front.edit.content_placeholder')"
                        class="font-mono text-sm"
                    />
                    <x-form-error name="content" />
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
                    ? '{{ __('admin/front.edit.sidebar_open') }}'
                    : '{{ __('admin/front.edit.sidebar_close') }}'">
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
                    {{ __('admin/front.edit.storage_section') }}
                </h3>

                <div>
                    <x-form-label :text="__('admin/front.edit.storage_type_label')" />
                    <x-form-select
                        name="storage_type"
                        :options="collect($storageOptions)->mapWithKeys(fn ($opt, $key) => [$key => $opt['label']])->all()"
                        :value="old('storage_type', $storageType)"
                        xModel="storageType"
                    />
                    <x-form-error name="storage_type" />
                </div>

                <div class="mt-2 text-sm" x-show="isFileStorage" x-cloak>
                    <span class="text-gray-500 dark:text-gray-400">{{ __('admin/front.edit.storage_file_path') }}</span>
                    <span class="font-mono text-blue-600 dark:text-blue-400 break-all" x-text="filePath"></span>
                </div>
            </div>

        </div>
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
