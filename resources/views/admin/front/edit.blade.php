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
              langCode: '{{ $langCode }}',
              previewUrl: '{{ $previewUrl }}',
              editorTypeValue: '{{ $editorTypeValue }}'
          })">
        @csrf
        @method('PUT')

        {{-- ===== エディタータイプ + 言語（プレビュー外に配置） ===== --}}
        <div class="flex flex-wrap items-center gap-3 mb-4 text-xs">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                <i class="{{ $editorTypeIcon }}" style="color: {{ $editorTypeColor }}"></i>
                {{ $editorTypeLabel }}
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                <i class="fas fa-globe"></i>
                {{ $langName }}
            </span>
        </div>

        {{-- ===== テーマプレビューシェル（常時表示） ===== --}}
        {{-- コンテンツエリアに編集/プレビュータブ + エディタ + プレビューを共存させる --}}
        @includeIf('themes::admin.preview-shell', [
            'previewContent' => '<div class="not-prose">'
                {{-- 編集/プレビュー切替タブ（コンテンツエリア内） --}}
                . '<div class="mb-4">'
                . '<nav class="inline-flex gap-x-1 rounded-lg bg-gray-800/90 backdrop-blur-sm p-1 shadow-lg" aria-label="Tabs">'
                . '<button type="button" @click="showEditor()" :class="!previewMode ? \'bg-blue-600 text-white shadow-sm\' : \'text-gray-300 hover:text-white hover:bg-gray-700/80\'" class="flex items-center gap-x-2 rounded-md px-3 py-1.5 text-sm font-medium whitespace-nowrap transition-colors">'
                . '<i class="fas fa-edit"></i>' . __('components/content-editor.preview_tab_edit')
                . '</button>'
                . '<button type="button" @click="loadPreview()" :class="previewMode ? \'bg-blue-600 text-white shadow-sm\' : \'text-gray-300 hover:text-white hover:bg-gray-700/80\'" class="flex items-center gap-x-2 rounded-md px-3 py-1.5 text-sm font-medium whitespace-nowrap transition-colors">'
                . '<i class="fas fa-eye"></i>' . __('components/content-editor.preview_tab_preview')
                . '</button>'
                . '</nav>'
                . '</div>'
                {{-- エディタ埋め込みターゲット --}}
                . '<div id="editor-embed-target" x-show="!previewMode"></div>'
                {{-- プレビュー表示エリア --}}
                . '<div x-show="previewMode" x-cloak>'
                . '<div x-show="previewLoading" class="text-center py-12">'
                . '<i class="fas fa-spinner fa-spin text-2xl text-gray-400 mb-3 block"></i>'
                . '<p class="text-sm text-gray-500">' . __('components/content-editor.preview_loading') . '</p>'
                . '</div>'
                . '</div>'
                . '</div>'
                {{-- プレビューコンテンツ（prose 適用） --}}
                . '<div x-show="previewMode && !previewLoading" x-cloak id="preview-content-slot"></div>',
        ])

        {{-- ===== エディタフォーム要素（Alpine init でプレビューシェル内に移動される） ===== --}}
        <div x-ref="editorFields" class="hidden">
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

@if($guiEditorAssetHtml ?? '')
    @push('head')
        {!! $guiEditorAssetHtml !!}
    @endpush
@endif
