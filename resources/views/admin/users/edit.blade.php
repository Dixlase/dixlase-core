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

    <form action="{{ route('admin.users.update', ['user' => $user->id] ) }}" method="POST">
        @csrf
        @method('PATCH')
        @include('components::form.hidden', [
            'name' => 'id',
            'value' => $user->id,
        ])

        <!-- Form -->
        @include('admin::users.partials.form', [
            'require_password' => false,
        ])

        <!-- 保存ボタンとモーダル -->
        @include('components::form.save', [
            'onclick' => "openModal('confirmationModal')",
            'title' => '更新の確認',
            'label' => 'ユーザーを更新',
            'message' => 'この内容でユーザー情報を更新しますか？',
            'confirm_label' => '更新',
            'cancel_label' => '戻る',
        ])

    </form>

    <!-- 削除ボタン -->
    <form action="{{ route('admin.users.destroy', ['user' => $user->id] ) }}" method="POST">
        @csrf
        @method('DELETE')
        @include('components::form.button', [
            'type' => 'button',
            'label' => 'ユーザーを削除',
            'class' => 'text-white bg-red-700 hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900',
            'onclick' => "openModal('deleteModal')",
        ])

        <!-- 削除モーダル -->
        @include('components::form.modal', [
            'id' => 'deleteModal',
            'title' => '削除の確認',
            'message' => 'このユーザーを削除しますか？',
            'confirm_label' => '削除',
            'cancel_label' => 'キャンセル',
        ])
    </form>

@endsection
