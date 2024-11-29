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

@extends('admin.partials.layout')

@section('content')
    <form action="{{ route('admin.users.update', $admin->id) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- フォーム -->
        @include('admin.settings.admins.partials.form')

        <!-- 保存ボタン -->
        @include('components.form.button', [
            'type' => 'button',
            'label' => 'ユーザーを更新',
            'onclick' => "openModal('confirmationModal')",
            'theme' => $theme,
        ])

        <!-- モーダル -->
        @include('components.form.modal', [
            'id' => 'confirmationModal',
            'title' => 'ユーザー情報更新の確認',
            'message' => 'この内容でユーザー情報を更新しますか？',
            'cancelText' => '戻る',
            'theme' => $theme
        ])

    </form>
@endsection
