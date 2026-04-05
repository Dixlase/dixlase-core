{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-ui-livewire-notification />

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

{{-- Livewire Notification Container (Alpine.js不要、CSP厳格モード対応) --}}
{{-- JavaScriptは resources/src/common/js/livewire-notification.js に外部化 --}}
<div id="livewire-notification-container" style="display: none;" role="alert">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <i id="livewire-notification-icon" class="text-xl"></i>
        </div>
        <div class="ml-3 flex-1">
            <p id="livewire-notification-message" class="text-sm font-medium"></p>
        </div>
        <div class="ml-auto pl-3">
            <button id="livewire-notification-close" type="button">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</div>
