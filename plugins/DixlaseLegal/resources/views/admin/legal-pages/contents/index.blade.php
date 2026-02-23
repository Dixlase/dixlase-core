{{--
This file is part of Dixlase Legal.

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
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {{ __('dixlase-legal::admin/legal-pages/contents.table_page_type') }}
                    </th>
                    @foreach ($languages as $langCode => $langName)
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ $langName }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($matrix as $slug => $row)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                    <i class="{{ $row['icon'] }} text-sm text-gray-600 dark:text-gray-400"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $row['name'] }}</span>
                            </div>
                        </td>
                        @foreach ($row['languages'] as $langCode => $langData)
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($langData['exists'])
                                    <div class="flex items-center gap-2">
                                        <x-ui-status-badge
                                            :label="$langData['status_label']"
                                            :variant="$langData['status_css']"
                                            size="xs"
                                        />
                                        <a href="{{ route('dixlase-legal::admin.legal-pages.contents.edit', ['slug' => $slug, 'lang' => $langCode]) }}"
                                           class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                            {{ __('dixlase-legal::admin/legal-pages/contents.edit_link') }}
                                        </a>
                                    </div>
                                @else
                                    <a href="{{ route('dixlase-legal::admin.legal-pages.contents.edit', ['slug' => $slug, 'lang' => $langCode]) }}"
                                       class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400">
                                        <i class="fas fa-plus text-xs"></i>
                                        {{ __('dixlase-legal::admin/legal-pages/contents.create_link') }}
                                    </a>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
