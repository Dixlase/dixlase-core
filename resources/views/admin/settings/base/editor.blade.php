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

@extends('layouts.admin')

@section('content')
<div class="mx-auto">
    @if($modeData['isGuideOnly'] ?? false)
        <x-admin.mode-guide-banner />
    @endif
<form id="base-editor-form" action="{{ route('admin.settings.base.editor.update') }}" method="POST">
    @csrf
    <fieldset {{ ($modeData['isGuideOnly'] ?? false) ? 'disabled' : '' }}>

    <!-- GUIエディター設定 -->
    <section>
        <h2>{{ __('admin/settings/base/editor.gui_editor_settings') }}</h2>

        <fieldset>
            <legend>{{ __('admin/settings/base/editor.preferred_gui_editor') }}</legend>

            @if(count($guiEditors) === 0)
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
                    <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-info-circle text-lg"></i>
                        <p class="text-sm">{{ __('admin/settings/base/editor.no_gui_editor_available') }}</p>
                    </div>
                </div>
            @else
                <x-form-select
                    name="preferred_gui_editor"
                    :options="$guiEditorOptions"
                    :value="old('preferred_gui_editor', $settings['preferred_gui_editor'])"
                />
            @endif

            <x-form-error field="preferred_gui_editor" />
            <p class="mt-2">{{ __('admin/settings/base/editor.preferred_gui_editor_help') }}</p>
        </fieldset>
    </section>

    </fieldset>
</form>
</div>
@endsection

@section('save')
@if($modeData['isEditable'] ?? true)
    <x-admin.save-button
        id_confirmation="confirmationModal"
        :label="__('common.save')"
        :title="__('common.save_confirmation_title')"
        :message="__('common.save_confirmation_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="base-editor-form"
    />
@endif
@endsection
