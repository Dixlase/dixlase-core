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
<div class="container mx-auto p-6">
    <!-- Flash message for success or error -->
    @include('components::flash_message')


    <!-- プラグイン一覧テーブル -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-md">
            <thead>
                <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                    <th class="py-3 px-6 text-left">ID</th>
                    <th class="py-3 px-6 text-left">プラグイン名</th>
                    <th class="py-3 px-6 text-center">状態</th>
                    <th class="py-3 px-6 text-center">操作</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 text-sm font-medium">
                @foreach ($plugins as $plugin)
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="py-3 px-6">{{ $plugin->id }}</td>
                        <td class="py-3 px-6">{{ $plugin->name }}</td>
                        <td class="py-3 px-6 text-center">
                            <span
                                class="inline-block px-3 py-1 rounded-full text-xs font-semibold
                                {{ $plugin->status === 'enabled' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}"
                            >
                                {{ $plugin->status === 1 ? '有効' : '無効' }}
                            </span>
                        </td>
                        <td class="py-3 px-6 text-center flex justify-center space-x-2">
                            @if ($plugin->status === 1)
                                <form action="{{ route('admin.settings.plugins.disable', $plugin->id) }}" method="POST">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="bg-yellow-500 hover:bg-yellow-600 text-white py-1 px-3 rounded-md text-sm"
                                    >
                                        無効化
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('admin.settings.plugins.enable', $plugin->id) }}" method="POST">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="bg-green-500 hover:bg-green-600 text-white py-1 px-3 rounded-md text-sm"
                                    >
                                        有効化
                                    </button>
                                </form>
                            @endif

                            <form action="{{ route('admin.settings.plugins.uninstall', $plugin->id) }}" method="POST">
                                @csrf
                                <button
                                    type="submit"
                                    class="bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded-md text-sm"
                                >
                                    アンインストール
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
