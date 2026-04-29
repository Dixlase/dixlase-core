{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see LICENSE
      for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact office@exc-d.com).

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
    <div class="bg-white shadow-md rounded-lg p-6 dark:bg-gray-800"
         x-data="mediaUploader('{{ route('admin.media.store') }}', '{{ csrf_token() }}', '{{ route('admin.media.index') }}')">

        <div class="flex flex-col gap-2">
            <label class="font-medium">{{ __('admin/media/upload.select_file') }}</label>
            <div class="relative border-2 border-dashed rounded-lg p-8 transition duration-300 cursor-pointer"
                 :class="isDragging ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-blue-500'"
                 @dragover.prevent="isDragging = true"
                 @dragleave.prevent="isDragging = false"
                 @drop.prevent="handleDrop($event)"
                 @click="$refs.fileInput.click()">

                <input type="file" name="files[]" x-ref="fileInput"
                       accept="{{ collect($allowedFileTypes)->map(fn($ext) => '.' . $ext)->implode(',') }}"
                       class="hidden"
                       multiple
                       @change="handleFileSelect($event)">

                <div class="flex flex-col items-center justify-center text-center pointer-events-none">
                    <i class="fas fa-cloud-upload-alt text-4xl text-blue-500 mb-3"></i>
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('admin/media/upload.drag_drop_text') }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ __('admin/media/upload.supported_formats') }}
                        <strong>{{ collect($allowedFileTypes)->map(fn($ext) => '.' . $ext)->implode(', ') }}</strong>
                    </p>
                    <p class="text-xs text-gray-400 mt-1">{{ __('admin/media/upload.multiple_files_hint') }}</p>
                </div>
            </div>
        </div>

        {{-- アップロード進捗表示 --}}
        <div x-show="queue.length > 0" x-cloak class="mt-4 space-y-2">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('admin/media/upload.upload_progress') }}
                <span x-text="completedCount + '/' + queue.length"></span>
            </h3>
            <template x-for="(item, index) in queue" :key="index">
                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex-shrink-0">
                        <i x-show="item.status === 'pending'" class="fas fa-clock text-gray-400"></i>
                        <i x-show="item.status === 'uploading'" class="fas fa-spinner fa-spin text-blue-500"></i>
                        <i x-show="item.status === 'success'" class="fas fa-check-circle text-green-500"></i>
                        <i x-show="item.status === 'error'" class="fas fa-times-circle text-red-500"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm truncate text-gray-700 dark:text-gray-300" x-text="item.name"></p>
                        <p x-show="item.error" class="text-xs text-red-500" x-text="item.error"></p>
                    </div>
                    <div class="flex-shrink-0 text-xs text-gray-500" x-text="item.sizeLabel"></div>
                </div>
            </template>
        </div>

        {{-- 完了メッセージ --}}
        <div x-show="allDone && successCount > 0" x-cloak class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg">
            <p class="text-sm text-green-700 dark:text-green-300">
                <i class="fas fa-check-circle mr-1"></i>
                <span x-text="successMessage"></span>
            </p>
            <a :href="redirectUrl" class="inline-block mt-2 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                <i class="fas fa-arrow-left mr-1"></i>{{ __('admin/media/upload.back_to_list') }}
            </a>
        </div>

        <div x-show="allDone && failCount > 0" x-cloak class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
            <p class="text-sm text-red-700 dark:text-red-300">
                <i class="fas fa-exclamation-circle mr-1"></i>
                <span x-text="failCount + ' {{ __('admin/media/upload.files_failed') }}'"></span>
            </p>
        </div>
    </div>

    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-5">
        <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-3">
            <i class="fas fa-cog mr-1"></i>
            {{ ($isSimpleMode ?? false) ? __('admin/media/upload.settings_heading_auto') : __('admin/media/upload.settings_heading') }}
        </h3>

        {{-- 許可されたファイルタイプ --}}
        <section class="mb-4">
            <h4 class="text-xs font-medium text-blue-700 dark:text-blue-400 mb-2">{{ __('admin/media/upload.allowed_file_types') }}</h4>
            <div class="flex flex-wrap gap-2">
                @foreach($allowedFileTypes as $extension)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                        .{{ $extension }}
                        @if(isset($fileExtensionNames[$extension]))
                            <span class="text-gray-400 dark:text-gray-500 ml-1">({{ $fileExtensionNames[$extension] }})</span>
                        @endif
                    </span>
                @endforeach
            </div>
        </section>

        {{-- ファイルタイプ別サイズ上限 --}}
        <section class="mb-4">
            <h4 class="text-xs font-medium text-blue-700 dark:text-blue-400 mb-2">{{ __('admin/media/upload.size_limits') }}</h4>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <div class="bg-white dark:bg-gray-800 rounded px-3 py-2 text-center">
                    <i class="fas fa-image text-green-500"></i>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('admin/media/settings.category.image') }}</p>
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $fileSizeLimits['image'] }} MB</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded px-3 py-2 text-center">
                    <i class="fas fa-video text-purple-500"></i>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('admin/media/settings.category.video') }}</p>
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $fileSizeLimits['video'] }} MB</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded px-3 py-2 text-center">
                    <i class="fas fa-file-alt text-blue-500"></i>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('admin/media/settings.category.document') }}</p>
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $fileSizeLimits['document'] }} MB</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded px-3 py-2 text-center">
                    <i class="fas fa-file-archive text-orange-500"></i>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('admin/media/settings.category.archive') }}</p>
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $fileSizeLimits['archive'] }} MB</p>
                </div>
            </div>
        </section>

        {{-- セキュリティ設定 --}}
        <section>
            <h4 class="text-xs font-medium text-blue-700 dark:text-blue-400 mb-2">{{ __('admin/media/upload.security_status') }}</h4>
            <ul class="space-y-1">
                <li class="flex items-center text-xs text-gray-700 dark:text-gray-300">
                    <i class="fas {{ ($securityStatus['mime_validation'] ?? true) ? 'fa-check-circle text-green-500' : 'fa-times-circle text-red-500' }} mr-2"></i>
                    {{ __('admin/media/settings.mime_validation') }}
                </li>
                <li class="flex items-center text-xs text-gray-700 dark:text-gray-300">
                    <i class="fas {{ ($securityStatus['svg_sanitization'] ?? true) ? 'fa-check-circle text-green-500' : 'fa-times-circle text-red-500' }} mr-2"></i>
                    {{ __('admin/media/settings.svg_sanitization') }}
                </li>
                <li class="flex items-center text-xs text-gray-700 dark:text-gray-300">
                    <i class="fas {{ ($securityStatus['zip_security'] ?? true) ? 'fa-check-circle text-green-500' : 'fa-times-circle text-red-500' }} mr-2"></i>
                    {{ __('admin/media/settings.zip_security') }}
                </li>
            </ul>
        </section>
    </div>
@endsection
