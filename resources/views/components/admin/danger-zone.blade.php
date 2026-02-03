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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@props([
    'unlockRoute' => null,
    'forceLogoutRoute' => null,
    'deleteRoute' => null,
    'canDelete' => true,
    'entityType' => 'member', // 'member' or 'user'
    'entityId' => null,
    'entityName' => null,
])

<!-- 管理操作セクション -->
<section>
    <h2>{{ __('common.management_operations') }}</h2>

    @if($unlockRoute)
        <fieldset>
            <legend>{{ __('components/admin/danger-zone.unlock_lockout') }}</legend>
            <p class="mb-4">{{ __('components/admin/danger-zone.unlock_lockout_description') }}</p>
            <x-form-button
                variant="info"
                icon="fas fa-unlock"
                :label="__('components/admin/danger-zone.unlock_lockout_button')"
                @click="openModal('unlockLockoutModal')"
            />
        </fieldset>
    @endif
    
    @if($forceLogoutRoute)
        <fieldset>
            <legend>{{ __('components/admin/danger-zone.force_logout') }}</legend>
            <p class="mb-4">{{ __('components/admin/danger-zone.force_logout_description') }}</p>
            <x-form-button
                variant="warning"
                icon="fas fa-sign-out-alt"
                :label="__('components/admin/danger-zone.force_logout_button')"
                @click="openModal('forceLogoutModal')"
            />
        </fieldset>
    @endif

    @if($deleteRoute && $canDelete)
        <fieldset>
            <legend>{{ __('components/admin/danger-zone.delete_' . $entityType) }}</legend>
            <p class="mb-4">{{ __('components/admin/danger-zone.delete_' . $entityType . '_description') }}</p>
            <x-form-button
                variant="danger"
                icon="fas fa-trash"
                :label="__('components/admin/danger-zone.delete_' . $entityType . '_button')"
                @click="openModal('delete{{ ucfirst($entityType) }}Modal')"
            />
        </fieldset>
    @endif
</section>

<!-- モーダル -->
@if($unlockRoute)
    <x-ui-modal
        id="unlockLockoutModal"
        :title="__('components/admin/danger-zone.unlock_lockout')"
        :message="__('components/admin/danger-zone.unlock_lockout_description')"
        :confirm-label="__('components/admin/danger-zone.unlock_lockout_button')"
        :cancel-label="__('common.cancel')"
        form="unlock-lockout-form"
        icon-type="info"
        confirm-color="blue"
    />
@endif

@if($forceLogoutRoute)
    <x-ui-modal
        id="forceLogoutModal"
        :title="__('components/admin/danger-zone.force_logout')"
        :message="__('components/admin/danger-zone.force_logout_description')"
        :confirm-label="__('components/admin/danger-zone.force_logout_button')"
        :cancel-label="__('common.cancel')"
        form="force-logout-form"
        icon-type="warning"
        confirm-color="yellow"
    />
@endif

@if($deleteRoute && $canDelete)
    <x-ui-modal
        id="delete{{ ucfirst($entityType) }}Modal"
        :title="__('common.delete_confirmation_title')"
        :message="__('components/admin/danger-zone.delete_' . $entityType . '_description')"
        :confirm-label="__('common.delete')"
        :cancel-label="__('common.cancel')"
        form="{{ $entityType }}-delete-form"
        icon-type="danger"
        confirm-color="red"
    />
@endif

<!-- フォーム -->
@if($unlockRoute)
    <form id="unlock-lockout-form" 
          action="{{ $unlockRoute }}" 
          method="POST" 
          style="display: none;">
        @csrf
    </form>
@endif

@if($forceLogoutRoute)
    <form id="force-logout-form" 
          action="{{ $forceLogoutRoute }}" 
          method="POST" 
          style="display: none;">
        @csrf
    </form>
@endif

@if($deleteRoute && $canDelete)
    <form id="{{ $entityType }}-delete-form" 
          action="{{ $deleteRoute }}" 
          method="POST" 
          style="display: none;">
        @csrf
        @method('DELETE')
    </form>
@endif
