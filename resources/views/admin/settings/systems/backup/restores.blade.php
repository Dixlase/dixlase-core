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

@extends('layouts.admin')

@section('content')
    <section>
        <h2>{{ __('admin/settings/systems/backup.restores_heading') }}</h2>
        <p class="mb-4 text-gray-600 dark:text-gray-300">
            {{ __('admin/settings/systems/backup.restores_description') }}
        </p>

        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
            <p class="text-yellow-800 dark:text-yellow-200">
                <i class="fas fa-tools mr-2"></i>
                {{ __('admin/settings/systems/backup.placeholder') }}
            </p>
        </div>
    </section>
@endsection
