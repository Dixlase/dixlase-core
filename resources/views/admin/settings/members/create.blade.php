{{--
This file is part of MySoftware.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

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

    <form action="{{ route('admin.settings.members.store') }}" method="POST" class="mt-6">
        @csrf
        @include('admin::settings.members.partials.form',[
            'require_password' => true,
        ])

        <!-- 保存ボタンとモーダル -->
        @include('components::form.save', [
            'id' => 'confirmationModal',
            'label' => '作成',
            'onclick' => "openModal('confirmationModal')",
            'title' => '作成の確認',
            'message' => '新規管理者を作成しますか？',
            'confirm_label' => '作成',
            'cancel_label' => '戻る',
        ])

    </form>
@endsection
