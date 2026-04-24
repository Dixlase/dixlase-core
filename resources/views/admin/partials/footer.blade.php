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

<footer id="page-footer" class="lg:ml-64 border-t py-4 text-center text-xs bg-white text-gray-500 border-gray-200 dark:bg-black dark:text-gray-400 dark:border-gray-700">
    <span class="font-semibold">{{ config('app.software_name', 'Dixlase') }}</span>
    v{{ config('app.version', '1.0.0') }} &middot;
    &copy; {{ date('Y') }} exc-D inc. &middot;
    <a href="{{ config('dixlase.license_url', 'https://www.gnu.org/licenses/agpl-3.0.html') }}" target="_blank" rel="noopener" class="underline hover:text-gray-700 dark:hover:text-gray-300">
        {{ config('dixlase.license_label', 'AGPLv3') }}
    </a> &middot;
    <a href="{{ config('dixlase.source_url', 'https://github.com/Dixlase/dixlase-core') }}" target="_blank" rel="noopener" class="underline hover:text-gray-700 dark:hover:text-gray-300" title="{{ __('common.source_code_title') }}">
        {{ __('common.source_code') }}
    </a>
</footer>