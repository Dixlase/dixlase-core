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

<div class="mb-4">
    @include('components.form.label', [
        'for' => 'name',
        'text' => '名前',
    ])
    @include('components.form.text', [
        'id' => 'name',
        'name' => 'name',
        'value' => old('name', $admin->name ?? ''),
        'required' => true,
        'theme' => $theme
    ])
    @include('components.form.error', [
        'messages' => $errors->get('name')
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
        'value' => old('email', $admin->email ?? ''),
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
        'required' => true,
        'theme' => $theme
    ])
    @include('components.form.error', [
        'messages' => $errors->get('password')
    ])
</div>


<div class="mb-4">
    @include('components.form.label', [
        'for' => 'admin_theme',
        'text' => '権限',
    ])

    @include('components.form.select', [
        'id' => 'role',
        'name' => 'role',
        'options' => config('admin.roles'),
        'class' => config('admin.theme_class.' . $theme . '.form_input_text'),
        'required' => true,
        'theme' => $theme
    ])

</div>
