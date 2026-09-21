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
@php
    // Editable menu access unlocks the destructive bulk-delete UI.
    // Read-only viewers still see cards but never the Select / Delete controls.
    $bulkEditable = ($menuEditable ?? true);
@endphp
<div class="mx-auto" x-data="mediaIndex">
    <span id="mediaBulkStrings"
          data-selected-tpl="{{ __('admin/media/index.bulk.selected_count', ['count' => ':count']) }}"
          hidden></span>

    {{-- .media-toolbar (media.scss) makes this row sticky under the admin bar
         so Upload / bulk-select stay reachable while the grid scrolls. --}}
    <div class="media-toolbar flex flex-wrap items-center justify-between gap-2">
        <x-form-button
            type="link"
            :href="route('admin.media.upload')"
            :label="__('admin/media/index.upload_new_file')"
            variant="primary"
            icon="fas fa-plus"
        />

        @if($bulkEditable && $media->count() > 0)
            <div class="media-bulk-toolbar">
                <button type="button"
                        x-show="!selectionMode"
                        @click="enterSelectMode()"
                        class="media-bulk-toolbar__enter">
                    <i class="fas fa-check-square" aria-hidden="true"></i>
                    {{ __('admin/media/index.bulk.enter_select') }}
                </button>

                <div x-show="selectionMode" x-cloak class="media-bulk-toolbar__panel">
                    <span class="media-bulk-toolbar__count" x-text="selectedCountLabel()"></span>
                    <button type="button" @click="selectAllOnPage()" class="media-bulk-toolbar__link">
                        {{ __('admin/media/index.bulk.select_all') }}
                    </button>
                    <button type="button"
                            @click="clearSelection()"
                            :disabled="selectedIds.length === 0"
                            class="media-bulk-toolbar__link">
                        {{ __('admin/media/index.bulk.clear') }}
                    </button>
                    <button type="button"
                            @click="openBulkDeleteModal()"
                            :disabled="selectedIds.length === 0"
                            class="media-bulk-toolbar__delete">
                        <i class="fas fa-trash-alt" aria-hidden="true"></i>
                        {{ __('admin/media/index.bulk.delete_selected') }}
                    </button>
                    <button type="button" @click="exitSelectMode()" class="media-bulk-toolbar__done">
                        {{ __('admin/media/index.bulk.exit_select') }}
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- 検索フォーム -->
    <section>        
        <form method="GET" action="{{ route('admin.media.index') }}">
            <fieldset>
                <legend>{{ __('common.file_name') }}</legend>
                <x-form-text
                    type="text"
                    id="search"
                    name="search"
                    :value="$search ?? ''"
                    :placeholder="__('admin/media/index.search.file_name_placeholder')"
                />
            </fieldset>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                <fieldset>
                    <legend>{{ __('common.file_type') }}</legend>
                    <x-form-select
                        id="file_type"
                        name="file_type"
                        :options="[
                            '' => 'common.all',
                            'image' => 'admin.media.types.image',
                            'video' => 'admin.media.types.video',
                            'audio' => 'admin.media.types.audio',
                            'document' => 'admin.media.types.document',
                        ]"
                        :value="$fileType ?? ''"
                    />
                </fieldset>

                {{-- !my-0 on both dates: x-form-text adds my-2 for stacked forms,
                     which dropped these two inputs 8px below the file-type
                     select in the same row (the select carries no margin). --}}
                <fieldset>
                    <legend>{{ __('admin/media/index.search.date_from') }}</legend>
                    <x-form-text
                        type="date"
                        id="date_from"
                        name="date_from"
                        :value="$dateFrom ?? ''"
                        class="!my-0"
                    />
                </fieldset>

                <fieldset>
                    <legend>{{ __('admin/media/index.search.date_to') }}</legend>
                    <x-form-text
                        type="date"
                        id="date_to"
                        name="date_to"
                        :value="$dateTo ?? ''"
                        class="!my-0"
                    />
                </fieldset>
            </div>

            <div class="flex flex-col sm:flex-row gap-2">
                <x-form-button
                    type="submit"
                    variant="primary"
                    :label="__('common.search')"
                    icon="fas fa-search"
                />
                
                <x-form-button
                    type="link"
                    variant="secondary"
                    :label="__('common.reset')"
                    icon="fas fa-redo"
                    :href="route('admin.media.index')"
                />
            </div>
        </form>
    </section>

    <!-- 上部のページネーションと表示件数設定 -->
    @if($media->hasPages() || $media->count() > 0)
        <div class="media-controls">
            <x-ui-pagination-controls
                :paginator="$media"
                :perPageOptions="[10, 25, 50, 100]"
                :currentPerPage="request('per_page', 25)"
                totalLabel="components/ui-pagination.total_count"
                perPageLabel="components/ui-pagination.per_page_label"
                :showSort="true"
                :sortOptions="[
                    'name' => __('common.file_name'),
                    'type' => __('common.file_type'),
                    'created_at' => __('common.created_at'),
                    'updated_at' => __('common.updated_at'),
                ]"
                :currentSort="$currentSort ?? 'created_at'"
                :currentOrder="$currentOrder ?? 'desc'"
            />
            
            <x-ui-pagination
                :pagination="[
                    'current_page' => $media->currentPage(),
                    'last_page' => $media->lastPage(),
                    'prev_page' => $media->currentPage() > 1 ? $media->currentPage() - 1 : null,
                    'next_page' => $media->hasMorePages() ? $media->currentPage() + 1 : null,
                ]"
                route="admin.media.index"
                :routeParams="request()->except('page')"
            />
        </div>
    @endif

    <div class="media-gallery">
        @if($media->count() > 0)
            <div class="media-grid">
                @foreach($media as $file)
                    @include('admin.media.partials.card', [
                        'file' => $file,
                        'mediaPath' => config('admin.files.mediaPath')
                    ])
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <i class="fas fa-images text-6xl"></i>
                <p>{{ __('admin/media/index.no_files') }}</p>
                <p class="description-text">{{ __('admin/media/index.upload_first_file') }}</p>
            </div>
        @endif
    </div>

    <!-- 下部のページネーション -->
    @if($media->hasPages())
        <div class="media-controls media-controls--bottom">
            <x-ui-pagination
                :pagination="[
                    'current_page' => $media->currentPage(),
                    'last_page' => $media->lastPage(),
                    'prev_page' => $media->currentPage() > 1 ? $media->currentPage() - 1 : null,
                    'next_page' => $media->hasMorePages() ? $media->currentPage() + 1 : null,
                ]"
                route="admin.media.index"
                :routeParams="request()->except('page')"
            />
        </div>
    @endif

