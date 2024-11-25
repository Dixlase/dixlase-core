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
    'cancelText' => '戻る',                  // キャンセルボタンのテキスト
    'theme' => 'light',                     // テーマ
])
<!-- 保存ボタン -->
@include('components.form.button', [
    'type' => $type,
    'label' => $label,
    'theme' => $theme,
])

<!-- モーダル -->
@include('components.form.modal', [
    'id' => $id,
    'title' => $title,
    'message' => $message,
    'confirmText' => $label,
    'cancelText' => $cancelText,
    'theme' => $theme
])
