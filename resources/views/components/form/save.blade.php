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
    'type' => 'button',      // ボタンのタイプ (button, submit, reset)
    'disabled' => false,     // ボタンを無効にする
    'class' => '',                          // カスタムクラス
    'label' => '保存',                    // ボタンのテキスト
    'id' => 'confirmationModal',            // モーダルのID
    'title' => '保存の確認',                  // モーダルのタイトル
    'message' => 'この内容で保存しますか？',    // モーダルのメッセージ
    'confirm_label' => '保存',                  // キャンセルボタンのテキスト
    'form' => null,                          // フォームのID
    'id_confirmation' => 'confirmationModal',        // モーダルのID
    'id_delete' => 'deleteModal',                  // 削除モーダルのID


])

<!-- 保存ボタン -->
@include('components::form.button', [
    'type' => $type,
    'label' => $label,
    'disabled' => $disabled,
    'onclick' => "openModal('" . $id_confirmation . "')",
    'form' => $form,
])

<!-- 保存モーダル -->
@push('modals')
    @include('components::form.modal', [
    'id' => $id_confirmation,
    'title' => $title,
    'message' => $message,
    'confirm_label' => $label,
    'cancel_label' => $cancel_label,
    'form' => $form,
])
@endpush