<!-- 削除確認モーダル -->
<x-ui-modal
    id="deleteModal"
    data-delete-message="{{ __('admin/media/index.delete_message') }}"
    :title="__('admin/media/preview.delete_confirmation')"
    message=""
    :confirm_label="__('common.delete')"
    :cancel_label="__('common.cancel')"
    icon_type="danger"
    confirm_color="red"
    form="deleteForm"
/>

<!-- 削除用フォーム -->
<form id="deleteForm" data-base-url="{{ route('admin.media.delete', ['media' => '__ID__']) }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

@if($bulkEditable)
    <!-- 一括削除確認モーダル -->
    <x-ui-modal
        id="bulkDeleteModal"
        data-confirm-message="{{ __('admin/media/index.bulk.confirm_message', ['count' => ':count']) }}"
        :title="__('admin/media/index.bulk.confirm_title')"
        message=""
        :confirm_label="__('common.delete')"
        :cancel_label="__('common.cancel')"
        icon_type="danger"
        confirm_color="red"
        form="bulkDeleteForm"
    />

    <!-- 一括削除フォーム: ids[] は Alpine が動的に注入 -->
    <form id="bulkDeleteForm"
          action="{{ route('admin.media.bulk-delete') }}"
          method="POST"
          style="display: none;">
        @csrf
    </form>
@endif

</div>

@endsection
