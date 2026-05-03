{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-ui-notification />

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

{{-- Alpine.js Notification Container --}}
<div x-data x-show="$store.notification.show" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform translate-y-2"
     x-transition:enter-end="opacity-100 transform translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     :class="$store.notification.containerClass"
     style="display: none;"
     role="alert">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <i :class="$store.notification.iconClass" class="text-xl"></i>
        </div>
        <div class="ml-3 flex-1">
            <p class="text-sm font-medium" x-text="$store.notification.message"></p>
        </div>
        <div class="ml-auto pl-3">
            <button @click="$store.notification.hide()" 
                    :class="$store.notification.closeButtonClass"
                    type="button">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</div>
