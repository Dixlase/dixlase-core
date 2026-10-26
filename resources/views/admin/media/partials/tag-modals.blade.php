{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

{{--
Modals of the library's tags (#766): bulk add and bulk remove for the
selection mode. Renaming and deleting live on the tag page. mediaIndex
(resources/src/admin/media/js/index.js) fills the forms and opens them.
Rendered only for members who can edit media.
--}}

<!-- Bulk add -->
<x-ui-modal
    id="bulkTagAddModal"
    :title="__('admin/media/tags.bulk.add_title')"
    message=""
    :confirm_label="__('admin/media/tags.bulk.apply')"
    :cancel_label="__('common.cancel')"
    icon_type="info"
    form="bulkTagAddForm"
>
    <p class="modal-message mb-3" data-bulk-tag-message="{{ __('admin/media/tags.bulk.add_message', ['count' => ':count']) }}"></p>
    <div class="text-left">
        <x-media.tag-input id="bulkTagAddInput" name="add[]" form="bulkTagAddForm" :inline="true" />
    </div>
</x-ui-modal>
<form id="bulkTagAddForm" action="{{ route('admin.media.tags.bulk') }}" method="POST" style="display: none;">
    @csrf
</form>

@if($tags->isNotEmpty())
    <!-- Bulk remove -->
    <x-ui-modal
        id="bulkTagRemoveModal"
        :title="__('admin/media/tags.bulk.remove_title')"
        message=""
        :confirm_label="__('admin/media/tags.bulk.apply')"
        :cancel_label="__('common.cancel')"
        icon_type="warning"
        form="bulkTagRemoveForm"
    >
        <p class="modal-message mb-3" data-bulk-tag-message="{{ __('admin/media/tags.bulk.remove_message', ['count' => ':count']) }}"></p>
        <div class="text-left">
            <x-media.tag-input id="bulkTagRemoveInput" name="remove[]" form="bulkTagRemoveForm" :allowCreate="false" valueField="id" :inline="true" />
        </div>
    </x-ui-modal>
    <form id="bulkTagRemoveForm" action="{{ route('admin.media.tags.bulk') }}" method="POST" style="display: none;">
        @csrf
    </form>
@endif
