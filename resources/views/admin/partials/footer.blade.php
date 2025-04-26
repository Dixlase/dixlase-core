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

<footer id="page-footer" class="border-t py-4 text-center text-xs bg-gray-100 text-gray-500 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700">
    <span class="font-semibold">{{ config('app.name', 'MySoftware') }}</span>
    v{{ config('app.version', '1.0.0') }} &middot;
    &copy; {{ date('Y') }} exc-D inc. &middot;
    <a href="https://www.gnu.org/licenses/agpl-3.0.html" target="_blank" class="underline hover:text-gray-700 dark:hover:text-gray-300">
        AGPLv3
    </a>
</footer>