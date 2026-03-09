{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

インストール直後のプラグイン有効化確認モーダル
フラッシュメッセージ内のボタンから呼び出される
--}}

<form action="{{ route('admin.settings.plugins.enable', $card['id']) }}" method="POST" id="quickEnableForm">
    @csrf
</form>

<x-ui-modal
    id="quickEnableModal"
    :title="__('admin/settings/plugins/index.permissions.enable_warning_title')"
    message=""
    icon_type="warning"
    confirm_color="yellow"
    data-needs-scan="{{ $card['needsScan'] ? '1' : '0' }}"
    data-plugin-slug="{{ $card['slug'] }}"
    data-plugin-name="{{ $card['translatedName'] }}"
    data-action-type="enable"
    data-form-id="quickEnableForm">
    <div class="text-left">
        @if($card['hasEnableWarnings'])
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                {{ __('admin/settings/plugins/index.permissions.enable_warning_message', ['name' => $card['translatedName']]) }}
            </p>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                <i class="fas fa-file-signature mr-1"></i>
                {{ __('admin/settings/plugins/index.permissions.signature_section_label') }}
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
        @else
            <p class="text-sm text-gray-700 dark:text-gray-300">
                {{ __('admin/settings/plugins/index.permissions.enable_confirm_simple', ['name' => $card['translatedName']]) }}
            </p>
        @endif
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
            form="quickEnableForm"
            class="mx-2"
        />
    </x-slot:footer>
</x-ui-modal>
