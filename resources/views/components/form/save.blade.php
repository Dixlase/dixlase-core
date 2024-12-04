{{--
This file is part of Your Software Name.

Copyright (C) 2024 exc-D inc.
Website: https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@props([
    'type' => 'button',      // ボタンのタイプ (button, submit, reset)
    'class' => '',                          // カスタムクラス
    'label' => '保存',                    // ボタンのテキスト
    'id' => 'confirmationModal',            // モーダルのID
    'title' => '保存の確認',                  // モーダルのタイトル
    'message' => 'この内容で保存しますか？',    // モーダルのメッセージ
    'confirm_label' => '保存',                  // キャンセルボタンのテキスト
    'id_confirmation' => 'confirmationModal',        // モーダルのID
    'id_delete' => 'deleteModal',                  // 削除モーダルのID


])

<!--<div class="fixed bottom-0 left-0 w-full flex z-50 justify-center mt-6 border-t py-3 px-3 {{ config('admin.appearance_class.layout.save_button') }}">-->
    <!-- 保存ボタン -->
    @include('components::form.button', [
        'type' => $type,
        'label' => $label,
        'onclick' => "openModal('" . $id_confirmation . "')",

    ])
<!--</div>-->

<!-- 保存モーダル -->
@include('components::form.modal', [
    'id' => $id_confirmation,
    'title' => $title,
    'message' => $message,
    'confirm_label' => $label,
    'cancel_label' => $cancel_label,
])


