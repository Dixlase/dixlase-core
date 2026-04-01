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
    @if ($frontPage)
        {{-- コンテンツ存在時: ステータスカード --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                {{ __('admin/front.index.content_exists_title') }}
            </h2>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/front.index.language') }}</dt>
                    <dd class="mt-1 font-medium text-gray-900 dark:text-white">{{ $langName }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/front.index.editor_type') }}</dt>
                    <dd class="mt-1 font-medium text-gray-900 dark:text-white">{{ $editorTypeLabel }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/front.index.storage_type') }}</dt>
                    <dd class="mt-1 font-medium text-gray-900 dark:text-white">{{ $storageTypeLabel }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/front.index.last_updated') }}</dt>
                    <dd class="mt-1 font-medium text-gray-900 dark:text-white">{{ $frontPage->updated_at->format('Y-m-d H:i') }}</dd>
                </div>
            </dl>

            <div class="mt-6 flex flex-wrap gap-3">
                <x-form-button
                    type="link"
                    variant="primary"
                    icon="fas fa-edit"
                    :href="route('admin.front.edit')"
                >
                    {{ __('admin/front.index.edit_button') }}
                </x-form-button>

                <x-form-button
                    type="button"
                    variant="danger"
                    icon="fas fa-undo"
                    @click="openModal('resetFrontPageModal')"
                >
                    {{ __('admin/front.index.reset_button') }}
                </x-form-button>
            </div>
        </div>
    @else
        {{-- コンテンツ未存在時: 空ステート --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
            <div class="mx-auto w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                <i class="fas fa-file-alt text-2xl text-gray-400 dark:text-gray-500"></i>
            </div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                {{ __('admin/front.index.no_content_title') }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                {{ __('admin/front.index.no_content_description') }}
            </p>
            <x-form-button
                type="link"
                variant="primary"
                icon="fas fa-plus"
                :href="route('admin.front.create')"
            >
                {{ __('admin/front.index.create_button') }}
            </x-form-button>
        </div>
    @endif

    {{-- Theme Preview --}}
    @if ($frontPage && !empty($previewContent))
        <div class="mt-6">
            @includeIf('themes::admin.preview-shell', ['previewContent' => $previewContent])
        </div>
    @endif

    @if ($frontPage)
        <x-ui-modal
            id="resetFrontPageModal"
            :title="__('admin/front.index.reset_confirm_title')"
            :message="__('admin/front.index.reset_confirm')"
            :confirm-label="__('admin/front.index.reset_button')"
            :cancel-label="__('common.cancel')"
            icon-type="danger"
            confirm-color="red"
            form="front-page-reset-form"
        />

        <form id="front-page-reset-form"
              action="{{ route('admin.front.destroy') }}"
              method="POST"
              style="display: none;">
            @csrf
            @method('DELETE')
        </form>
    @endif
@endsection
