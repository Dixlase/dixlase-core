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

@extends('layouts.admin')

@section('content')
     <div class="max-w-5xl mx-auto px-4 py-8">
        @foreach ($info as $category => $items)
            <div class="bg-white dark:bg-gray-800 shadow rounded-2xl mb-6">
                <div class="bg-gray-100 dark:bg-gray-700 px-4 py-3 rounded-t-2xl border-b border-gray-200 dark:border-gray-600">
                    <h2 class="text-lg font-semibold capitalize text-gray-800 dark:text-white">{{ $category }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left border-collapse">
                        <tbody>
                            @foreach ($items as $key => $value)
                                <tr class="border-b border-gray-200 dark:border-gray-700 transition">
                                    <th class="w-1/3 px-4 py-2 font-medium text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-800">
                                        {{ $key }}
                                    </th>
                                    <td class="px-4 py-2 text-gray-800 dark:text-gray-100 break-all">
                                        {{ is_bool($value) ? ($value ? 'true' : 'false') : $value }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>

@endsection
