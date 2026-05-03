{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact office@exc-d.com).

Unless you have entered into a commercial license agreement, this
file is governed by the AGPL terms below.

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

<section>
    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
        <i class="fas fa-server mr-2"></i>{{ __('admin/dashboard.system_info') }}
    </h2>

    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <tbody>
                @foreach($systemInfo as $info)
                    <tr class="border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 w-1/3">
                            {{ $info['label'] }}
                        </th>
                        <td class="px-4 py-3 text-gray-900 dark:text-gray-100">
                            {{ $info['value'] }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
