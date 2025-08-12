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

    <div class="mb-4">
        <label class="mr-2 dark:text-gray-200">{{ __('admin.settings.systems.logs.log_type_label') }}</label>
        @foreach ($logTypes as $type)
            <a href="{{ route('admin.settings.systems.logs', ['type' => $type]) }}"
                class="inline-block px-2 py-1 rounded mr-2 text-white {{ $logType === $type ? 'bg-blue-500' : 'bg-gray-500' }}
                dark:{{ $logType === $type ? 'bg-blue-700' : 'bg-gray-700' }}">
                {{ __('admin.settings.systems.logs.' . $type) }}
            </a>
        @endforeach
    </div>

    <div class="w-full overflow-x-auto">
        <div class="min-w-full bg-white border rounded font-mono text-sm
        h-[600px] overflow-y-scroll
        dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100">

        @forelse ($logs as $line)
            <div class="border-b py-1 px-2 break-all whitespace-normal dark:border-gray-700">
                {{ $line }}
            </div>
        @empty
            <p class="p-2 dark:text-gray-300">{{ __('admin.settings.systems.logs.no_logs_found') }}</p>
        @endforelse

        </div>
    </div>
@endsection
