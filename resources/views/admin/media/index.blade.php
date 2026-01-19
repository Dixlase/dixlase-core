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
    <div class="flex justify-start mb-4">
        <x-form.button
            type="link"
            :href="route('admin.media.upload')"
            :label="__('admin/media/index.upload_new_file')"
            variant="primary"
            icon="fas fa-plus"
        />
    </div>

    <!-- 検索フォーム -->
    <section>        
        <form method="GET" action="{{ route('admin.media.index') }}">
            <fieldset>
                <legend>{{ __('common.file_name') }}</legend>
                <x-form.text
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
                    <x-form.select
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

                <fieldset>
                    <legend>{{ __('admin/media/index.search.date_from') }}</legend>
                    <x-form.text
                        type="date"
                        id="date_from"
                        name="date_from"
                        :value="$dateFrom ?? ''"
                    />
                </fieldset>

                <fieldset>
                    <legend>{{ __('admin/media/index.search.date_to') }}</legend>
                    <x-form.text
                        type="date"
                        id="date_to"
                        name="date_to"
                        :value="$dateTo ?? ''"
                    />
                </fieldset>
            </div>

            <div class="flex flex-col sm:flex-row gap-2">
                <x-form.button
                    type="submit"
                    variant="primary"
                    :label="__('common.search')"
                    icon="fas fa-search"
                />
                
                <x-form.button
                    type="button"
                    variant="secondary"
                    :label="__('common.reset')"
                    icon="fas fa-redo"
                    :onclick="\"window.location.href='\" . route('admin.media.index') . \"'\""
                />
            </div>
        </form>
    </section>

    <!-- 上部のページネーションと表示件数設定 -->
    @if($media->hasPages() || $media->count() > 0)
        <div class="media-controls">
            <x-ui.pagination-controls
                :paginator="$media"
                :perPageOptions="[10, 25, 50, 100]"
                :currentPerPage="request('per_page', 25)"
                totalLabel="components.pagination.total_count"
                perPageLabel="components.pagination.per_page_label"
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
            
            <x-ui.pagination
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
                        'mediaPath' => config('admin.mediaPath')
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
            <x-ui.pagination
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
<x-ui.modal
    id="deleteModal"
    data-delete-message="{{ __('admin/media.index.delete_message') }}"
    :title="__('admin/media.preview.delete_confirmation')"
    message=""
    :confirm_label="__('common.delete')"
    :cancel_label="__('common.cancel')"
    icon_type="danger"
    confirm_color="red"
    form="deleteForm"
/>

<!-- 削除用フォーム -->
<form id="deleteForm" data-base-url="{{ url('admin/media/delete') }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>


</div>

@endsection
