{{--
This file is part of MySoftware.

Copyright (C) 2025 exc-D inc.
Website: https://exc-d.com

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

<a href="{{ route('admin.media.upload') }}" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition duration-300">新しいファイルをアップロード</a>



<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mt-6">
    @foreach($media as $file)
        <div class="bg-white shadow-md rounded-lg p-4 dark:bg-gray-800">
            <a href="{{ route('admin.media.preview', $file->id) }}" target="_blank">
                @if(in_array($file->type, ['image/jpeg', 'image/png', 'image/gif']))
                    <img src="{{ asset('storage/' . config('admin.mediaPath') . '/' . $file->path) }}" alt="{{ $file->name }}" class="w-full h-32 object-cover rounded">
                @else
                    <div class="flex items-center justify-center w-full h-32 bg-gray-200 rounded">
                        <svg class="w-12 h-12 text-gray-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.75v14.5m-6-6h12"></path>
                        </svg>
                    </div>
                @endif
            </a>

            <div class="mt-4">
                <a href="{{ route('admin.media.preview', $file->id) }}" target="_blank" class="text-sm font-bold truncate">{{ $file->name }}</a>
                <div class="flex gap-2 mt-2 justify-end">
                    <a href="{{ route('admin.media.download', $file->id) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-500">
                        <i class="fas fa-download"></i>
                    </a>

                    <a href="{{ route('admin.media.preview', $file->id) }}" target="_blank" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-500">
                        <i class="fas fa-eye"></i>
                    </a>

                    <form action="{{ route('admin.media.delete', $file->id) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-500">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
