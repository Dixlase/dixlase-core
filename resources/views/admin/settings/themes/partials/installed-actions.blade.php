{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

インストール済みテーマのアクションボタン
--}}

@if($card['isEnabled'])
    {{-- 有効化中のテーマ：設定ボタンのみ --}}
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
    {{-- 無効化中：有効化とアンインストールボタン --}}
    <form action="{{ route('admin.settings.themes.switch', $card['id']) }}" method="POST" class="inline-block" id="enableThemeForm-{{ $card['id'] }}">
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
