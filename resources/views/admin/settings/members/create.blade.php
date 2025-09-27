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
        'requirePassword' => true,
        'passwordMinLength' => $passwordMinLength,
        'passwordRequireUppercase' => $passwordRequireUppercase,
        'passwordRequireSymbol' => $passwordRequireSymbol,
        'roles' => $roles,
        'twoFactorMode' => $twoFactorMode,
        'enabledTwoFactorMethods' => $enabledTwoFactorMethods,
        'defaultTwoFactorMethod' => $defaultTwoFactorMethod,
        'isInitialAdmin' => false,
        'isMailServerTested' => $isMailServerTested,
        'formAction' => route('admin.settings.members.store'),
        'formMethod' => 'POST',
        'formId' => 'create-form',
        'includeForm' => true
    ])
@endsection

@section('save')
    <!-- {{ __('admin.settings.members.create.create_confirmation_title') }} -->
    @include('components.save', [
        'id' => 'confirmationModal',
        'label' => __('common.create'),
        'onclick' => "openModal('confirmationModal')",
        'title' => __('admin.settings.members.create.create_confirmation_title'),
        'message' => __('admin.settings.members.create.create_confirmation_message'),
        'confirm_label' => __('common.create'),
        'cancel_label' => __('common.back'),
        'form' => 'create-form', // 🔁 保存ボタンに form 属性を渡す（必要なら）
    ])
@endsection


