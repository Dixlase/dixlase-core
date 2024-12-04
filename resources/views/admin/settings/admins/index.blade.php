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

@extends('admin::partials.layout')

@section('content')
    <!-- Flash message for success or error -->
    @include('components::flash_message')

    <h3 class="text-lg font-semibold">管理者検索</h3>

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

    <!-- 管理者一覧 -->
    <div class="shadow-md rounded p-6">

        <!-- デスクトップ用テーブル -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm text-left rtl:text-right">
                <thead class="text-xs uppercase {{ config('admin.appearance_class.table.header') }}">
                    <tr>
                        <th class="border px-4 py-2">ID</th>
                        <th class="border px-4 py-2">名前</th>
                        <th class="border px-4 py-2">メールアドレス</th>
                        <th class="border px-4 py-2">権限</th>
                        <th class="border px-4 py-2">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($admins as $admin)
                        <tr class="{{ config('admin.appearance_class.table.row') }}">
                            <td class="border px-4 py-2">{{ $admin->id }}</td>
                            <td class="border px-4 py-2">{{ $admin->name }}</td>
                            <td class="border px-4 py-2">{{ $admin->email }}</td>
                            <td class="border px-4 py-2">{{ $admin->role }}</td>
                            <td class="border px-4 py-2">
                                <a href="{{ route('admin.settings.admins.edit', ['admin' => $admin->id]) }}"
                                    class="{{ config('admin.appearance_class.link') }}">
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
            @foreach ($admins as $admin)
                <div class="border rounded-lg p-4 mb-4 shadow">
                    <p><strong>ID:</strong> {{ $admin->id }}</p>
                    <p><strong>名前:</strong> {{ $admin->name }}</p>
                    <p><strong>メールアドレス:</strong> {{ $admin->email }}</p>
                    <p><strong>権限:</strong>{{ __('admin.roles.' . $admin->role) }}</p>
                    <div class="mt-2">
                        <a href="{{ route('admin.settings.admins.edit', ['admin' => $admin->id]) }}"
                            class="text-blue-600 hover:text-blue-800">
                            編集
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- ページネーション -->
        <div class="mt-4">
            {{ $admins->links() }}
        </div>
    </div>
@endsection
