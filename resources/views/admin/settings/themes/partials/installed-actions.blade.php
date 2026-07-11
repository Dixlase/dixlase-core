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

@php
    // See plugins/partials/installed-actions.blade.php for the
    // CheckMenuAccess contract. Server-side check.menu.edit still
    // guards the theme switch / uninstall POST routes.
    $viewOnly = ! ($menuEditable ?? true);
    $tooltipText = $viewOnly ? __('common.view_only_action_disabled') : '';
@endphp

@if($card['isEnabled'])
    {{-- Active theme: settings button only — link, stays enabled for
         view-only users because the settings page is view only for
         them anyway. --}}
    @if($card['hasSettings'] && Route::has('admin.settings.themes.settings'))
        <a href="{{ route('admin.settings.themes.settings') }}" class="inline-block">
            <x-form-button
                type="button"
                :label="__('common.settings')"
                variant="primary"
                size="xs"
                class="py-2 px-3"
                icon="fas fa-cog"
            />
        </a>
    @endif
@else
    {{-- Inactive: activate and uninstall buttons --}}
    <form action="{{ route('admin.settings.themes.switch', $card['id']) }}" method="POST" class="inline-block" id="enableThemeForm-{{ $card['id'] }}">
        @csrf
        @if($card['hasEnableWarnings'])
            <x-form-button
                type="button"
                :label="__('common.enable')"
                variant="success"
                size="xs"
                icon="fas fa-play"
                :disabled="$viewOnly"
                :title="$tooltipText"
                @click="openModal('{{ $card['enableModalId'] }}')"
                class="py-2 px-3"
            />

            <x-ui-modal
                :id="$card['enableModalId']"
                :title="__('admin/settings/themes/index.permissions.enable_warning_title')"
                icon_type="warning"
                :confirm_label="__('common.enable')"
                :cancel_label="__('common.cancel')"
                form="enableThemeForm-{{ $card['id'] }}"
                confirm_color="yellow">
                <div class="text-left">
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                        {{ __('admin/settings/themes/index.permissions.enable_warning_message', ['name' => $card['name']]) }}
                    </p>
                    <div class="p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 mb-3">
                        <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1 ml-4 list-disc">
                            @foreach($card['enableWarnings'] as $warning)
                                <li>{{ $warning }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('admin/settings/themes/index.permissions.enable_warning_confirm') }}
                    </p>
                </div>
            </x-ui-modal>
        @else
            <x-form-button
                type="submit"
                :label="__('common.enable')"
                variant="success"
                size="xs"
                :disabled="$viewOnly"
                :title="$tooltipText"
                class="py-2 px-3"
                icon="fas fa-check"
            />
        @endif
    </form>

    <form action="{{ route('admin.settings.themes.uninstall', $card['id']) }}" method="POST" class="inline-block" id="uninstallThemeForm-{{ $card['id'] }}">
        @csrf
        <x-form-button
            type="button"
            :label="__('common.uninstall')"
            variant="danger"
            size="xs"
            :disabled="$viewOnly"
            :title="$tooltipText"
            class="py-2 px-3"
            icon="fas fa-trash"
            @click="openModal('uninstallThemeModal-{{ $card['id'] }}')"
        />

        <x-ui-modal
            id="uninstallThemeModal-{{ $card['id'] }}"
            :title="__('admin/settings/themes/index.uninstall.confirm_title')"
            :message="str_replace('{name}', $card['name'], __('admin/settings/themes/index.uninstall.confirm_message'))"
            :confirm_label="__('common.uninstall')"
            :cancel_label="__('common.cancel')"
            form="uninstallThemeForm-{{ $card['id'] }}"
            icon_type="danger"
            confirm_color="red"
        />
    </form>
@endif
