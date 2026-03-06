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
                    <x-content-editor.type-badge
                        :icon="$editorTypeIcon"
                        :color="$editorTypeColor"
                        :label="$editorTypeLabel"
                        :description="$editorTypeDescription"
                    />
                </div>

                {{-- 言語（固定表示） --}}
                <div>
                    <x-form-label :text="__('admin/front.edit.lang_label')" />
                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $langName }}</p>
                </div>

                {{-- タブナビゲーション（HTML エディタ時のみ） --}}
                @if ($isHtmlEditor)
                    <x-content-editor.tabs />
                @endif

                {{-- Content タブ --}}
                <div x-show="activeTab === 'content'">
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

                @if ($isHtmlEditor)
                    {{-- CSS タブ --}}
                    <div x-show="activeTab === 'css'" x-cloak>
                        <x-form-label :for="'custom_css'" :text="__('components/content-editor.tab_css')" />
                        <x-form-textarea
                            id="custom_css"
                            name="custom_css"
                            :value="$customCss ?? ''"
                            rows="20"
                            :placeholder="__('admin/front.edit.custom_css_placeholder')"
                            class="font-mono text-sm"
                        />
                        <x-form-error name="custom_css" />
                    </div>

                    {{-- JavaScript タブ --}}
                    <div x-show="activeTab === 'js'" x-cloak>
                        <x-form-label :for="'custom_js'" :text="__('components/content-editor.tab_js')" />
                        <x-form-textarea
                            id="custom_js"
                            name="custom_js"
                            :value="$customJs ?? ''"
                            rows="20"
                            :placeholder="__('admin/front.edit.custom_js_placeholder')"
                            class="font-mono text-sm"
                        />
                        <x-form-error name="custom_js" />
                    </div>
                @endif
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
