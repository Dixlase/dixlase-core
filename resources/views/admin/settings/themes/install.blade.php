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

@php
    /**
     * アップロード出来るファイルサイズの上限を取得
     * @param string $sizeStr PHPの設定値 (例: "2M")
     * @return int バイト数
     */
    function parsePhpSize($sizeStr) {
        $sizeStr = trim($sizeStr);
        $unit = strtoupper(substr($sizeStr, -1)); // 末尾1文字 (K, M, G)
        $value = (int) substr($sizeStr, 0, -1);

        switch ($unit) {
            case 'G':
                $value *= 1024;
                // no break
            case 'M':
                $value *= 1024;
                // no break
            case 'K':
                $value *= 1024;
                break;
            default:
                $value = (int)$sizeStr; // 単位なしの場合
        }
        return $value;
    }

    // PHPの設定から取得
    $uploadMaxFilesize = ini_get('upload_max_filesize');  // 例: "2M"
    $postMaxSize       = ini_get('post_max_size');        // 例: "8M"

    // バイト数に変換
    $uploadMaxBytes = parsePhpSize($uploadMaxFilesize);
    $postMaxBytes   = parsePhpSize($postMaxSize);

    // 画面表示用に "2M" 形式でそのまま表示しても良いし、
    // あるいは数値(MB)を小数込みで表示したい場合は:
    $uploadMaxMB = number_format($uploadMaxBytes / 1048576, 2); // 1MB = 1048576 bytes
    $postMaxMB   = number_format($postMaxBytes / 1048576, 2);
@endphp

@section('content')
<div class="container mx-auto p-6">
    <!-- Flash message for success or error -->
    @if(session('success'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50">
            {{ session('success') }}
        </div>
    @endif

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
        <h2 class="text-2xl font-bold mb-6 text-gray-700 dark:text-white">{{ __('admin.settings.themes.install.upload_title') }}</h2>
        <!-- Alpine.jsでファイルアップロードを管理 -->
        <form
            action="{{ route('admin.settings.themes.upload') }}"
            method="POST"
            enctype="multipart/form-data"
            class="space-y-6"
            x-data="{ fileName: '' }"
        >
            @csrf

            <!-- アップロードフィールド -->
            <div class="flex flex-col gap-2">
                <label for="plugin_file" class="text-gray-600 dark:text-gray-300 font-medium">
                    {{ __('admin.settings.plugins.install.file_select_label') }}
                </label>
                <div
                    class="relative border-2 border-dashed border-gray-300 rounded-lg p-6 hover:border-blue-500 transition duration-300"
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
                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="fileName || '{{ __('admin.settings.plugins.install.drag_drop_text') }}'"></p>
                        <p class="text-xs text-gray-400 mt-1">{{ __('admin.settings.plugins.install.supported_format') }} <strong>.zip</strong></p>

                        <!-- アップロード上限表示 -->
                        <p class="text-xs text-gray-400 mt-1">{{ __('admin.settings.plugins.install.upload_limit') }}
                            <strong>{{ $uploadMaxMB }} MB</strong>
                        </p>
                    </div>
                </div>
            </div>


            <!-- アップロードボタン -->
            <div class="text-right">
                <button
                    type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-6 rounded-lg shadow-md transition duration-300"
                >
                    {{ __('admin.settings.themes.install.upload_button') }}
                </button>
            </div>
        </form>
    </div>
</div>
<!-- Alpine.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endsection
