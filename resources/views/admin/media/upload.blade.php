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
    <div class="bg-white shadow-md rounded-lg p-6 dark:bg-gray-800">
        <form action="{{ route('admin.media.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-6" x-data="{ fileName: '' }">
            @csrf
            <div class="flex flex-col gap-2">
                <label for="media_file" class="font-medium">{{ __('admin.media.upload.select_file') }}</label>
                <div class="relative border-2 border-dashed border-gray-300 rounded-lg p-6 hover:border-blue-500 transition duration-300">
                    <input type="file" name="file" id="media_file" accept=".jpg,.png,.gif,.mp4,.pdf,.docx"
                        class="absolute top-0 left-0 w-full h-full opacity-0 cursor-pointer"
                        @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''"
                        required>

                    <div class="flex flex-col items-center justify-center text-center pointer-events-none">
                        <svg class="w-12 h-12 text-blue-500 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16v4m0 0H8m4 0h4m-4-4a4 4 0 01-4-4 4 4 0 014-4 4 4 0 014 4 4 4 0 01-4 4z"/>
                        </svg>
                        <p class="text-sm" x-text="fileName || '{{ __('admin.media.upload.drag_drop_text') }}'"></p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('admin.media.upload.supported_formats') }}
                            <strong>
                                @foreach($allowedFileTypes as $extension)
                                    .{{ $extension }}
                                    @if (!$loop->last)
                                        ,
                                    @endif
                                @endforeach
                            </strong>
                        </p>
                    </div>
                </div>
            </div>

            <div class="text-right">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-6 rounded-lg shadow-md transition duration-300">
                    {{ __('common.upload') }}
                </button>
            </div>
        </form>
    </div>
    <!-- Alpine.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endsection
