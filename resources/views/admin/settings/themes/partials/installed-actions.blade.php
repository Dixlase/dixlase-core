{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

インストール済みテーマのアクションボタン
--}}

@php
    $isEnabled = $theme->id === $activeThemeId;
@endphp

@if($isEnabled)
    {{-- 有効化中のテーマ：設定ボタンのみ --}}
    @if(($theme->has_settings ?? false) && Route::has('admin.settings.themes.settings'))
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
    {{-- 無効化中：有効化とアンインストールボタン --}}
    @php
        $enableWarnings = [];
        $signatureStatus = $theme->permission_summary['signature']['status'] ?? 'unsigned';
        $riskLevel = $theme->permission_summary['risk_level'] ?? 'low';
        $hasPermissions = $theme->permission_summary['has_permissions'] ?? false;
        $hasMismatchesForEnable = $theme->permission_summary['audit']['has_mismatches'] ?? false;
        $auditedAtForEnable = $theme->permission_summary['audit']['audited_at'] ?? null;
        
        if ($signatureStatus === 'invalid') {
            $enableWarnings[] = __('admin/settings/themes/index.permissions.enable_warning_invalid_signature');
        }
        if ($signatureStatus === 'unsigned' || $signatureStatus === 'none') {
            $enableWarnings[] = __('admin/settings/themes/index.permissions.install_warning_unsigned');
        }
        if (!$hasPermissions) {
            $enableWarnings[] = __('admin/settings/themes/index.permissions.install_warning_undefined');
        }
        if ($riskLevel === 'high') {
            $enableWarnings[] = __('admin/settings/themes/index.permissions.enable_warning_needs_attention');
        }
        if ($riskLevel === 'medium') {
            $enableWarnings[] = __('admin/settings/themes/index.permissions.health_warning');
        }
        if ($hasMismatchesForEnable) {
            $enableWarnings[] = __('admin/settings/themes/index.permissions.install_warning_mismatch');
        }
        if (!$auditedAtForEnable) {
            $enableWarnings[] = __('admin/settings/themes/index.permissions.warning_not_scanned');
        }
        
        $hasEnableWarnings = !empty($enableWarnings);
        $enableModalId = 'enableThemeModal-' . $theme->id;
    @endphp
    
    <form action="{{ route('admin.settings.themes.switch', $theme->id) }}" method="POST" class="inline-block" id="enableThemeForm-{{ $theme->id }}">
        @csrf
        @if($hasEnableWarnings)
            <x-form-button
                type="button"
                :label="__('common.enable')"
                variant="success"
                size="xs"
                class="py-2 px-3"
                icon="fas fa-check"
                onclick="openModal('{{ $enableModalId }}')"
            />
            
            <x-ui-modal
                :id="$enableModalId"
                :title="__('admin/settings/themes/index.permissions.enable_warning_title')"
                icon_type="warning"
                :confirm_label="__('common.enable')"
                :cancel_label="__('common.cancel')"
                form="enableThemeForm-{{ $theme->id }}"
                confirm_color="yellow">
                <div class="text-left">
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                        {{ __('admin/settings/themes/index.permissions.enable_warning_message', ['name' => $theme->name]) }}
                    </p>
                    <div class="p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 mb-3">
                        <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1 ml-4 list-disc">
                            @foreach($enableWarnings as $warning)
                                <li>{{ $warning }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('admin/settings/themes/index.permissions.enable_warning_confirm') }}
                    </p>
                </div>
            </x-modal>
        @else
            <x-form-button
                type="submit"
                :label="__('common.enable')"
                variant="success"
                size="xs"
                class="py-2 px-3"
                icon="fas fa-check"
            />
        @endif
    </form>

    <form action="{{ route('admin.settings.themes.uninstall', $theme->id) }}" method="POST" class="inline-block" id="uninstallThemeForm-{{ $theme->id }}">
        @csrf
        <x-form-button
            type="button"
            :label="__('common.uninstall')"
            variant="danger"
            size="xs"
            class="py-2 px-3"
            icon="fas fa-trash"
            onclick="openModal('uninstallThemeModal-{{ $theme->id }}')"
        />

        <x-ui-modal
            id="uninstallThemeModal-{{ $theme->id }}"
            :title="__('admin/settings/themes/index.uninstall.confirm_title')"
            :message="str_replace('{name}', $theme->name, __('admin/settings/themes/index.uninstall.confirm_message'))"
            :confirm_label="__('common.uninstall')"
            :cancel_label="__('common.cancel')"
            form="uninstallThemeForm-{{ $theme->id }}"
            icon_type="danger"
            confirm_color="red"
        />
    </form>
@endif
