{{--
This file is part of MySoftware.

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
    'type' => 'button',      // Button type (button, submit, reset)
    'disabled' => false,     // Disable button
    'class' => '',                          // Custom class
    'label' => __('common.form.save_button'),                    // Button text
    'id' => 'confirmationModal',            // Modal ID
    'title' => __('common.form.save_confirmation_title'),                  // Modal title
    'message' => __('common.form.save_confirmation_message'),    // Modal message
    'confirm_label' => __('common.form.save_button'),                  // Confirm button text
    'cancel_label' => __('common.form.cancel_button'),                  // Cancel button text
    'form' => null,                          // Form ID
    'id_confirmation' => 'confirmationModal',        // Modal ID
    'id_delete' => 'deleteModal',                  // Delete modal ID


])

<!-- {{ __('common.form.save_button') }} -->
@include('components::form.button', [
    'type' => $type,
    'label' => $label,
    'disabled' => $disabled,
    'onclick' => "openModal('" . $id_confirmation . "')",
    'form' => $form,
])

<!-- {{ __('common.form.save_confirmation_title') }} -->
@push('modals')
    @include('components::form.modal', [
    'id' => $id_confirmation,
    'title' => $title,
    'message' => $message,
    'confirm_label' => $label,
    'cancel_label' => $cancel_label,
    'form' => $form,
])
@endpush



