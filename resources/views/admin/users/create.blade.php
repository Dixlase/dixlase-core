{{--
This file is part of Your Software Name.

Copyright (C) 2024 exc-D inc.
Website: https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@php
$theme_class_header = $theme == 'dark' ? 'bg-gray-900 text-white' : 'bg-white text-gray-900';
@endphp


<x-admin-layout :title="__($title)">
    <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="shadow-md rounded p-6">
            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700">名前</label>
                    <input type="text" name="name" id="name" required
                           class="{{ config('admin.form_class.text') }} {{ config('admin.theme_class.' . $theme . '.form_input_text') }}"
                           value="{{ old('name') }}">
                    @error('name')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700">メールアドレス</label>
                    <input type="email" name="email" id="email" required
                           class="{{ config('admin.form_class.text') }} {{ config('admin.theme_class.' . $theme . '.form_input_text') }}"
                           value="{{ old('email') }}">
                    @error('email')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700">パスワード</label>
                    <input type="password" name="password" id="password" required
                           class="{{ config('admin.form_class.text') }} {{ config('admin.theme_class.' . $theme . '.form_input_text') }}">
                    @error('password')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">パスワード確認</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                           class="{{ config('admin.form_class.text') }} {{ config('admin.theme_class.' . $theme . '.form_input_text') }}">
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="py-2 px-4 bg-indigo-600 text-white rounded-md shadow-sm hover:bg-indigo-700">
                        ユーザーを作成
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
