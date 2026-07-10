{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-admin.delete-button />

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
    'id_confirmation',
    'form' => null,
    'title' => null,
    'message' => null,
    'label' => null,
    'cancel_label' => null,
])

{{-- Same view-only suppression as x-admin.save-button: when CheckMenuAccess
     has shared menuEditable=false, the current user has view-only
     permission on the menu and a delete would 403. See save-button.blade
     for the shared-variable contract. --}}
@if($menuEditable ?? true)
    <!-- Delete button -->
    <x-form-button
        type="button"
        variant="danger"
        :label="$label ?? __('common.delete')"
        icon="fas fa-trash-alt"
        @click="openModal('{{ $id_confirmation }}')"
        class="delete-button"
    />

    <!-- Delete confirmation modal -->
    <x-ui-modal
        :id="$id_confirmation"
        :title="$title ?? __('common.delete_confirmation_title')"
        :message="$message ?? __('common.delete_confirmation_message')"
        :confirm_label="$label ?? __('common.delete')"
        :cancel_label="$cancel_label ?? __('common.cancel')"
        icon_type="danger"
        confirm_color="red"
        :form="$form ?? null"
    />
@endif
