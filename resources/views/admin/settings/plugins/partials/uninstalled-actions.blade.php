{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

未インストールプラグインのアクションボタン
--}}

@php
    $audit = $plugin['permission_summary']['audit'] ?? [];
    $hasMismatches = $audit['has_mismatches'] ?? false;
    $auditedAt = $audit['audited_at'] ?? null;
    $isNotScanned = empty($auditedAt);
    $isUnsigned = ($plugin['permission_summary']['signature']['status'] ?? 'unsigned') === 'unsigned';
    $isUndefined = !($plugin['permission_summary']['has_permissions'] ?? false);
    $riskLevel = $plugin['permission_summary']['risk_level'] ?? 'unknown';
    $hasWarnings = $hasMismatches || $isUnsigned || $isUndefined || $isNotScanned || in_array($riskLevel, ['medium', 'high']);
@endphp

{{-- インストールボタン --}}
<form action="{{ route('admin.settings.plugins.install') }}" method="POST" class="inline-block" id="installForm-{{ $plugin['directory'] }}">
    @csrf
    <input type="hidden" name="directory" value="{{ $plugin['directory'] }}">
    <x-form.button
        type="button"
        :label="__('common.install')"
        variant="success"
        size="xs"
        class="py-2 px-3"
        icon="fas fa-download"
        onclick="openModal('installModal-{{ $plugin['directory'] }}')"
    />

    <x-ui.modal
        id="installModal-{{ $plugin['directory'] }}"
        :title="$hasWarnings ? __('admin/settings/plugins/index.permissions.install_warning_title') : __('admin/settings/plugins/index.install.confirm_title')"
        :confirm_label="__('common.install')"
        :cancel_label="__('common.cancel')"
        form="installForm-{{ $plugin['directory'] }}"
        :icon_type="$hasWarnings ? 'warning' : 'info'"
        :confirm_color="$hasWarnings ? 'yellow' : 'green'"
    >
        @if($hasWarnings)
            <div class="text-left">
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                    {{ str_replace('{name}', $plugin['name'], __('admin/settings/plugins/index.install.confirm_message')) }}
                </p>
                <div class="p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 mb-3">
                    <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-200 mb-2">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        {{ __('admin/settings/plugins/index.permissions.install_warning_risk') }}
                    </p>
                    <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1 ml-5 list-disc">
                        @if($isUndefined)
                            <li>{{ __('admin/settings/plugins/index.permissions.install_warning_undefined') }}</li>
                        @endif
                        @if($isUnsigned)
                            <li>{{ __('admin/settings/plugins/index.permissions.install_warning_unsigned') }}</li>
                        @endif
                        @if($hasMismatches)
                            <li>{{ __('admin/settings/plugins/index.permissions.install_warning_mismatch') }}</li>
                        @endif
                        @if($isNotScanned)
                            <li>{{ __('admin/settings/plugins/index.permissions.warning_not_scanned') }}</li>
                        @endif
                        @if(in_array($riskLevel, ['medium', 'high']))
                            <li>{{ __('admin/settings/plugins/index.permissions.risk_' . $riskLevel) }}</li>
                        @endif
                    </ul>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('admin/settings/plugins/index.permissions.install_warning_confirm') }}
                </p>
            </div>
        @else
            <p class="text-sm text-gray-700 dark:text-gray-300">
                {{ str_replace('{name}', $plugin['name'], __('admin/settings/plugins/index.install.confirm_message')) }}
            </p>
        @endif
    </x-modal>
</form>

{{-- 削除ボタン --}}
<form action="{{ route('admin.settings.plugins.delete') }}" method="POST" class="inline-block" id="deleteForm-{{ $plugin['directory'] }}">
    @csrf
    <input type="hidden" name="directory" value="{{ $plugin['directory'] }}">
    <x-form.button
        type="button"
        :label="__('common.delete')"
        variant="danger"
        size="xs"
        icon="fas fa-trash"
        class="py-2 px-3"
        onclick="openModal('deleteModal-{{ $plugin['directory'] }}')"
    />

    <x-ui.modal
        id="deleteModal-{{ $plugin['directory'] }}"
        :title="__('admin/settings/plugins/index.delete.confirm_title')"
        :message="str_replace('{name}', $plugin['name'], __('admin/settings/plugins/index.delete.confirm_message'))"
        :confirm_label="__('common.delete')"
        :cancel_label="__('common.cancel')"
        form="deleteForm-{{ $plugin['directory'] }}"
        icon_type="danger"
        confirm_color="red"
    />
</form>
