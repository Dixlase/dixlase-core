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

@props([
    'require_password' => false,
])

<div class="mb-4">
    @include('components::form.label', [
        'for' => 'name',
        'text' => '名前',
    ])
    @include('components::form.text', [
        'id' => 'name',
        'name' => 'name',
        'value' => old('name', $member->name ?? ''),
        'required' => true,
    ])
    @include('components::form.error', [
        'messages' => $errors->get('name')
    ])
</div>



<div class="mb-4">
    @include('components::form.label', [
        'for' => 'email',
        'text' => 'メールアドレス',
    ])
    @include('components::form.text', [
        'type' => 'email',
        'id' => 'email',
        'name' => 'email',
        'value' => old('email', $member->email ?? ''),
        'required' => true,
    ])
    @include('components::form.error', [
        'messages' => $errors->get('email')
    ])
</div>

<div class="mb-4">
    @include('components::form.label', [
        'for' => 'password',
        'text' => 'パスワード',
    ])
    @include('components::form.password-tools', [
        'id' => 'password',
        'name' => 'password',
        'required' => $requirePassword,
    ])
    @include('components::form.error', [
        'messages' => $errors->get('password')
    ])
</div>


<div class="mb-4">
    @include('components::form.label', [
        'for' => 'role',
        'text' => config('admin.role'),

    ])


    @include('components::form.select', [
        'id' => 'role',
        'name' => 'role',
        'options' => $roleOptions,
        'value' => $roleValue,
        'required' => true,
    ])
</div>

<div class="mb-4">
    @include('components::form.label', [
        'for' => 'appearance',
        'text' => '外観モード',

    ])
    @include('components::form.select', [
        'id' => 'appearance',
        'name' => 'appearance',
        'options' => config('admin.appearance'),
        'value' => old('appearance', $member->appearance ?? ''),
        'required' => true,
    ])
</div>

<div class="mb-4">
    @include('components::form.label', [
        'for' => 'status',
        'text' => 'ステータス',

    ])
    @include('components::form.select', [
        'id' => 'status',
        'name' => 'status',
        'options' => $statusOptions,
        'value' => $statusValue,
        'required' => true,
    ])
</div>
