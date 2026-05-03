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

{{-- パーシャル用変数のデフォルト値設定 --}}
@php
    $modalId = $modalId ?? 'passkeyResultModal';
@endphp

<div id="{{ $modalId }}" 
     class="modal" 
     x-data="passkeyResultModal()" 
     x-show="show" 
     x-cloak
     @keydown.escape.window="close()"
     style="display: none;">
    <div class="modal-overlay bg-white/80 dark:bg-black/50" @click="close()"></div>
    <div class="modal-container" @click.stop style="transition: transform 300ms ease-out, opacity 300ms ease-out;">
        <div class="modal-content">
            <div :class="modalIconClass">
                <i :class="iconClass" aria-hidden="true"></i>
            </div>
            
            <div class="modal-body">
                <h2 class="modal-title" x-text="title"></h2>
                <div class="modal-message">
                    <p x-text="message"></p>
                </div>
            </div>
        </div>
        
        <div class="modal-actions">
            <x-form-button
                type="button"
                variant="primary"
                :label="__('common.close')"
                @click="close()"
                class="mx-2"
            />
        </div>
    </div>
</div>

