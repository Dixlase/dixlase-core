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
    'type' => 'button',                              // Button type (button, submit, reset)
    'disabled' => false,                             // Disable button
    'class' => '',                                   // Custom class
    'label' => __('common.save'),                    // Button text
    'title' => __('common.save_confirmation_title'), // Modal title
    'message' => __('common.save_confirmation_message'), // Modal message
    'confirmLabel' => __('common.save'),             // Confirm button text
    'confirm_label' => null,                         // 後方互換性
    'cancelLabel' => __('common.cancel'),            // Cancel button text
    'cancel_label' => null,                          // 後方互換性
    'form' => null,                                  // Form ID
    'modalId' => 'confirmationModal',                // Modal ID
    'modal_id' => null,                              // 後方互換性
    'id_confirmation' => null,                       // 後方互換性（非推奨）
    'backUrl' => null,                               // Back button URL (optional)
    'back_url' => null,                              // 後方互換性
    'backLabel' => __('common.back'),                // Back button text
    'back_label' => null,                            // 後方互換性
    'iconType' => 'info',                            // Icon type for modal
    'icon_type' => null,                             // 後方互換性
    'confirmColor' => 'blue',                        // Confirm button color
    'confirm_color' => null,                         // 後方互換性
])

@php
// 後方互換性: ケバブケースとスネークケースの統一
$confirmLabel = $confirm_label ?? $confirmLabel;
$cancelLabel = $cancel_label ?? $cancelLabel;
$backUrl = $back_url ?? $backUrl;
$backLabel = $back_label ?? $backLabel;
$iconType = $icon_type ?? $iconType;
$confirmColor = $confirm_color ?? $confirmColor;
$modalId = $modal_id ?? $id_confirmation ?? $modalId;
@endphp

<div class="flex justify-between items-center {{ $class }}">
    @if($backUrl)
        <x-form-button
            type="link"
            variant="tertiary"
            :label="$backLabel"
            icon="fas fa-arrow-left"
            :href="$backUrl"
            class="mx-2"
        />
    @endif
    
    <x-form-button
        type="button"
        variant="primary"
        :label="$label"
        icon="fas fa-save"
        :id="'saveButton_' . $modalId"
        class="save-button mx-2"
    />
</div>

@push('modals')
    <x-ui-modal-vanilla
        :id="$modalId"
        :title="$title"
        :message="$message"
        :confirmLabel="$confirmLabel"
        :cancelLabel="$cancelLabel"
        :iconType="$iconType"
        :confirmColor="$confirmColor"
        :form="$form"
    />
@endpush

@push('scripts')
<script>
(function() {
    const modalId = '{{ $modalId }}';
    const buttonId = 'saveButton_' + modalId;
    
    document.addEventListener('DOMContentLoaded', function() {
        const button = document.getElementById(buttonId);
        if (button) {
            button.addEventListener('click', function() {
                window.modalManager?.open(modalId);
            });
        }
    });
})();
</script>
@endpush
