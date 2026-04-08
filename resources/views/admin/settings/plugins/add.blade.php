{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
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

    @if ($errors->any())
        <div class="mb-4 p-4 text-red-800 bg-red-100 border border-red-200 rounded-lg">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
        <h2 class="text-2xl font-bold mb-6 text-gray-700 dark:text-white">{{ __('admin/settings/plugins/add.upload_title') }}</h2>
        <!-- Alpine.jsでファイルアップロードを管理 -->
        <form
            action="{{ route('admin.settings.plugins.upload') }}"
            method="POST"
            enctype="multipart/form-data"
            class="space-y-6"
            x-data="{ fileName: '' }"
        >
            @csrf

            <!-- アップロードフィールド -->
            <div class="flex flex-col gap-2">
                <label for="plugin_file" class="text-gray-600 dark:text-gray-300 font-medium">
                    {{ __('admin/settings/plugins/add.file_select_label') }}
                </label>
                <div
                    class="relative border-2 border-dashed border-gray-300 rounded-lg p-6 hover:border-blue-500 transition duration-300 max-w-full"
                >
                    <!-- ファイル入力 -->
                    <input
                        type="file"
                        name="plugin_file"
                        id="plugin_file"
                        accept=".zip"
                        class="absolute top-0 left-0 w-full h-full opacity-0 cursor-pointer"
                        @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''"
                        required
                    >

                    <!-- ドロップエリア表示部分 -->
                    <div class="flex flex-col items-center justify-center text-center pointer-events-none">
                        <svg
                            class="w-12 h-12 text-blue-500 mb-3"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                            viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 16v4m0 0H8m4 0h4m-4-4a4 4 0 01-4-4 4 4 0 014-4 4 4 0 014 4 4 4 0 01-4 4z"
                            ></path>
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="fileName || '{{ __('admin/settings/plugins/add.drag_drop_text') }}'"></p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('admin/settings/plugins/add.supported_format') }} <strong>.zip</strong></p>

                        <!-- アップロード上限表示 -->
                        <p class="text-xs text-gray-400 mt-1">{{ __('admin/settings/plugins/add.upload_limit') }}
                            <strong>{{ $uploadMaxMB }} MB</strong>
                        </p>
                    </div>
                </div>
            </div>

            <!-- アップロードボタン -->
            <div class="flex justify-end">
                <button
                    type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg shadow-md transition duration-300"
                >
                    {{ __('common.upload') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
