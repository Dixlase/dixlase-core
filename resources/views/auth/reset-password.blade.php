{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
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
    'layout' => 'layouts.auth',
    'title',
    'header',
    'description',
    'route',
    'token',
    'email',
    'emailLabel',
    'passwordLabel',
    'submitText',
    'passwordMinLength' => 8,
    'passwordRequireUppercase' => false,
    'passwordRequireNumber' => false,
    'passwordRequireSymbol' => false,
])

@extends($layout)
@section('title', $title)
@section('header', $header)
@section('description', $description)

@section('content')
    <x-auth.reset-password
        :route="$route"
        :token="$token"
        :email="$email"
        :emailLabel="$emailLabel"
        :passwordLabel="$passwordLabel"
        :submitText="$submitText"
        :passwordMinLength="$passwordMinLength"
        :passwordRequireUppercase="$passwordRequireUppercase"
        :passwordRequireNumber="$passwordRequireNumber"
        :passwordRequireSymbol="$passwordRequireSymbol"
    />
@endsection
