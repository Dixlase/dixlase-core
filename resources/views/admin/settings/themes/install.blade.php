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

    <!-- Flash Message -->
    @include('components::flash_message')

    <!-- ファイルアップロードフォーム -->
    <div class="max-w-2xl mx-auto mt-10 bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
        <h2 class="text-2xl font-bold mb-4">テーマをアップロード</h2>
        <form action="{{ route('admin.contents.themes.upload') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2" for="theme">ファイルを選択</label>
                <input type="file" name="theme" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
            </div>
            <button type="submit" class="bg-indigo-600 text-white font-semibold py-2 px-4 rounded-md hover:bg-indigo-700 transition duration-300">
                アップロード
            </button>
        </form>
    </div>


@endsection

