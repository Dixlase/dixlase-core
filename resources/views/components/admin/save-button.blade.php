{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

@api Available for plugins/themes as <x-admin.save-button />

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
    'type' => 'button',      // Button type (button, submit, reset)
    'disabled' => false,     // Disable button
    'class' => '',                          // Custom class
    'label' => __('common.save'),                    // Button text
    'id' => 'confirmationModal',            // Modal ID
    'title' => __('common.save_confirmation_title'),                  // Modal title
    'message' => __('common.save_confirmation_message'),    // Modal message
    'confirm_label' => __('common.save'),                  // Confirm button text
    'cancel_label' => __('common.cancel'),                  // Cancel button text
    'form' => null,                          // Form ID
    'id_confirmation' => 'confirmationModal',        // Modal ID
    'id_delete' => 'deleteModal',                  // Delete modal ID
    'onclick' => null,                       // Custom onclick function (optional)
    'back_url' => null,                      // Back button URL (optional)
    'back_label' => __('common.back'),       // Back button text
    'guard' => true,                         // Ask before leaving the form with unsaved changes
])

{{-- When the CheckMenuAccess middleware has shared menuEditable=false
     for this request, the current user has view-only permission and
     any save would 403 server-side. The button stays in the layout
     (so the sticky footer height does not shift between menus) but is
     rendered as HTML-`disabled` + dimmed via the base x-form-button
     `disabled:opacity-50 disabled:cursor-not-allowed` styles, and
     carries a `title` tooltip explaining why. Disabled <button>s do
     not fire click events, so the Alpine @click handler and the
     confirmation modal below never trigger.
     Undefined / true → render as before (backwards compatible for any
     view not behind CheckMenuAccess). --}}
@php
    $viewOnly = ! ($menuEditable ?? true);
    $tooltipText = $viewOnly ? __('common.view_only_action_disabled') : '';
    // The form this button saves is watched for unsaved changes
    // (resources/src/admin/js/unsaved-changes-guard.js). A page that came
    // back with validation errors holds input that was never saved.
    $guardForm = $guard && $form && ! $viewOnly ? $form : null;
    $startsDirty = $guardForm !== null && isset($errors) && $errors->any();
@endphp
<div class="flex justify-between items-center"
    @if ($guardForm) data-unsaved-changes-form="{{ $guardForm }}" @endif
    @if ($startsDirty) data-unsaved-changes-dirty="1" @endif
>

    @if($back_url)
        <!-- {{ __('common.back') }} -->
        <x-form-button
            type="link"
            variant="tertiary"
            :label="$back_label ?? __('common.back')"
            icon="fas fa-arrow-left"
            :href="$back_url"
            class="mx-2"
        />
    @endif
    <x-form-button
        type="button"
        variant="primary"
        :label="$label ?? __('common.save')"
        icon="fas fa-save"
        :disabled="$viewOnly"
        :title="$tooltipText"
        @click="openModal('{{ $id_confirmation }}')"
        class="save-button mx-2"
    />


</div>

<!-- {{ __('common.save_confirmation_title') }} -->
@push('modals')
    <x-ui-modal
        :id="$id_confirmation"
        :title="$title ?? __('common.save_confirmation_title')"
        :message="$message ?? __('common.save_confirmation_message')"
        :confirm_label="$confirm_label ?? __('common.save')"
        :cancel_label="$cancel_label ?? __('common.cancel')"
        :form="$form ?? null"
    />
@endpush
