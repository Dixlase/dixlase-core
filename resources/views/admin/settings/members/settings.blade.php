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

    <form method="POST" action="{{ route('admin.settings.members.settings.update') }}">
        @csrf

        <div class="mb-6">
            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">
                {{ __('admin.settings.members.two_factor_mode.label') }}
            </label>

            @php
                $twoFactorOptions = collect(config('admin.global_two_factor_mode'))
                    ->mapWithKeys(fn ($value) => [$value => __('admin.settings.members.two_factor_mode.options_global.' . $value)])
                    ->toArray();
            @endphp


            @include('components.form.radio-group', [
                'name' => 'force_2fa',
                'options' => $twoFactorOptions,
                'value' => old('force_2fa', (string) $force2fa),
            ])

        </div>

        <div>
            <button type="submit"
                    class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                {{ __('admin.settings.members.settings.submit') }}
            </button>
        </div>
    </form>
@endsection
