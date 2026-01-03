{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

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

@props([
    'modalId' => 'methodChangeModal',
    'usedMethod' => null,
    'currentMethod' => null,
    'autoOpen' => false,
])

<x-modal :id="$modalId" :title="__('admin/dashboard.method_change_modal.title')" icon_type="info">
    <div class="space-y-4">
        {{-- 説明メッセージ --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <p class="text-sm text-blue-800 dark:text-blue-200">
                <i class="fas fa-info-circle mr-2"></i>
                {{ __('admin/dashboard.method_change_modal.message', [
                    'used_method' => $usedMethod ? $usedMethod->label() : '',
                    'current_method' => $currentMethod ? $currentMethod->label() : ''
                ]) }}
            </p>
        </div>

        {{-- 質問 --}}
        <p class="text-gray-700 dark:text-gray-300">
            {{ __('admin/dashboard.method_change_modal.question', [
                'used_method' => $usedMethod ? $usedMethod->label() : ''
            ]) }}
        </p>

        {{-- ボタン --}}
        <div class="flex flex-col sm:flex-row gap-3 justify-end">
            <button
                type="button"
                onclick="dismissMethodChangeModal()"
                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                {{ __('admin/dashboard.method_change_modal.keep_button') }}
            </button>
            <button
                type="button"
                onclick="switchToUsedMethod()"
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                {{ __('admin/dashboard.method_change_modal.switch_button') }}
            </button>
        </div>
    </div>
</x-modal>

@if($autoOpen)
<script @cspNonce>
document.addEventListener('DOMContentLoaded', function() {
    const modal = FlowbiteInstances.getInstance('Modal', '{{ $modalId }}');
    if (modal) {
        modal.show();
    }
});
</script>
@endif

<script @cspNonce>
function switchToUsedMethod() {
    fetch('{{ route('admin.dashboard.switch2faMethod') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // モーダルを閉じる
            const modal = FlowbiteInstances.getInstance('Modal', '{{ $modalId }}');
            if (modal) {
                modal.hide();
            }
            
            // 成功メッセージを表示
            showNotification(data.message, 'success');
            
            // 回復コードモーダルがある場合は表示
            setTimeout(() => {
                const recoveryModal = FlowbiteInstances.getInstance('Modal', 'recoveryCodesModal');
                if (recoveryModal) {
                    recoveryModal.show();
                }
            }, 500);
        } else {
            showNotification(data.message || '{{ __('admin/dashboard.method_switch_failed') }}', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('{{ __('admin/dashboard.method_switch_failed') }}', 'error');
    });
}

function dismissMethodChangeModal() {
    fetch('{{ route('admin.dashboard.dismissMethodChange') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // モーダルを閉じる
            const modal = FlowbiteInstances.getInstance('Modal', '{{ $modalId }}');
            if (modal) {
                modal.hide();
            }
            
            // 回復コードモーダルがある場合は表示
            setTimeout(() => {
                const recoveryModal = FlowbiteInstances.getInstance('Modal', 'recoveryCodesModal');
                if (recoveryModal) {
                    recoveryModal.show();
                }
            }, 500);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function showNotification(message, type = 'info') {
    // 既存の通知システムを使用（Flowbite Toastなど）
    // ここでは簡易的にalertを使用
    alert(message);
}
</script>
