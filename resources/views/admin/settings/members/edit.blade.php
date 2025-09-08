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
    <form id="update-form" action="{{ route('admin.settings.members.update', ['member' => $member->id]) }}" method="POST" class="mb-48">
        @csrf
        @method('PATCH')
        @include('components::form.hidden', [
            'name' => 'id',
            'value' => $member->id,
        ])

        <!-- フォーム -->
        @include('admin::settings.members.partials.form',[
            'require_password' => false,
        ])
    </form>

        <!-- 削除用フォーム（ボタンは下部バーに出す） -->
    <form id="delete-form" action="{{ route('admin.settings.members.destroy', ['member' => $member->id]) }}" method="POST">
        @csrf
        @method('DELETE')
    </form>

    <!-- 強制ログアウト用フォーム -->
    <form id="force-logout-form" action="{{ route('admin.settings.members.force-logout', ['member' => $member->id]) }}" method="POST">
        @csrf
    </form>
@endsection

@section('save')
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <!-- 保存ボタン -->
        @include('components::form.button', [
            'type' => 'button',
            'label' => '更新',
            'class' => '',
            'onclick' => "openModal('confirmationModal')"
        ])
    </div>
@endsection

@section('modals')
    <!-- 保存モーダル -->
    @include('components::form.modal', [
        'id' => 'confirmationModal',
        'title' => '更新の確認',
        'message' => 'この内容でメンバー情報を更新しますか？',
        'confirm_label' => '更新',
        'cancel_label' => 'キャンセル',
        'form' => 'update-form',
    ])


@endsection

@section('scripts')
    document.addEventListener('DOMContentLoaded', function () {
        const saveModal = document.getElementById('confirmationModal');
        const forceLogoutModal = document.getElementById('forceLogoutModal');
        const deleteModal = document.getElementById('deleteModal');

        saveModal.querySelector('button[type="submit"]').addEventListener('click', () => {
            document.getElementById('update-form').submit();
        });

        forceLogoutModal.querySelector('button[type="submit"]').addEventListener('click', () => {
            document.getElementById('force-logout-form').submit();
        });

        deleteModal.querySelector('button[type="submit"]').addEventListener('click', () => {
            document.getElementById('delete-form').submit();
        });
    });
@endsection

