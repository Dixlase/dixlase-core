{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

インストール済みプラグインのアクションボタン
--}}

@php
    $isEnabled = $plugin->isEnabled();
@endphp

{{-- 設定画面リンク --}}
@if ($isEnabled && isset($plugin->has_settings) && $plugin->has_settings)
    @php
        $settingsUrl = app('App\Http\Controllers\Admin\Settings\AdminPluginsSettingsController')->getPluginSettingsUrl($plugin);
    @endphp
    @if ($settingsUrl)
        <a href="{{ $settingsUrl }}" class="inline-block">
            <x-form.button
                type="button"
                :label="__('common.settings')"
                variant="primary"
                size="sm"
                icon="fas fa-cog"
            />
        </a>
    @endif
@endif

@if ($isEnabled)
    {{-- 有効化中：無効化ボタンのみ --}}
    <form action="{{ route('admin.settings.plugins.disable', $plugin->id) }}" method="POST" class="inline-block">
        @csrf
        <x-form.button
            type="submit"
            :label="__('common.disable')"
            variant="warning"
            size="sm"
            icon="fas fa-pause"
        />
    </form>
@else
    {{-- 無効化中：有効化とアンインストールボタン --}}
    @php
        $enableWarnings = [];
        $signatureStatus = $plugin->permission_summary['signature']['status'] ?? 'unsigned';
        $riskLevel = $plugin->permission_summary['risk_level'] ?? 'low';
        $hasPermissions = !empty($plugin->permission_summary['permissions'] ?? []);
        $hasMismatchesForEnable = $plugin->permission_summary['audit']['has_mismatches'] ?? false;
        $auditedAtForEnable = $plugin->permission_summary['audit']['audited_at'] ?? null;
        
        if ($signatureStatus === 'invalid') {
            $enableWarnings[] = __('admin/settings/plugins.permissions.enable_warning_invalid_signature');
        }
        if ($signatureStatus === 'unsigned' || $signatureStatus === 'none') {
            $enableWarnings[] = __('admin/settings/plugins.permissions.install_warning_unsigned');
        }
        if (!$hasPermissions) {
            $enableWarnings[] = __('admin/settings/plugins.permissions.install_warning_undefined');
        }
        if ($riskLevel === 'high') {
            $enableWarnings[] = __('admin/settings/plugins.permissions.enable_warning_high_risk');
        }
        if ($hasMismatchesForEnable) {
            $enableWarnings[] = __('admin/settings/plugins.permissions.install_warning_mismatch');
        }
        if (!$auditedAtForEnable) {
            $enableWarnings[] = __('admin/settings/plugins.permissions.warning_not_scanned');
        }
        
        $hasEnableWarnings = !empty($enableWarnings);
        $enableModalId = 'enableModal-' . $plugin->id;
    @endphp
    
    <form action="{{ route('admin.settings.plugins.enable', $plugin->id) }}" method="POST" class="inline-block" id="enableForm-{{ $plugin->id }}">
        @csrf
        @if($hasEnableWarnings)
            <x-form.button
                type="button"
                :label="__('common.enable')"
                variant="success"
                size="sm"
                icon="fas fa-play"
                onclick="openModal('{{ $enableModalId }}')"
            />
            
            <x-modal
                :id="$enableModalId"
                :title="__('admin/settings/plugins.permissions.enable_warning_title')"
                icon_type="warning"
                :confirm_label="__('common.enable')"
                :cancel_label="__('common.cancel')"
                form="enableForm-{{ $plugin->id }}"
                confirm_color="yellow">
                <div class="text-left">
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                        {{ __('admin/settings/plugins.permissions.enable_warning_message', ['name' => $plugin->translated_name]) }}
                    </p>
                    <div class="p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 mb-3">
                        <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1 ml-4 list-disc">
                            @foreach($enableWarnings as $warning)
                                <li>{{ $warning }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('admin/settings/plugins.permissions.enable_warning_confirm') }}
                    </p>
                </div>
            </x-modal>
        @else
            <x-form.button
                type="submit"
                :label="__('common.enable')"
                variant="success"
                size="sm"
                icon="fas fa-play"
            />
        @endif
    </form>

    <form action="{{ route('admin.settings.plugins.uninstall', $plugin->id) }}" method="POST" class="inline-block" id="uninstallForm-{{ $plugin->id }}">
        @csrf
        <x-form.button
            type="button"
            :label="__('common.uninstall')"
            variant="danger"
            size="sm"
            icon="fas fa-trash"
            onclick="openModal('uninstallModal-{{ $plugin->id }}')"
        />

        <x-modal
            id="uninstallModal-{{ $plugin->id }}"
            :title="__('admin/settings/plugins.index.uninstall.confirm_title')"
            :message="str_replace('{name}', $plugin->name, __('admin/settings/plugins.index.uninstall.confirm_message'))"
            :confirm_label="__('common.uninstall')"
            :cancel_label="__('common.cancel')"
            :checkbox="true"
            checkbox_name="remove_db_data"
            checkbox_label="{!! __('admin/settings/plugins.index.uninstall.remove_data_checkbox') !!}"
            form="uninstallForm-{{ $plugin->id }}"
            icon_type="danger"
            confirm_color="red"
        />
    </form>
@endif
