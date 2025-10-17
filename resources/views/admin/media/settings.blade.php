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
    <form id="media-settings-form" action="{{ route('admin.media.settings.update') }}" method="POST">
        @csrf
        <div class="mb-6">
            <h2>{{ __('admin.media.settings.allowed_file_types') }}</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                @foreach($fileExtensions as $extension)
                    <label class="flex items-center space-x-2 cursor-pointer bg-gray-100 dark:bg-gray-700 p-2 rounded-lg shadow-sm hover:bg-gray-200 dark:hover:bg-gray-600">
                        <input type="checkbox" name="allowed_file_types[]" value="{{ $extension }}" class="form-checkbox h-5 w-5 text-blue-600 dark:text-blue-400"
                            {{ in_array($extension, $allowedFileTypes) ? 'checked' : '' }}>
                        <span class="text-gray-800 dark:text-gray-200">.{{ $extension }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="mb-6">
            <h2 class="text-lg font-bold mb-2 text-gray-900 dark:text-gray-100">{{ __('admin.media.settings.max_file_size') }}</h2>
            <div class="flex items-center space-x-2">
                <input type="number" name="max_file_size" value="{{ round($maxFileSize / 1024, 1) }}" 
                       class="form-input w-32 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400"
                       min="1" max="100" step="1" required>
                <span class="text-gray-600 dark:text-gray-400">MB</span>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.media.settings.file_size_range') }}</span>
            </div>
            @error('max_file_size')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

    </form>
@endsection

@section('save')
    <!-- 保存ボタンとモーダル -->
    @include('components.save', [
        'id' => 'confirmationModal',
        'label' => __('common.save'),
        'onclick' => "openModal('confirmationModal')",
        'title' => __('common.save_confirmation_title'),
        'message' => __('common.save_confirmation_message'),
        'confirm_label' => __('common.save'),
        'cancel_label' => __('common.cancel'),
        'form' => 'media-settings-form',
    ])
@endsection