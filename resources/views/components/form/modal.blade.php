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
])

<div id="{{ $id }}" class="mt-0 fixed inset-0 z-[9999] flex items-center justify-center bg-opacity-50 dark:bg-opacity-70 opacity-0 pointer-events-none transition-opacity duration-300 bg-gray-100 dark:bg-black">
    <div class="relative transform overflow-hidden rounded-lg text-left shadow-xl transition-all sm:w-full sm:max-w-lg scale-95 bg-white dark:bg-gray-900">
        <div class="px-4 pb-4 pt-5 sm:p-6 sm:pb-4 {{ config('admin.appearance_class.form.modal') }}">
            <div class="sm:flex sm:items-start">
                <div class="mx-auto flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>
                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white" id="modal-title">{{ $title }}</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-900 dark:text-white">{{ $message }}</p>
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
            </div>
        </div>
        <div class="px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 bg-gray-50 dark:bg-gray-800">
            <button type="submit"
                @if ($form) form="{{ $form }}" @endif
                class="inline-flex w-full justify-center rounded-md bg-blue-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-600 sm:ml-3 sm:w-auto">
                {{ $confirm_label }}
            </button>
            <button type="button" onclick="closeModal('{{ $id }}')" class="mt-3 inline-flex w-full justify-center rounded-md px-3 py-2 text-sm font-semibold shadow-sm ring-1 ring-inset  sm:mt-0 sm:w-auto bg-white text-gray-900 ring-gray-300 hover:bg-gray-50 dark:bg-gray-900 dark:text-white dark:ring-gray-300 dark:hover:bg-gray-50' }}">
                {{ $cancel_label }}
            </button>
        </div>
    </div>
</div>

<script>

const modalId = '{{ $id }}';
// モーダルウィンドウを開く
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('opacity-0', 'pointer-events-none', 'scale-95');
    modal.classList.add('opacity-100', 'scale-100');
}

// モーダルウィンドウを閉じる
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('opacity-100', 'scale-100');
    modal.classList.add('opacity-0', 'pointer-events-none', 'scale-95');
}

document.addEventListener('DOMContentLoaded', () => {
    @if ($errors->any())
        // モーダルを閉じる処理
        closeModal(modalId);
    @endif
});

</script>
