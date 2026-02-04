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
<div class="fixed top-0 left-0 right-0 z-[60] bg-red-600 dark:bg-red-700 text-white shadow-lg" role="alert" id="csp-safe-mode-banner">
    <div class="container mx-auto px-4 py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <i class="fas fa-exclamation-triangle text-2xl"></i>
                <div>
                    <p class="font-bold text-lg">
                        {{ __('admin/settings/security/csp.safe_mode_banner_title') }}
                    </p>
                    <p class="text-sm">
                        {{ __('admin/settings/security/csp.safe_mode_banner_message') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="{{ route('admin.settings.security.csp') }}" 
                   class="px-4 py-2 bg-white text-red-600 dark:bg-gray-800 dark:text-red-400 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    {{ __('admin/settings/security/csp.safe_mode_go_to_settings') }}
                </a>
                <form method="POST" action="{{ route('admin.settings.security.csp.disable-safe-mode') }}" class="inline">
                    @csrf
                    <button type="submit" 
                            class="px-4 py-2 bg-red-800 dark:bg-red-900 text-white rounded hover:bg-red-900 dark:hover:bg-red-950 transition">
                        {{ __('admin/settings/security/csp.safe_mode_disable') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
