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
    <h1>プラグイン管理</h1>
    <form action="{{ route('admin.settings.plugins.install') }}" method="POST">
        @csrf
        <input type="text" name="name" placeholder="プラグイン名">
        <button type="submit">インストール</button>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>プラグイン名</th>
                <th>状態</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($plugins as $plugin)
            <tr>
                <td>{{ $plugin->id }}</td>
                <td>{{ $plugin->name }}</td>
                <td>{{ $plugin->status }}</td>
                <td>
                    @if ($plugin->status === 'enabled')
                    <form action="{{ route('admin.settings.plugins.disable', $plugin->id) }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit">無効化</button>
                    </form>
                    @else
                    <form action="{{ route('admin.settings.plugins.enable', $plugin->id) }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit">有効化</button>
                    </form>
                    @endif
                    <form action="{{ route('admin.settings.plugins.uninstall', $plugin->id) }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit">アンインストール</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    </div>
@endsection
