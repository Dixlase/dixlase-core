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
@props([
    'require_password' => false,
])

<div class="mb-4">
    @include('components.form.label', [
        'for' => 'last_name',
        'text' => '姓',
    ])
    @include('components.form.text', [
        'id' => 'last_name',
        'name' => 'last_name',
        'value' => old('last_name', $user->last_name ?? ''),
        'required' => true,
        'theme' => $theme
    ])
    @include('components.form.error', [
        'messages' => $errors->get('last_name')
    ])
</div>

<div class="mb-4">
    @include('components.form.label', [
        'for' => 'first_name',
        'text' => '名',
    ])
    @include('components.form.text', [
        'id' => 'first_name',
        'name' => 'first_name',
        'value' => old('first_name', $user->first_name ?? ''),
        'required' => true,
        'theme' => $theme
    ])
    @include('components.form.error', [
        'messages' => $errors->get('first_name')
    ])
</div>

<div class="mb-4">
    @include('components.form.label', [
        'for' => 'email',
        'text' => 'メールアドレス',
    ])
    @include('components.form.text', [
        'type' => 'email',
        'id' => 'email',
        'name' => 'email',
        'value' => old('email', $user->email ?? ''),
        'required' => true,
        'theme' => $theme
    ])
    @include('components.form.error', [
        'messages' => $errors->get('email')
    ])
</div>

<div class="mb-4">
    @include('components.form.label', [
        'for' => 'password',
        'text' => 'パスワード',
    ])
    @include('components.form.text', [
        'type' => 'password',
        'id' => 'password',
        'name' => 'password',
        'value' => '',
        'required' => $require_password,
        'theme' => $theme
    ])
    @include('components.form.error', [
        'messages' => $errors->get('password')
    ])
</div>
