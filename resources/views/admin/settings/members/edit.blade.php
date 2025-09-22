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
    @include('admin.settings.members.partials.members-form', [
        'member' => $member,
        'requirePassword' => false,
        'passwordMinLength' => $passwordMinLength,
        'passwordRequireUppercase' => $passwordRequireUppercase,
        'passwordRequireSymbol' => $passwordRequireSymbol,
        'roles' => $roles,
        'twoFactorMode' => $twoFactorMode,
        'enabledTwoFactorMethods' => $enabledTwoFactorMethods,
        'defaultTwoFactorMethod' => $defaultTwoFactorMethod,
        'isInitialAdmin' => $isInitialAdmin,
        'isMailServerTested' => $isMailServerTested,
        'formAction' => route('admin.settings.members.update', ['member' => $member->id]),
        'formMethod' => 'PATCH',
        'formId' => 'update-form',
        'includeForm' => true
    ])
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

