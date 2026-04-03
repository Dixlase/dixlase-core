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

        {{-- ===== メインコンテンツエリア ===== --}}
        <div class="space-y-6 min-w-0 overflow-hidden">
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

            {{-- ===== プレビュー ===== --}}
            <div x-show="previewVisible" x-cloak>
                {{-- プレビューヘッダー --}}
                <div class="flex flex-wrap items-center justify-between px-4 py-2 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-t-lg gap-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                            <i class="fas fa-eye mr-1"></i>{{ __('admin/front.edit.preview_title') }}
                        </span>
                        {{-- スケール表示（縮小時のみ） --}}
                        <span x-show="previewScale < 1" x-cloak
                              class="text-[10px] text-gray-400 dark:text-gray-500"
                              x-text="Math.round(previewScale * 100) + '%'"></span>
                    </div>

                    <div class="flex items-center gap-3">
                        {{-- デバイストグルボタン --}}
                        <div class="flex items-center bg-gray-100 dark:bg-gray-800 rounded-lg p-0.5 gap-0.5">
                            <button type="button" @click="setPreviewDevice('mobile')"
                                :class="previewDevice === 'mobile' ? 'bg-white dark:bg-gray-600 shadow-sm text-blue-600 dark:text-blue-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                                class="px-2.5 py-1.5 rounded-md transition-all text-xs"
                                title="{{ __('admin/front.edit.device_mobile') }} (375×667)">
                                <i class="fas fa-mobile-alt"></i>
                            </button>
                            <button type="button" @click="setPreviewDevice('tablet')"
                                :class="previewDevice === 'tablet' ? 'bg-white dark:bg-gray-600 shadow-sm text-blue-600 dark:text-blue-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                                class="px-2.5 py-1.5 rounded-md transition-all text-xs"
                                title="{{ __('admin/front.edit.device_tablet') }} (768×1024)">
                                <i class="fas fa-tablet-alt"></i>
                            </button>
                            <button type="button" @click="setPreviewDevice('desktop')"
                                :class="previewDevice === 'desktop' ? 'bg-white dark:bg-gray-600 shadow-sm text-blue-600 dark:text-blue-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                                class="px-2.5 py-1.5 rounded-md transition-all text-xs"
                                title="{{ __('admin/front.edit.device_desktop') }} (1440×900)">
                                <i class="fas fa-desktop"></i>
                            </button>
                            <button type="button" @click="setPreviewDevice('free')"
                                :class="previewDevice === 'free' ? 'bg-white dark:bg-gray-600 shadow-sm text-blue-600 dark:text-blue-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                                class="px-2.5 py-1.5 rounded-md transition-all text-xs"
                                title="{{ __('admin/front.edit.device_free') }}">
                                <i class="fas fa-expand-arrows-alt"></i>
                            </button>
                        </div>

                        {{-- サイズ表示 / フリーサイズ入力 --}}
                        <div class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                            <template x-if="previewDevice === 'free'">
                                <div class="flex items-center gap-1">
                                    <input type="number" x-model.number="freeWidth" min="200" max="3840"
                                           class="w-16 px-1.5 py-0.5 text-xs rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                                           title="{{ __('admin/front.edit.preview_width') }}">
                                    <span>×</span>
                                    <input type="number" x-model.number="freeHeight" min="200" max="3840"
                                           class="w-16 px-1.5 py-0.5 text-xs rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                                           title="{{ __('admin/front.edit.preview_height') }}">
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
                    {{-- ローディングオーバーレイ --}}
                    <div x-show="previewLoading" x-transition class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-900 z-10">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mb-3 block"></i>
                            <p class="text-sm text-gray-500">{{ __('components/content-editor.preview_loading') }}</p>
                        </div>
                    </div>

                    {{-- スケーリングラッパー --}}
                    <div :style="'width: ' + scaledPreviewWidth + 'px; height: ' + scaledPreviewHeight + 'px; margin: 0 auto;'">
                        <iframe x-ref="previewIframe"
                                :src="previewFrameUrl"
                                class="bg-white"
                                :style="'width: ' + currentPreviewWidth + 'px; height: ' + currentPreviewHeight + 'px; transform: scale(' + previewScale + '); transform-origin: top left;'"
                                sandbox="allow-scripts allow-same-origin allow-forms"
                                title="{{ __('admin/front.edit.preview_title') }}">
                        </iframe>
                    </div>

                    {{-- 下辺リサイズハンドル --}}
                    <div class="absolute -bottom-1.5 left-0 w-full h-3 cursor-ns-resize group z-10"
                         @mousedown="startPreviewResize($event, 'vertical')">
                        <div class="absolute bottom-0.5 left-1/2 -translate-x-1/2 h-1 w-8 rounded-full bg-gray-300 dark:bg-gray-600 group-hover:bg-blue-400 transition-colors"></div>
                    </div>
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
