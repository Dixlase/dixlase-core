{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

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

@extends('layouts.admin')

@section('content')
    {{-- Changes made to the files outside the admin, waiting to be
         published (#555). Outside the edit form: it posts its own form. --}}
    <x-content.file-changes
        id="front-file-changes"
        :changes="$fileChanges"
        :publishUrl="route('admin.front.publish-file')"
        :canPublish="$canPublishFile"
    />

    <form id="front-page-edit-form"
          action="{{ route('admin.front.edit.update') }}"
          method="POST"
          class="min-w-0 overflow-hidden">
        @csrf
        @method('PUT')

        {{-- The editor type and storage are fixed once the front page
             exists (see the sidebar). --}}
        <x-form-content-editor
            :storageType="$storageType"
            :editorType="$editorType"
            :content="$body"
            :showStorageSelector="false"
            :showEditorSelector="false"
            :contentLabel="false"
            :placeholder="__('admin/front.edit.content_placeholder')"
            :guiEditorInfo="$guiEditorInfo ?? null"
            :preview="true"
            {{-- Through ContentPreviewService, as the page renders: the
                 browser's Markdown renderer knows no {#id} / {.class}. --}}
            previewRender="server"
            :previewFrameUrl="$previewFrameUrl"
            :previewUrl="$previewUrl"
            :assetTabs="$isHtmlEditor"
            :customCss="$customCss ?? ''"
            :customJs="$customJs ?? ''"
            :cssPlaceholder="__('admin/front.edit.custom_css_placeholder')"
            :jsPlaceholder="__('admin/front.edit.custom_js_placeholder')"
            :fileStorageBasePath="$fileStorageBasePath"
            :rightSidebar="true"
        />
        <x-form-error name="content" />

        {{-- ===== Right Sidebar ===== --}}
        <x-admin.right-sidebar
            :openLabel="__('admin/front.edit.sidebar_open')"
            :closeLabel="__('admin/front.edit.sidebar_close')"
        >

            {{-- Meta Information --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                    {{ __('admin/front.edit.meta_section') }}
                </h3>
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                        <i class="{{ $editorTypeIcon }}" style="color: {{ $editorTypeColor }}"></i>
                        {{ $editorTypeLabel }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                        <i class="fas fa-globe"></i>
                        {{ $langName }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                        <i class="fas fa-lock text-gray-500 dark:text-gray-400"></i>
                        {{ $storageOptions[$storageType]['label'] ?? $storageType }}
                    </span>
                </div>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('admin/front.edit.storage_locked_help') }}
                </p>

                {{-- Path display on file save; the paths come from the
                     editor (see the contentEditorStorageInfo component). --}}
                <div x-data="contentEditorStorageInfo" data-storage-type="{{ $storageType }}"
                     x-show="isFileStorage" x-cloak class="mt-3 text-xs space-y-1">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">{{ __('admin/front.edit.storage_file_path') }}</span>
                        <span class="font-mono text-blue-600 dark:text-blue-400 break-all" x-text="filePath"></span>
                    </div>
                    <div x-show="showJsPath" x-cloak>
                        <span class="text-gray-500 dark:text-gray-400">JS:</span>
                        <span class="font-mono text-blue-600 dark:text-blue-400 break-all" x-text="jsFilePath"></span>
                    </div>
                    <div x-show="showCssPath" x-cloak>
                        <span class="text-gray-500 dark:text-gray-400">CSS:</span>
                        <span class="font-mono text-blue-600 dark:text-blue-400 break-all" x-text="cssFilePath"></span>
                    </div>
                </div>
            </div>

            {{-- Revision --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                    {{ __('admin/front.edit.revisions_section') }}
                </h3>
                <x-form-button
                    type="link"
                    variant="secondary"
                    size="sm"
                    icon="fas fa-clock-rotate-left"
                    :href="route('admin.front.revisions.index')"
                >
                    {{ __('admin/front.edit.revisions_button') }}
                </x-form-button>
            </div>

            {{-- Reset --}}
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

        </x-admin.right-sidebar>
    </form>

    {{-- Reset Confirmation Modal --}}
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
