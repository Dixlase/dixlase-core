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
      (see LICENSE.commercial, or contact info@dixlase.org).

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
<form action="{{ route('admin.settings.plugins.install') }}" method="POST" class="inline-block" id="installForm-{{ $card['directory'] }}">
    @csrf
    <input type="hidden" name="directory" value="{{ $card['directory'] }}">
    <x-form-button
        type="button"
        :label="__('common.install')"
        variant="success"
        size="xs"
        class="py-2 px-3 two-stage-action-btn"
        icon="fas fa-download"
        data-action-type="install"
        data-needs-scan="{{ $card['needsScan'] ? '1' : '0' }}"
        data-plugin-slug="{{ $card['slug'] }}"
        data-plugin-name="{{ $card['name'] }}"
        data-form-id="installForm-{{ $card['directory'] }}"
        data-enable-action="{{ $card['enableAction'] ?? 'allowed' }}"
        data-operation-status="{{ $card['operationStatus']['status'] ?? 'unknown' }}"
        data-health-score="{{ $card['healthScore'] ?? '' }}"
        data-health-status="{{ $card['healthStatus'] ?? '' }}"
        data-health-issues="{{ json_encode($card['healthIssues'] ?? []) }}"
    />

    <x-ui-modal
        id="installModal-{{ $card['directory'] }}"
        :title="$card['installWarnings']['hasWarnings'] ? __('admin/settings/plugins/index.permissions.install_warning_title') : __('admin/settings/plugins/index.install.confirm_title')"
        message=""
        :icon_type="$card['installWarnings']['hasWarnings'] ? 'warning' : 'info'"
        :confirm_color="$card['installWarnings']['hasWarnings'] ? 'yellow' : 'green'"
    >
        @if($card['installWarnings']['hasWarnings'])
            <div class="text-left">
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                    {{ str_replace('{name}', $card['name'], __('admin/settings/plugins/index.install.confirm_message')) }}
                </p>
                <div class="p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 mb-3">
                    <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-200 mb-2">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        {{ __('admin/settings/plugins/index.permissions.install_warning_risk') }}
                    </p>
                    <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1 ml-5 list-disc">
                        @if($card['installWarnings']['isUndefined'])
                            <li>{{ __('admin/settings/plugins/index.permissions.install_warning_undefined') }}</li>
                        @endif
                        @if($card['installWarnings']['isUnsigned'])
                            <li>{{ __('admin/settings/plugins/index.permissions.install_warning_unsigned') }}</li>
                        @endif
                        @if($card['installWarnings']['hasMismatches'])
                            <li>{{ __('admin/settings/plugins/index.permissions.install_warning_mismatch') }}</li>
                        @endif
                        @if($card['installWarnings']['isNotScanned'])
                            <li>{{ __('admin/settings/plugins/index.permissions.warning_not_scanned') }}</li>
                        @endif
                        @if(in_array($card['installWarnings']['riskLevel'], ['medium', 'high']))
                            <li>{{ __('admin/settings/plugins/index.permissions.risk_' . $card['installWarnings']['riskLevel']) }}</li>
                        @endif
                    </ul>
                </div>
                @if($card['installWarnings']['isNotScanned'])
                    <p class="text-sm text-blue-600 dark:text-blue-400 mb-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        {{ __('admin/settings/plugins/index.permissions.scan_recommendation') }}
                    </p>
                @endif
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('admin/settings/plugins/index.permissions.install_warning_confirm') }}
                </p>
            </div>
        @else
            <p class="text-sm text-gray-700 dark:text-gray-300">
                {{ str_replace('{name}', $card['name'], __('admin/settings/plugins/index.install.confirm_message')) }}
            </p>
        @endif

        <x-slot:footer>
            <x-form-button
                type="button"
                :label="__('common.cancel')"
                variant="secondary"
                class="mx-2"
                @click="close()"
            />
            @if($card['installWarnings']['isNotScanned'] ?? false)
                <x-form-button
                    type="button"
                    :label="__('admin/settings/plugins/index.permissions.audit_button')"
                    variant="primary"
                    icon="fas fa-search"
                    class="audit-btn mx-2"
                    data-slug="{{ $card['slug'] }}"
                    @click="close()"
                />
            @endif
            <x-form-button
                type="submit"
                :label="__('common.install')"
                :variant="$card['installWarnings']['hasWarnings'] ? 'warning' : 'success'"
                form="installForm-{{ $card['directory'] }}"
                class="mx-2"
            />
        </x-slot:footer>
    </x-ui-modal>
</form>

{{-- Delete button --}}
<form action="{{ route('admin.settings.plugins.delete') }}" method="POST" class="inline-block" id="deleteForm-{{ $card['directory'] }}">
    @csrf
    <input type="hidden" name="directory" value="{{ $card['directory'] }}">
    <x-form-button
        type="button"
        :label="__('common.delete')"
        variant="danger"
        size="xs"
        icon="fas fa-trash"
        class="py-2 px-3"
        @click="openModal('deleteModal-{{ $card['directory'] }}')"
    />

    <x-ui-modal
        id="deleteModal-{{ $card['directory'] }}"
        :title="__('admin/settings/plugins/index.delete.confirm_title')"
        :message="str_replace('{name}', $card['name'], __('admin/settings/plugins/index.delete.confirm_message'))"
        :confirm_label="__('common.delete')"
        :cancel_label="__('common.cancel')"
        form="deleteForm-{{ $card['directory'] }}"
        icon_type="danger"
        confirm_color="red"
    />
</form>
