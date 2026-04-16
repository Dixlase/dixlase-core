{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api Available for plugins/themes as <x-ui-modal />

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
    'confirmLabel' => '確認',     // 確認ボタンのテキスト
    'confirm_label' => null,      // 後方互換性
    'cancelLabel' => 'キャンセル', // キャンセルボタンのテキスト
    'cancel_label' => null,       // 後方互換性
    'closeLabel' => null,         // 閉じるボタンのテキスト（設定すると閉じるボタンのみモード）
    'close_label' => null,        // 後方互換性
    'class' => '',               // モーダルのカスタムクラス
    // ↓ チェックボックス用追加パラメータ
    'checkbox' => false,          // チェックボックスを表示するかどうか
    'checkboxName' => 'remove_db_data', // name属性
    'checkbox_name' => null,      // 後方互換性
    'checkboxLabel' => 'データベースを削除する', // チェックボックスのラベル
    'checkbox_label' => null,     // 後方互換性
    'form' => null,              // フォームのID
    // ↓ 新しいカスタマイズパラメータ
    'iconType' => 'info',         // アイコンタイプ: warning, danger, info, success
    'icon_type' => null,          // 後方互換性
    'confirmColor' => 'blue',     // 確認ボタンの色: blue, red, green, yellow
    'confirm_color' => null,      // 後方互換性
    'closeOnly' => false,         // 閉じるボタンのみ表示モード
    'close_only' => null,         // 後方互換性
    'dismissible' => true,        // 背景クリックで閉じるかどうか（デフォルト: true）
    'hideActions' => false,       // アクション領域を非表示（ローディング表示等で使用）
])

@php
    // 後方互換性: ケバブケースとスネークケースの統一
    $iconType = $icon_type ?? $iconType;
    $confirmColor = $confirm_color ?? $confirmColor;
    $confirmLabel = $confirm_label ?? $confirmLabel;
    $cancelLabel = $cancel_label ?? $cancelLabel;
    $closeLabel = $close_label ?? $closeLabel;
    $closeOnly = $close_only ?? $closeOnly;
    $checkboxName = $checkbox_name ?? $checkboxName;
    $checkboxLabel = $checkbox_label ?? $checkboxLabel;
    
    $iconClasses = [
        'warning' => 'fas fa-exclamation-triangle',
        'danger' => 'fas fa-times-circle',
        'info' => 'fas fa-info-circle',
        'success' => 'fas fa-check-circle',
        'loading' => 'fas fa-spinner fa-spin',
    ];
    $iconClass = $iconClasses[$iconType] ?? $iconClasses['warning'];

    // アイコンの色を設定
    $iconColorClasses = [
        'warning' => 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-400',
        'danger' => 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-400',
        'info' => 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-400',
        'success' => 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400',
        'loading' => 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900 dark:text-indigo-400',
    ];
    $iconColorClass = $iconColorClasses[$iconType] ?? $iconColorClasses['info'];
    
    // icon_typeに応じて確認ボタンのvariantを自動設定
    $iconTypeToVariant = [
        'info' => 'primary',      // 青
        'warning' => 'warning',   // 黄色
        'danger' => 'danger',     // 赤
        'success' => 'success'    // 緑
    ];
    $confirm_variant = $iconTypeToVariant[$iconType] ?? 'primary';
    
    // confirm_colorが指定されている場合はそれを優先
    $colorToVariant = [
        'blue' => 'primary',
        'red' => 'danger',
        'green' => 'success',
        'yellow' => 'warning'
    ];
    if ($confirmColor && isset($colorToVariant[$confirmColor])) {
        $confirm_variant = $colorToVariant[$confirmColor];
    }
    
    // slotが使用されているかチェック
    $hasCustomContent = !empty(trim($slot ?? ''));
    $hasCustomFooter = isset($footer) && !empty(trim($footer ?? ''));
@endphp

<div id="{{ $id }}" 
     class="modal"
     x-data="modal()"
     x-show="show"
     x-cloak
     @keydown.escape.window="handleEscape($event)"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     style="display: none;"
     {{ $attributes }}>
    <div class="modal-overlay bg-white/80 dark:bg-black/50" 
         @click="closeOnBackdrop({{ $dismissible ? 'true' : 'false' }})"></div>
    <div class="modal-container" 
         @click.stop
         style="transition: opacity 300ms ease-out, transform 300ms ease-out;">
        <div class="modal-content">
            {{-- アイコンは常に表示 --}}
            <div class="flex items-center justify-center w-16 h-16 mx-auto rounded-full {{ $iconColorClass }}">
                <i class="{{ $iconClass }} text-3xl" aria-hidden="true"></i>
            </div>

            <div class="modal-body">
                <h2 class="modal-title">{{ $title }}</h2>

                @if(!$hasCustomContent)
                    {{-- 標準モード：メッセージ + チェックボックス --}}
                    <div class="modal-message">
                        <p>{!! $message !!}</p>
                    </div>

                    @if($checkbox)
                        <div class="modal-checkbox">
                            <label>
                                <input type="checkbox" name="{{ $checkboxName }}" value="1" />
                                <span class="text-left">{!! $checkboxLabel !!}</span>
                            </label>
                        </div>
                    @endif
                @else
                    {{-- カスタムモード：メッセージ（あれば） + slotコンテンツ --}}
                    @if(!empty($message))
                        <div class="modal-message">
                            <p>{!! $message !!}</p>
                        </div>
                    @endif
                    {{ $slot }}

                    @if($checkbox)
                        <div class="modal-checkbox">
                            <label>
                                <input type="checkbox" name="{{ $checkboxName }}" value="1" />
                                <span class="text-left">{!! $checkboxLabel !!}</span>
                            </label>
                        </div>
                    @endif
                @endif
            </div>
        </div>
        
        @if(! $hideActions)
        <div class="modal-actions">
            @if(!$hasCustomFooter)
                @if($closeOnly || $closeLabel)
                    {{-- 閉じるボタンのみモード --}}
                    <x-form-button
                        type="button"
                        variant="secondary"
                        :label="$closeLabel ?? __('common.close')"
                        @click="close()"
                        class="mx-2"
                    />
                @else
                    {{-- 標準フッター（確認・キャンセル） --}}
                    <x-form-button
                        type="button"
                        variant="secondary"
                        icon="fas fa-times"
                        xDisabled="submitting"
                        @click="close()"
                        class="mx-2"
                    >{{ $cancelLabel ?? __('common.cancel') }}</x-form-button>
                    @if($form)
                        <x-form-button
                            type="submit"
                            variant="{{ $confirm_variant ?? 'primary' }}"
                            form="{{ $form }}"
                            xDisabled="submitting"
                            @click="submitting = true; submitModalForm('{{ $form }}')"
                            class="mx-2"
                        >
                            <i x-show="!submitting" class="fas fa-check mr-2"></i>
                            <i x-show="submitting" class="fas fa-spinner fa-spin mr-2" x-cloak></i>
                            {{ $confirmLabel ?? __('common.confirm') }}
                        </x-form-button>
                    @else
                        <x-form-button
                            type="button"
                            variant="{{ $confirm_variant ?? 'primary' }}"
                            xDisabled="submitting"
                            class="mx-2"
                        >
                            <i x-show="!submitting" class="fas fa-check mr-2"></i>
                            <i x-show="submitting" class="fas fa-spinner fa-spin mr-2" x-cloak></i>
                            {{ $confirmLabel ?? __('common.confirm') }}
                        </x-form-button>
                    @endif
                @endif
            @else
                {{-- カスタムフッター --}}
                {{ $footer }}
            @endif
        </div>
        @endif
    </div>
</div>

