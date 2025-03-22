{{--
This file is part of MySoftware.

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

@extends('admin::partials.layout')

@section('content')
    <!-- Flash message for success or error -->
    @include('components::flash_message')

    <form action="{{ route('admin.media.settings.update') }}" method="POST" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
        @csrf
        <div class="mb-6">
            <h2 class="text-lg font-bold mb-2 text-gray-900 dark:text-gray-100">許可するファイルタイプ</h2>
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

        <button type="submit" class="bg-blue-600 dark:bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-700 dark:hover:bg-blue-400 transition">
            設定を保存
        </button>
    </form>
@endsection