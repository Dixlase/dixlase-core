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

<!-- 削除ボタン -->
<x-form-button
    type="button"
    variant="danger"
    :label="$label ?? __('common.delete')"
    icon="fas fa-trash-alt"
    onclick="openModal('{{ $id_confirmation }}')"
    class="delete-button"
/>

<!-- 削除モーダル -->
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
