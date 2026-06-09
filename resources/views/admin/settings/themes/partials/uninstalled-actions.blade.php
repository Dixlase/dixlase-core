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

{{-- Install button --}}
<form action="{{ route('admin.settings.themes.install') }}" method="POST" class="inline-block" id="installThemeForm-{{ $card['directory'] }}">
    @csrf
    <input type="hidden" name="directory" value="{{ $card['directory'] }}">
    <x-form-button
        type="button"
        :label="__('common.install')"
        variant="success"
        size="xs"
        class="py-2 px-3"
        icon="fas fa-download"
        @click="openModal('installThemeModal-{{ $card['directory'] }}')"
    />

    <x-ui-modal
        id="installThemeModal-{{ $card['directory'] }}"
        :title="$card['installWarnings']['hasWarnings'] ? __('admin/settings/themes/index.permissions.install_warning_title') : __('admin/settings/themes/index.install.confirm_title')"
        :confirm_label="__('common.install')"
        :cancel_label="__('common.cancel')"
        form="installThemeForm-{{ $card['directory'] }}"
        :icon_type="$card['installWarnings']['hasWarnings'] ? 'warning' : 'info'"
        :confirm_color="$card['installWarnings']['hasWarnings'] ? 'yellow' : 'green'"
    >
        @if($card['installWarnings']['hasWarnings'])
            <div class="text-left">
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                    {{ str_replace('{name}', $card['name'], __('admin/settings/themes/index.install.confirm_message')) }}
                </p>
                <div class="p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 mb-3">
                    <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-200 mb-2">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        {{ __('admin/settings/themes/index.permissions.install_warning_risk') }}
                    </p>
                    <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1 ml-5 list-disc">
                        @if($card['installWarnings']['isUndefined'])
                            <li>{{ __('admin/settings/themes/index.permissions.install_warning_undefined') }}</li>
                        @endif
                        @if($card['installWarnings']['isUnsigned'])
                            <li>{{ __('admin/settings/themes/index.permissions.install_warning_unsigned') }}</li>
                        @endif
                        @if($card['installWarnings']['hasMismatches'])
                            <li>{{ __('admin/settings/themes/index.permissions.install_warning_mismatch') }}</li>
                        @endif
                        @if($card['installWarnings']['isNotScanned'])
                            <li>{{ __('admin/settings/themes/index.permissions.warning_not_scanned') }}</li>
                        @endif
                        @if(in_array($card['installWarnings']['riskLevel'], ['medium', 'high']))
                            <li>{{ __('admin/settings/themes/index.permissions.risk_' . $card['installWarnings']['riskLevel']) }}</li>
                        @endif
                    </ul>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('admin/settings/themes/index.permissions.install_warning_confirm') }}
                </p>
            </div>
        @else
            <p class="text-sm text-gray-700 dark:text-gray-300">
                {{ str_replace('{name}', $card['name'], __('admin/settings/themes/index.install.confirm_message')) }}
            </p>
        @endif
    </x-ui-modal>
</form>

{{-- Delete button --}}
<form action="{{ route('admin.settings.themes.delete') }}" method="POST" class="inline-block" id="deleteThemeForm-{{ $card['directory'] }}">
    @csrf
    <input type="hidden" name="directory" value="{{ $card['directory'] }}">
    <x-form-button
        type="button"
        :label="__('common.delete')"
        variant="danger"
        size="xs"
        class="py-2 px-3"
        icon="fas fa-trash"
        @click="openModal('deleteThemeModal-{{ $card['directory'] }}')"
    />

    <x-ui-modal
        id="deleteThemeModal-{{ $card['directory'] }}"
        :title="__('admin/settings/themes/index.delete.confirm_title')"
        :message="str_replace('{name}', $card['name'], __('admin/settings/themes/index.delete.confirm_message'))"
        :confirm_label="__('common.delete')"
        :cancel_label="__('common.cancel')"
        form="deleteThemeForm-{{ $card['directory'] }}"
        icon_type="danger"
        confirm_color="red"
    />
</form>
