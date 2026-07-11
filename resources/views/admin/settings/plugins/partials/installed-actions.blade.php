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
    // Shared by all modify-action buttons on this partial. See
    // components/admin/save-button.blade.php for the CheckMenuAccess
    // contract. Server-side check.menu.edit still guards every POST
    // route these buttons submit to; this only handles the UI layer.
    $viewOnly = ! ($menuEditable ?? true);
    $tooltipText = $viewOnly ? __('common.view_only_action_disabled') : '';
@endphp

{{-- Settings screen link — stays enabled for view-only users because
     the settings page is itself only view for them. --}}
@if ($card['isEnabled'] && $card['settingsUrl'])
    <a href="{{ $card['settingsUrl'] }}" class="inline-block">
        <x-form-button
            type="button"
            :label="__('common.settings')"
            variant="primary"
            size="xs"
            icon="fas fa-cog"
            class="py-2 px-3"
        />
    </a>
@endif

@if ($card['isEnabled'])
    {{-- When enabled: disable button (with confirmation modal) --}}
    <form action="{{ route('admin.settings.plugins.disable', $card['id']) }}" method="POST" class="inline-block" id="disableForm-{{ $card['id'] }}">
        @csrf
        <x-form-button
            type="button"
            :label="__('common.disable')"
            variant="warning"
            size="xs"
            icon="fas fa-pause"
            :disabled="$viewOnly"
            :title="$tooltipText"
            @click="openModal('{{ $card['disableModalId'] }}')"
            class="py-2 px-3"
        />

        <x-ui-modal
            :id="$card['disableModalId']"
            :title="__('admin/settings/plugins/index.disabled.confirm_title')"
            :message="str_replace('{name}', $card['name'], __('admin/settings/plugins/index.disabled.confirm_message'))"
            icon_type="warning"
            :confirm_label="__('common.disable')"
            :cancel_label="__('common.cancel')"
            form="disableForm-{{ $card['id'] }}"
            confirm_color="yellow">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('admin/settings/plugins/index.disabled.confirm_warning') }}
            </p>
        </x-ui-modal>
    </form>
