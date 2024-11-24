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
// テーマの設定を取得
$theme_class_header = $theme == 'dark' ? 'bg-gray-900 text-white' : 'bg-white text-gray-900';
@endphp


<x-admin-layout :title="$title" :theme="$theme">
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h2 class="text-lg font-semibold">ユーザー検索</h2>

        <!-- 検索フォーム -->
        <form action="{{ route('admin.users.index') }}" method="GET" class="mb-6">
            <div class="flex items-center">
                <input
                    type="text"
                    name="search"
                    placeholder="ユーザー名やメールアドレスで検索"
                    value="{{ $search }}"
                    class="{{ config('admin.form_class.text') }} {{ config('admin.theme_class.' . $theme . '.form_input_text') }}"
                >
                <button
                    type="submit"
                    class="ml-2 px-4 py-2 bg-indigo-600 text-white rounded-md shadow-sm hover:bg-indigo-700">
                    検索
                </button>
            </div>
        </form>

        <!-- ユーザー一覧 -->
        <div class="shadow-md rounded p-6">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="">
                        <th class="border px-4 py-2">ID</th>
                        <th class="border px-4 py-2">名前</th>
                        <th class="border px-4 py-2">メールアドレス</th>
                        <th class="border px-4 py-2">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td class="border px-4 py-2">{{ $user->id }}</td>
                            <td class="border px-4 py-2">{{ $user->name }}</td>
                            <td class="border px-4 py-2">{{ $user->email }}</td>
                            <td class="border px-4 py-2">
                                <a href="{{ route('admin.users.edit', ['user' => $user->id]) }}"
                                   class="text-indigo-600 hover:text-indigo-900">
                                    編集
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- ページネーション -->
            <div class="mt-4">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</x-admin-layout>
