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
<div class="mx-auto">
    @php
    // コンテンツの取得
    $editorType = $frontPage->editor_type->value ?? 'html';
    $contentColumn = 'content_' . $editorType;
    
    // ファイル保存の場合はファイルから、DB保存の場合はカラムから
    if ($fileContents !== null) {
        $content = old('content', $fileContents);
    } else {
        $content = old('content', $frontPage->{$contentColumn} ?? $frontPage->content ?? '');
    }
    @endphp

    @if(session('success'))
    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
        {{ session('success') }}
    </div>
    @endif

    <form id="frontEditForm" action="{{ route('admin.front.edit.update') }}" method="POST">
        @csrf
        @method('PUT')

        <!-- コンテンツエディタ -->
        <x-content_editor
            :storageType="old('storage_type', $frontPage->storage_type->value ?? 'database')"
            :editorType="old('editor_type', $frontPage->editor_type->value ?? 'html')"
            :title="old('title', $frontPage->title ?? '')"
            :content="$content"
            :identifier="'front-main-content'"
            :pageId="$frontPage->id"
            :contentApiUrl="url('admin/front/edit/content/{storageType}/{editorType}')"
            :showTitle="false"
            :showMetaDescription="false"
            :showOgpImage="false"
            :fileBasePath="'storage/app/private/front'"
            :fileNamePrefix="'content'"
        />
    </form>
</div>
@endsection

<!-- 保存ボタンとモーダル -->
@section('save')
    @include('components.save', [
        'id' => 'confirmationModal',
        'onclick' => "openModal('confirmationModal')",
        'title' => __('admin.settings.front.save_confirmation_title'),
        'label' => __('common.save'),
        'message' => __('admin.settings.front.save_confirmation_message'),
        'confirm_label' => __('common.save'),
        'cancel_label' => __('common.back'),
        'form' => 'frontEditForm',
    ])
@endsection
