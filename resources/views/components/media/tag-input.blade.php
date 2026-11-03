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
Tag input for the media library (#766): <x-form-tag-input> (#841) pointed
at the media tags, with the media library's length limit and texts.
--}}

@props([
    'id',
    'name' => 'tags[]',
    'selected' => [],
    'allowCreate' => true,
    'valueField' => 'name',
    'form' => null,
    'inline' => false,
    'label' => null,
    'placeholder' => null,
])

<x-form-tag-input
    :id="$id"
    :tagsUrl="route('admin.media.tags.list')"
    :name="$name"
    :selected="$selected"
    :allowCreate="$allowCreate"
    :valueField="$valueField"
    :form="$form"
    :inline="$inline"
    :label="$label"
    :placeholder="$placeholder ?? __('admin/media/tags.input.placeholder')"
    :maxlength="\App\Models\MediaTag::MAX_NAME_LENGTH"
    :createLabel="__('admin/media/tags.input.create', ['name' => ':name'])"
    :removeLabel="__('admin/media/tags.input.remove', ['name' => ':name'])"
    :noMatch="__('admin/media/tags.input.no_match')"
/>
