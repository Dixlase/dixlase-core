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


    <form action="{{ route('admin.media.settings.update') }}" method="POST">
        @csrf
        <div class="mb-4">
            <h2 class="text-lg font-bold">許可するファイルタイプ</h2>
            @foreach($fileExtensions as $extension)
                <div>
                    <label>
                        <input type="checkbox" name="allowed_file_types[]" value="{{ $extension }}"
                            {{ in_array($extension, $allowedFileTypes) ? 'checked' : '' }}>
                        .{{ $extension }}
                    </label>
                </div>
            @endforeach
        </div>



        <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded">
            設定を保存
        </button>
    </form>
@endsection

