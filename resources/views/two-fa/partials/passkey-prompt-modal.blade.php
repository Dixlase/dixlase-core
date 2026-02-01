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

@props([
    'modalId' => 'passkeyPromptModal',
    'hasRecoveryModal' => false,
])

@php
    $passkeyPromptMessage = '<p class="mb-4">' . __('admin/profile/two-fa.passkey_prompt_message') . '</p>' .
        '<p class="text-sm text-gray-600 dark:text-gray-400">' . __('admin/profile/two-fa.passkey_prompt_description') . '</p>';
@endphp

<x-ui.modal 
    :id="$modalId"
    :title="__('admin/profile/two-fa.passkey_prompt_title')"
    :message="$passkeyPromptMessage"
    icon-type="info"
    :dismissible="true"
    data-passkey-prompt="true"
    :data-has-recovery-modal="$hasRecoveryModal ? 'true' : 'false'">
    
    <x-slot name="footer">
        <x-form.button
            type="button"
            variant="secondary"
            :label="__('common.later')"
            @click="close()"
            class="mx-2"
        />
        <x-form.button
            type="link"
            variant="primary"
            :label="__('admin/profile/two-fa.go_to_passkey_registration')"
            :href="route('admin.profile.two-fa-management')"
            icon="fas fa-key"
            class="mx-2"
        />
    </x-slot>
</x-ui.modal>
