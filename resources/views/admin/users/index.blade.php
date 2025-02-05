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

    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h3 class="text-lg font-semibold">ユーザー検索</h3>

        <!-- 検索フォーム -->
        <form action="{{ route('admin.users.index') }}" method="GET" class="mb-6">
            <div class="flex items-center">
                @csrf
                @include('components::form.text', [
                    'name' => 'search',
                    'placeholder' => 'ユーザー名やメールアドレスで検索',
                    'value' => $search,

                ])

                @include('components::form.button', [
                    'type' => "submit",
                    'label' => '検索',
                ])

            </div>
        </form>
    </div>

        <!-- デスクトップ用テーブル -->
        <div class="hidden md:block overflow-x-auto">
            <table class="{{ config('admin.appearance_class.table.table') }}">
                <thead class="{{ config('admin.appearance_class.table.thead') }}">
                    <tr>
                        <th class="{{ config('admin.appearance_class.table.td') }}">ID</th>
                        <th class="{{ config('admin.appearance_class.table.td') }}">名前</th>
                        <th class="{{ config('admin.appearance_class.table.td') }}">メールアドレス</th>
                        <th class="{{ config('admin.appearance_class.table.td') }}">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr class="{{ config('admin.appearance_class.table.tr') }}">
                            <td class="{{ config('admin.appearance_class.table.td') }}">{{ $user->id }}</td>
                            <td class="{{ config('admin.appearance_class.table.td') }}">{{ $user->name }}</td>
                            <td class="{{ config('admin.appearance_class.table.td') }}">{{ $user->email }}</td>
                            <td class="{{ config('admin.appearance_class.table.td') }}">
                                <a href="{{ route('admin.users.edit', ['user' => $user->id]) }}" class="{{ config('admin.appearance_class.link') }}">
                                    編集
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- モバイル用カード -->
        <div class="block md:hidden">
            @foreach ($users as $user)
                <div class="border rounded-lg p-4 mb-4">
                    <p><strong>ID:</strong> {{ $user->id }}</p>
                    <p><strong>名前:</strong> {{ $user->name }}</p>
                    <p><strong>メールアドレス:</strong> {{ $user->email }}</p>
                    <div class="mt-2">
                        <a href="{{ route('admin.users.edit', ['user' => $user->id]) }}"
                        class="{{ config('admin.appearance_class.link') }}">
                            編集
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- ページネーション -->
        <div class="mt-4">
            {{ $users->links() }}
        </div>

@endsection
