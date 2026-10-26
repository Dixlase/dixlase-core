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

{{--
Tag page of the media library (#766): every tag with its file count, each
linking to the library narrowed to it. Members who can edit media add,
rename and delete tags here (mediaTagsPage in
resources/src/admin/media/js/tags.js opens the modals).
--}}

@extends('layouts.admin')

@section('content')
@php
    $editable = ($menuEditable ?? true);
@endphp
<div class="mx-auto" x-data="mediaTagsPage">

    @if($errors->has('name'))
        <div role="alert" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
            {{ $errors->first('name') }}
        </div>
    @endif

    @if($editable)
        <section class="mb-6">
            <form method="POST" action="{{ route('admin.media.tags.store') }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                @csrf
                {{-- Rename shares the "name" field; only refill after an add --}}
                <input type="hidden" name="_tag_form" value="create">
                <div class="flex-1">
                    <label for="newTagName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('admin/media/tags.create.field') }}</label>
                    <input type="text"
                           id="newTagName"
                           name="name"
                           value="{{ old('_tag_form') === 'create' ? old('name') : '' }}"
                           required
                           maxlength="{{ \App\Models\MediaTag::MAX_NAME_LENGTH }}"
                           autocomplete="off"
                           placeholder="{{ __('admin/media/tags.create.placeholder') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <x-form-button
                    type="submit"
                    variant="primary"
                    :label="__('admin/media/tags.create.submit')"
                    icon="fas fa-plus"
                />
            </form>
        </section>
    @endif

    @if($tags->isEmpty())
        <div class="empty-state">
            <i class="fas fa-tags text-6xl" aria-hidden="true"></i>
            <p>{{ __('admin/media/tags.empty') }}</p>
        </div>
    @else
        <div class="responsive-table !border-0 !dark:border-0">
            <table class="border rounded-sm">
                <caption class="sr-only">{{ __('admin/media/tags.heading') }}</caption>
                <thead>
                    <tr>
                        <th>{{ __('admin/media/tags.list.name') }}</th>
                        <th>{{ __('admin/media/tags.list.files') }}</th>
                        @if($editable)
                            <th>{{ __('admin/media/tags.list.actions') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($tags as $tag)
                        <tr>
                            <td data-label="{{ __('admin/media/tags.list.name') }}">
                                <a href="{{ route('admin.media.index', ['tag' => $tag->id]) }}"
                                   title="{{ __('admin/media/tags.list.show_files', ['name' => $tag->name]) }}"
                                   class="break-all hover:underline">{{ $tag->name }}</a>
                            </td>
                            <td data-label="{{ __('admin/media/tags.list.files') }}">
                                {{ __('admin/media/tags.files_count', ['count' => $tag->media_count]) }}
                            </td>
                            @if($editable)
                                <td data-label="{{ __('admin/media/tags.list.actions') }}">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button"
                                                @click="openRenameTagModal($el)"
                                                data-tag-id="{{ $tag->id }}"
                                                data-tag-name="{{ $tag->name }}"
                                                aria-label="{{ __('admin/media/tags.rename.action', ['name' => $tag->name]) }}"
                                                title="{{ __('admin/media/tags.rename.action', ['name' => $tag->name]) }}"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                                            <i class="fas fa-pen text-sm" aria-hidden="true"></i>
                                        </button>
                                        <button type="button"
                                                @click="openDeleteTagModal($el)"
                                                data-tag-id="{{ $tag->id }}"
                                                data-tag-name="{{ $tag->name }}"
                                                data-tag-count="{{ $tag->media_count }}"
                                                aria-label="{{ __('admin/media/tags.delete.action', ['name' => $tag->name]) }}"
                                                title="{{ __('admin/media/tags.delete.action', ['name' => $tag->name]) }}"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-red-600 hover:bg-red-50 hover:text-red-800 dark:text-red-400 dark:hover:bg-red-900/30 dark:hover:text-red-300">
                                            <i class="fas fa-trash-alt text-sm" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($editable && $tags->isNotEmpty())
        <!-- Rename -->
        <x-ui-modal
            id="renameTagModal"
            :title="__('admin/media/tags.rename.title')"
            message=""
            :confirm_label="__('admin/media/tags.rename.confirm')"
            :cancel_label="__('common.cancel')"
            icon_type="info"
            form="renameTagForm"
        >
            <div class="text-left">
                <label for="renameTagName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('admin/media/tags.rename.field') }}</label>
                <input type="text"
                       id="renameTagName"
                       name="name"
                       form="renameTagForm"
                       required
                       maxlength="{{ \App\Models\MediaTag::MAX_NAME_LENGTH }}"
                       autocomplete="off"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
        </x-ui-modal>
        <form id="renameTagForm"
              data-base-url="{{ route('admin.media.tags.rename', ['mediaTag' => '__ID__']) }}"
              method="POST"
              style="display: none;">
            @csrf
            @method('PUT')
        </form>

        <!-- Delete -->
        <x-ui-modal
            id="deleteTagModal"
            data-confirm-message="{{ __('admin/media/tags.delete.message', ['name' => ':name', 'count' => ':count']) }}"
            :title="__('admin/media/tags.delete.title')"
            message=""
            :confirm_label="__('common.delete')"
            :cancel_label="__('common.cancel')"
            icon_type="danger"
            confirm_color="red"
            form="deleteTagForm"
        />
        <form id="deleteTagForm"
              data-base-url="{{ route('admin.media.tags.destroy', ['mediaTag' => '__ID__']) }}"
              method="POST"
              style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    @endif
</div>
@endsection
