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

<x-admin-layout :title="__($title)">
    <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="shadow-md rounded p-6">
            <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- フォーム -->
                @include('admin.users.partials.form', ['theme' => $theme])

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
        </div>
    </div>
</x-admin-layout>
