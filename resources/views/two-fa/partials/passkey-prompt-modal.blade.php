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
      (see LICENSE-COMMERCIAL, or contact info@dixlase.org).

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

@props([
    'modalId' => 'passkeyPromptModal',
    'hasRecoveryModal' => false,
])

<div x-data="passkeyPromptModal('{{ route('admin.profile.passkey-prompt.dismiss') }}', '{{ $modalId }}')">
    <x-ui-modal 
        :id="$modalId"
        :title="__('two-fa/passkey.prompt_modal.title')"
        message=""
        icon-type="info"
        :dismissible="true"
        data-passkey-prompt="true"
        :data-has-recovery-modal="$hasRecoveryModal ? 'true' : 'false'"
        @close="handleClose()">
        
        <div class="modal-message">
            <p>{{ __('two-fa/passkey.prompt_modal.message') }}</p>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <x-form-toggle
                name="dont_show_again"
                :label="__('two-fa/passkey.prompt_modal.dont_show_again')"
                x-model="dontShowAgain"
            />
        </div>
        
        <x-slot name="footer">
            <x-form-button
                type="button"
                variant="secondary"
                :label="__('common.close')"
                @click="closeModal()"
                class="mx-2"
            />
            <x-form-button
                type="link"
                variant="primary"
                :label="__('two-fa/passkey.prompt_modal.register_now')"
                :href="route('admin.profile.two-fa-management')"
                icon="fas fa-key"
                class="mx-2"
            />
        </x-slot>
    </x-ui-modal>
</div>
