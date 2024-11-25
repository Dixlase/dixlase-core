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
    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf

        <!-- フォーム -->
        @include('admin.users.partials.form', ['theme' => $theme])

        <!-- 保存ボタン -->
        @include('components.form.button', [
            'type' => 'button',
            'label' => 'ユーザーを作成',
            'onclick' => "openModal('confirmationModal')",
            'theme' => $theme,
        ])

        <!-- モーダル -->
        @include('components.form.modal', [
            'id' => 'confirmationModal',
            'title' => 'ユーザー作成の確認',
            'message' => 'この内容でユーザーを作成しますか？',
            'cancelText' => '戻る',
            'theme' => $theme
        ])

    </form>
</x-admin-layout>
