{{--
This file is part of Dixlase.

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

@if(session('csp_safe_mode'))
<div class="fixed top-0 left-0 right-0 z-[10000] bg-red-600 dark:bg-red-700 text-white px-4 py-3 shadow-md" role="alert" id="csp-safe-mode-banner">
    <div class="max-w-full mx-auto flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <i class="fas fa-exclamation-triangle text-xl"></i>
            <div>
                <div class="font-semibold">
                    {{ __('admin/settings/security/csp.safe_mode_banner_title') }}
                </div>
                <div class="text-sm opacity-90">
                    {{ __('admin/settings/security/csp.safe_mode_banner_message') }}
                </div>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('admin.settings.security.csp') }}" 
               class="px-4 py-2 bg-white text-red-600 dark:text-red-700 rounded hover:bg-gray-100 transition text-sm font-medium whitespace-nowrap">
                {{ __('admin/settings/security/csp.safe_mode_go_to_settings') }}
            </a>
            <form method="POST" action="{{ route('admin.settings.security.csp.disable-safe-mode') }}" class="inline">
                @csrf
                <button type="submit" 
                        class="px-4 py-2 bg-red-800 dark:bg-red-900 text-white rounded hover:bg-red-900 dark:hover:bg-red-950 transition text-sm font-medium whitespace-nowrap">
                    {{ __('admin/settings/security/csp.safe_mode_disable') }}
                </button>
            </form>
        </div>
    </div>
</div>

@endif
