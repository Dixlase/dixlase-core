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

{{-- Set default values for partial variables --}}
@php
    $modalId = $modalId ?? 'passkeyDeviceNameModal';
    $title = $title ?? null;
    $message = $message ?? null;
    $inputLabel = $inputLabel ?? null;
    $inputPlaceholder = $inputPlaceholder ?? '';
    $confirmLabel = $confirmLabel ?? null;
    $cancelLabel = $cancelLabel ?? null;
@endphp

<div id="{{ $modalId }}" 
     class="modal" 
     x-data="passkeyDeviceNameModal()" 
     x-show="show" 
     x-cloak
     @keydown.enter.window="handleEnter($event)"
     @keydown.escape.window="cancel()"
     style="display: none;">
    <div class="modal-overlay bg-white/80 dark:bg-black/50" @click="cancel()"></div>
    <div class="modal-container" @click.stop style="transition: transform 300ms ease-out, opacity 300ms ease-out;">
        <div class="modal-content">
            <div class="modal-icon modal-icon--info">
                <i class="fas fa-fingerprint" aria-hidden="true"></i>
            </div>
            
            <div class="modal-body">
                <h2 class="modal-title">{{ $title ?? __('two-fa/passkey.device_name_title') }}</h2>
                <div class="modal-message">
                    <p>{{ $message ?? __('two-fa/passkey.device_name_message') }}</p>
                </div>

                <div class="mt-4">
                    <label for="{{ $modalId }}_input" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ $inputLabel ?? __('two-fa/passkey.device_name_label') }}
                    </label>
                    <input 
                        type="text" 
                        id="{{ $modalId }}_input"
                        x-model="deviceName"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                        placeholder="{{ $inputPlaceholder }}"
                        maxlength="255"
                    />
                </div>
            </div>
        </div>
        
        <div class="modal-actions">
            <x-form-button
                type="button"
                variant="secondary"
                :label="$cancelLabel ?? __('common.cancel')"
                @click="cancel()"
                class="mx-2"
            />
            <x-form-button
                type="button"
                variant="primary"
                :label="$confirmLabel ?? __('common.ok')"
                @click="confirm()"
                class="mx-2"
            />
        </div>
    </div>
</div>

