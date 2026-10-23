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
    <form id="front-page-create-form"
          action="{{ route('admin.front.store') }}"
          method="POST"
          class="min-w-0 overflow-hidden">
        @csrf

        <x-form-content-editor
            :storageType="old('storage_type', $defaultStorageType)"
            :editorType="old('editor_type', 'markdown')"
            :content="old('content', '')"
            :showStorageSelector="false"
            :editors="collect($editorCardOptions)->pluck('value')->all()"
            :editorHelp="__('admin/front.create.editor_type_help')"
            :contentLabel="false"
            :placeholder="__('admin/front.create.content_placeholder')"
            :guiEditorInfo="$guiEditorInfo ?? null"
            :preview="true"
            {{-- Through ContentPreviewService, as the page renders: the
                 browser's Markdown renderer knows no {#id} / {.class}. --}}
            previewRender="server"
            :previewFrameUrl="$previewFrameUrl"
            :previewUrl="$previewUrl"
            :assetTabs="true"
            :customCss="old('custom_css', '')"
            :customJs="old('custom_js', '')"
            :cssPlaceholder="__('admin/front.create.custom_css_placeholder')"
            :jsPlaceholder="__('admin/front.create.custom_js_placeholder')"
            :templates="$templates"
            langField="lang"
            :lang="old('lang', $defaultLang)"
            :fileStorageBasePath="$fileStorageBasePath"
            :rightSidebar="true"
        />
        <x-form-error name="editor_type" />
        <x-form-error name="content" />

        {{-- ===== Right Sidebar ===== --}}
        <x-admin.right-sidebar
            :openLabel="__('admin/front.create.sidebar_open')"
            :closeLabel="__('admin/front.create.sidebar_close')"
>

            {{-- Save Method --}}
            <x-content-editor.storage-info
                :storageOptions="$storageOptions"
                :storageType="old('storage_type', $defaultStorageType)"
                :showJsCss="true"
            />

            {{-- Language Selection --}}
            <div>
                <x-form-label :for="'lang'" :text="__('admin/front.create.lang_label')" class="mb-2" />
                <x-form-select
                    id="lang"
                    name="lang"
                    :options="$languages"
                    :value="old('lang', $defaultLang)"
                />
                <x-form-error name="lang" />
            </div>

        </x-admin.right-sidebar>
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
