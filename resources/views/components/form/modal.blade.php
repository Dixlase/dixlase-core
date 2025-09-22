{{--
This file is part of MySoftware.

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
    'id' => 'confirmationModal', // モーダルのID
    'title' => '確認',          // モーダルのタイトル
    'message' => 'この操作を実行しますか？', // モーダルのメッセージ
    'confirm_label' => '確認',     // 確認ボタンのテキスト
    'cancel_label' => 'キャンセル', // キャンセルボタンのテキスト
    'class' => '',               // モーダルのカスタムクラス
    // ↓ チェックボックス用追加パラメータ
    'checkbox' => false,          // チェックボックスを表示するかどうか
    'checkbox_name' => 'remove_db_data', // name属性
    'checkbox_label' => 'データベースを削除する', // チェックボックスのラベル
    'form' => null,              // フォームのID
    // ↓ 新しいカスタマイズパラメータ
    'icon_type' => 'info',     // アイコンタイプ: warning, danger, info, success
    'confirm_color' => 'blue',     // 確認ボタンの色: blue, red, green, yellow
])

<div id="{{ $id }}" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full opacity-0 pointer-events-none z-50 modal-overlay transition-opacity duration-300" onclick="closeModal('{{ $id }}')">
    <div class="relative top-20 mx-auto p-5 border dark:border-gray-600 w-96 shadow-lg rounded-md bg-white dark:bg-gray-800 scale-95 modal-content transition-transform duration-300" onclick="event.stopPropagation()">
        <div class="mt-3 text-center">
            @php
                $iconConfig = [
                    'warning' => ['bg' => 'bg-red-100 dark:bg-red-900/20', 'text' => 'text-red-600 dark:text-red-400', 'path' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z'],
                    'danger' => ['bg' => 'bg-red-100 dark:bg-red-900/20', 'text' => 'text-red-600 dark:text-red-400', 'path' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z'],
                    'info' => ['bg' => 'bg-blue-100 dark:bg-blue-900/20', 'text' => 'text-blue-600 dark:text-blue-400', 'path' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    'success' => ['bg' => 'bg-green-100 dark:bg-green-900/20', 'text' => 'text-green-600 dark:text-green-400', 'path' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z']
                ];
                $currentIcon = $iconConfig[$icon_type] ?? $iconConfig['warning'];
            @endphp
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full {{ $currentIcon['bg'] }}">
                <svg class="h-6 w-6 {{ $currentIcon['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $currentIcon['path'] }}"></path>
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mt-4">{{ $title }}</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500 dark:text-gray-300">{{ $message }}</p>
            </div>

            <!-- チェックボックスを表示したい場合 -->
            @if($checkbox)
                <div class="mt-4">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="{{ $checkbox_name }}" value="1" class="mr-2 rounded" />
                        <span class="text-sm text-gray-900 dark:text-white">
                            {{ $checkbox_label }}
                        </span>
                    </label>
                </div>
            @endif
        </div>
            <div class="flex items-center justify-center px-4 py-3">
                @php
                    $buttonConfig = [
                        'blue' => ['bg' => 'bg-blue-500', 'hover' => 'hover:bg-blue-600'],
                        'red' => ['bg' => 'bg-red-500', 'hover' => 'hover:bg-red-600'],
                        'green' => ['bg' => 'bg-green-500', 'hover' => 'hover:bg-green-600'],
                        'yellow' => ['bg' => 'bg-yellow-500', 'hover' => 'hover:bg-yellow-600']
                    ];
                    $currentButton = $buttonConfig[$confirm_color] ?? $buttonConfig['red'];
                @endphp
                <button type="button"
                    @if ($form) onclick="submitModalForm('{{ $form }}')" @else type="submit" @endif
                    class="px-4 py-2 {{ $currentButton['bg'] }} text-white text-base font-medium rounded-md w-24 mr-2 {{ $currentButton['hover'] }} focus:outline-none focus:ring-2 focus:ring-red-300">
                    {{ $confirm_label }}
                </button>
                <button type="button" onclick="closeModal('{{ $id }}')" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white text-base font-medium rounded-md w-24 hover:bg-gray-400 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    {{ $cancel_label }}
                </button>
            </div>
    </div>
</div>

@push('scripts')
<style>
/* モーダルのアニメーション制御 */
.modal-overlay {
    transition: none; /* 初期状態ではアニメーションなし */
}

.modal-overlay.modal-animate {
    transition: opacity 300ms ease-in-out; /* アニメーション有効 */
}

.modal-content {
    transition: none; /* 初期状態ではアニメーションなし */
}

.modal-content.modal-animate {
    transition: transform 300ms ease-in-out; /* アニメーション有効 */
}
</style>

<script>
var modalId = '{{ $id }}';

// モーダルウィンドウを開く
function openModal(modalId) {
    var modal = document.getElementById(modalId);
    var modalContent = modal.querySelector('.modal-content');
    
    // アニメーションクラスを追加
    modal.classList.add('modal-animate');
    modalContent.classList.add('modal-animate');
    
    // 表示状態に変更
    modal.classList.remove('opacity-0', 'pointer-events-none');
    modalContent.classList.remove('scale-95');
    modal.classList.add('opacity-100');
    modalContent.classList.add('scale-100');
}

// モーダルウィンドウを閉じる
function closeModal(modalId) {
    var modal = document.getElementById(modalId);
    var modalContent = modal.querySelector('.modal-content');
    
    // 非表示状態に変更
    modal.classList.remove('opacity-100');
    modalContent.classList.remove('scale-100');
    modal.classList.add('opacity-0');
    modalContent.classList.add('scale-95');
    
    // アニメーション完了後にpointer-eventsを無効化
    setTimeout(() => {
        modal.classList.add('pointer-events-none');
    }, 300);
}

// フォーム送信関数
function submitModalForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.submit();
    } else {
        console.error('Form with ID "' + formId + '" not found');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    @if ($errors->any())
        // モーダルを閉じる処理
        closeModal(modalId);
    @endif
});
</script>
@endpush