@else
    {{-- When disabled: enable and uninstall buttons --}}
    <form action="{{ route('admin.settings.plugins.enable', $card['id']) }}" method="POST" class="inline-block" id="enableForm-{{ $card['id'] }}">
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
                class="py-2 px-3 two-stage-action-btn"
                data-action-type="enable"
                data-needs-scan="{{ $card['needsScan'] ? '1' : '0' }}"
                data-plugin-slug="{{ $card['slug'] }}"
                data-plugin-name="{{ $card['translatedName'] }}"
                data-form-id="enableForm-{{ $card['id'] }}"
                data-enable-action="{{ $card['enableAction'] }}"
                data-operation-status="{{ $card['operationStatus']['status'] ?? 'unknown' }}"
                data-health-score="{{ $card['healthScore'] ?? '' }}"
                data-health-status="{{ $card['healthStatus'] ?? '' }}"
                data-health-issues="{{ json_encode($card['healthIssues'] ?? []) }}"
            />

            <x-ui-modal
                :id="$card['enableModalId']"
                :title="__('admin/settings/plugins/index.permissions.enable_warning_title')"
                message=""
                icon_type="warning"
                confirm_color="yellow">
                <div class="text-left">
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                        {{ __('admin/settings/plugins/index.permissions.enable_warning_message', ['name' => $card['translatedName']]) }}
                    </p>
                    <div class="p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 mb-3">
                        <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1 ml-4 list-disc">
                            @foreach($card['enableWarnings'] as $warning)
                                <li>{{ $warning }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @if(!$card['auditedAt'])
                        <p class="text-sm text-blue-600 dark:text-blue-400 mb-3">
                            <i class="fas fa-info-circle mr-1"></i>
                            {{ __('admin/settings/plugins/index.permissions.scan_recommendation') }}
                        </p>
                    @endif
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('admin/settings/plugins/index.permissions.enable_warning_confirm') }}
                    </p>
                </div>

                <x-slot:footer>
                    <x-form-button
                        type="button"
                        :label="__('common.cancel')"
                        variant="secondary"
                        class="mx-2"
                        @click="close()"
                    />
                    @if(!$card['auditedAt'])
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
                        :label="__('common.enable')"
                        variant="warning"
                        :disabled="$viewOnly"
                        :title="$tooltipText"
                        form="enableForm-{{ $card['id'] }}"
                        class="mx-2"
                    />
                </x-slot:footer>
            </x-ui-modal>
        @else
            <x-form-button
                type="button"
                :label="__('common.enable')"
                variant="success"
                size="xs"
                icon="fas fa-play"
                :disabled="$viewOnly"
                :title="$tooltipText"
                class="py-2 px-3 mx-2 two-stage-action-btn"
                data-action-type="enable"
                data-needs-scan="{{ $card['needsScan'] ? '1' : '0' }}"
                data-plugin-slug="{{ $card['slug'] }}"
                data-plugin-name="{{ $card['translatedName'] }}"
                data-form-id="enableForm-{{ $card['id'] }}"
                data-enable-action="{{ $card['enableAction'] }}"
                data-operation-status="{{ $card['operationStatus']['status'] ?? 'unknown' }}"
                data-health-score="{{ $card['healthScore'] ?? '' }}"
                data-health-status="{{ $card['healthStatus'] ?? '' }}"
                data-health-issues="{{ json_encode($card['healthIssues'] ?? []) }}"
            />
        @endif
    </form>

    <form action="{{ route('admin.settings.plugins.uninstall', $card['id']) }}" method="POST" class="inline-block" id="uninstallForm-{{ $card['id'] }}">
        @csrf
        <x-form-button
            type="button"
            :label="__('common.uninstall')"
            variant="danger"
            size="xs"
            icon="fas fa-trash"
            :disabled="$viewOnly"
            :title="$tooltipText"
            @click="openModal('uninstallModal-{{ $card['id'] }}')"
            class="py-2 px-3 mx-2"
        />

        <x-ui-modal
            id="uninstallModal-{{ $card['id'] }}"
            :title="__('admin/settings/plugins/index.uninstall.confirm_title')"
            :message="str_replace('{name}', $card['name'], __('admin/settings/plugins/index.uninstall.confirm_message'))"
            :confirm_label="__('common.uninstall')"
            :cancel_label="__('common.cancel')"
            :checkbox="true"
            checkbox_name="remove_db_data"
            checkbox_label="{!! __('admin/settings/plugins/index.uninstall.remove_data_checkbox') !!}"
            form="uninstallForm-{{ $card['id'] }}"
            icon_type="danger"
            confirm_color="red"
        />
    </form>
@endif

@if($hasBackup ?? false)
    {{-- Rollback to the automatic pre-update backup (source + prebuilt assets + schema, no npm) --}}
    <form action="{{ route('admin.settings.plugins.rollback', $card['id']) }}" method="POST" class="inline-block" id="rollbackPluginForm-{{ $card['id'] }}">
        @csrf
        <x-form-button
            type="button"
            :label="__('admin/settings/plugins/show.rollback.button')"
            variant="secondary"
            size="xs"
            :disabled="$viewOnly"
            :title="$tooltipText"
            class="py-2 px-3 mx-2"
            icon="fas fa-rotate-left"
            @click="openModal('rollbackPluginModal-{{ $card['id'] }}')"
        />

        <x-ui-modal
            id="rollbackPluginModal-{{ $card['id'] }}"
            :title="__('admin/settings/plugins/show.rollback.confirm_title')"
            :message="str_replace('{name}', $card['name'], __('admin/settings/plugins/show.rollback.confirm_message'))"
            :confirm_label="__('admin/settings/plugins/show.rollback.button')"
            :cancel_label="__('common.cancel')"
            form="rollbackPluginForm-{{ $card['id'] }}"
            icon_type="warning"
            confirm_color="yellow"
        />
    </form>
@endif
