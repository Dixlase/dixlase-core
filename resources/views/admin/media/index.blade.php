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

<a href="{{ route('admin.media.upload') }}" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition duration-300">{{ __('admin.media.index.upload_new_file') }}</a>

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
                    <a href="{{ route('admin.media.download', $file->id) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-500" title="{{ __('admin.media.index.download') }}" aria-label="{{ __('admin.media.index.download') }}">
                        <i class="fas fa-download"></i>
                    </a>

                    <a href="{{ route('admin.media.preview', $file->id) }}" target="_blank" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-500" title="{{ __('admin.media.index.preview') }}" aria-label="{{ __('admin.media.index.preview') }}">
                        <i class="fas fa-eye"></i>
                    </a>

                    <button type="button" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-500" title="{{ __('admin.media.index.delete') }}" aria-label="{{ __('admin.media.index.delete') }}" onclick="openDeleteModal({{ $file->id }}, '{{ $file->name }}')">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- 削除確認モーダル -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900">
                <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mt-4">{{ __('admin.media.preview.delete_confirmation') }}</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('admin.media.preview.delete_message') }}
                </p>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mt-2" id="fileNameDisplay"></p>
            </div>
            <div class="items-center px-4 py-3">
                <button id="confirmDeleteBtn" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800">
                    {{ __('admin.media.index.delete') }}
                </button>
                <button id="cancelDeleteBtn" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:bg-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-800">
                    {{ __('admin.media.preview.cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 削除用の隠しフォーム -->
<form id="deleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
let currentFileId = null;

function openDeleteModal(fileId, fileName) {
    currentFileId = fileId;
    document.getElementById('fileNameDisplay').textContent = fileName;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    currentFileId = null;
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (currentFileId) {
        const form = document.getElementById('deleteForm');
        form.action = `{{ route('admin.media.delete', '') }}/${currentFileId}`;
        form.submit();
    }
});

document.getElementById('cancelDeleteBtn').addEventListener('click', closeDeleteModal);

// モーダル外クリックで閉じる
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});

// ESCキーで閉じる
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('deleteModal').classList.contains('hidden')) {
        closeDeleteModal();
    }
});
</script>

@endsection
