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

    <!-- Flash message for success or error -->
    @include('components.flash_message')

    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf

        <!-- フォーム -->
        @include('admin.users.partials.form', [
            'require_password' => true,
        ])

        <!-- 保存ボタンとモーダル -->
        @include('components.form.save', [
            'id' => 'confirmationModal',
            'onclick' => "openModal('confirmationModal')",
            'title' => '保存の確認',
            'label' => 'ユーザーを作成',
            'message' => 'この内容でユーザーを作成しますか？',
            'confirm_label' => '作成',
            'cancel_label' => '戻る',
        ])

    </form>
@endsection
