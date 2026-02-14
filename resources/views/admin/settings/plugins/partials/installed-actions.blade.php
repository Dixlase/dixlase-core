{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

インストール済みプラグインのアクションボタン
--}}

{{-- 設定画面リンク --}}
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
    {{-- 有効化中：無効化ボタンのみ --}}
    <form action="{{ route('admin.settings.plugins.disable', $card['id']) }}" method="POST" class="inline-block">
        @csrf
        <x-form-button
            type="submit"
            :label="__('common.disable')"
            variant="warning"
            size="xs"
            icon="fas fa-pause"
            class="py-2 px-3"
        />
    </form>
@else
    {{-- 無効化中：有効化とアンインストールボタン --}}
    <form action="{{ route('admin.settings.plugins.enable', $card['id']) }}" method="POST" class="inline-block" id="enableForm-{{ $card['id'] }}">
        @csrf
        @if($card['hasEnableWarnings'])
            <x-form-button
                type="button"
                :label="__('common.enable')"
                variant="success"
                size="xs"
                icon="fas fa-play"
                @click="openModal('{{ $card['enableModalId'] }}')"
                class="py-2 px-3"
            />

            <x-ui-modal
                :id="$card['enableModalId']"
                :title="__('admin/settings/plugins/index.permissions.enable_warning_title')"
                icon_type="warning"
                :confirm_label="__('common.enable')"
                :cancel_label="__('common.cancel')"
                form="enableForm-{{ $card['id'] }}"
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
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('admin/settings/plugins/index.permissions.enable_warning_confirm') }}
                    </p>
                </div>
            </x-modal>
        @else
            <x-form-button
                type="submit"
                :label="__('common.enable')"
                variant="success"
                size="xs"
                icon="fas fa-play"
                class="py-2 px-3"
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
            @click="openModal('uninstallModal-{{ $card['id'] }}')"
            class="py-2 px-3"
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
