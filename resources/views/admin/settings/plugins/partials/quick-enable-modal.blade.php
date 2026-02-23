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
    icon_type="warning"
    :confirm_label="__('common.enable')"
    :cancel_label="__('common.cancel')"
    form="quickEnableForm"
    confirm_color="yellow">
    <div class="text-left">
        @if($card['hasEnableWarnings'])
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
        @else
            <p class="text-sm text-gray-700 dark:text-gray-300">
                {{ __('admin/settings/plugins/index.permissions.enable_confirm_simple', ['name' => $card['translatedName']]) }}
            </p>
        @endif
    </div>
</x-ui-modal>
