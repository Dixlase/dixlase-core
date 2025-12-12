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

@extends('layouts.admin')

@section('content')

<form method="POST" action="{{ route('admin.members.settings.roles.update') }}" id="member-roles-form" class="permission-management permission-form" novalidate>
    @csrf
    
    {{-- コア機能の権限設定 --}}
    <div class="permission-section-wrapper mb-8">
        <div class="flex items-center gap-3 mb-4">
            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30">
                <i class="fas fa-cog text-indigo-600 dark:text-indigo-400"></i>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('admin.members.roles.core_permissions') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.members.roles.core_permissions_description') }}</p>
            </div>
        </div>
        
        <div class="permission-groups space-y-6 mb-10" x-data="{ openSections: {} }">
            @php
                $currentSection = null;
                $sectionItems = [];
            @endphp
            
            @foreach ($permissionItems as $index => $item)
                @if ($item['type'] === 'heading')
                    {{-- 前のセクションがあれば出力 --}}
                    @if ($currentSection !== null)
                        @include('admin.members.partials.roles-permission-group', [
                            'sectionTitle' => $currentSection,
                            'sectionId' => 'section_' . md5($currentSection),
                            'items' => $sectionItems,
                            'permissions' => $permissions,
                            'roles' => $roles
                        ])
                    @endif
                    
                    {{-- 新しいセクションを開始 --}}
                    @php
                        $currentSection = $item['title'];
                        $sectionItems = [];
                    @endphp
                @elseif ($item['type'] === 'permission')
                    {{-- セクション内のアイテムを収集 --}}
                    @php
                        $sectionItems[] = $item;
                    @endphp
                @endif
            @endforeach
            
            {{-- 最後のセクションを出力 --}}
            @if ($currentSection !== null)
                @include('admin.members.partials.roles-permission-group', [
                    'sectionTitle' => $currentSection,
                    'sectionId' => 'section_' . md5($currentSection),
                    'items' => $sectionItems,
                    'permissions' => $permissions,
                    'roles' => $roles
                ])
            @endif
        </div>
    </div>
    
    {{-- プラグインの権限設定 --}}
    @if (!empty($pluginPermissionGroups))
        <div class="permission-section-wrapper">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30">
                    <i class="fas fa-puzzle-piece text-purple-600 dark:text-purple-400"></i>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('admin.members.roles.plugin_permissions') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.members.roles.plugin_permissions_description') }}</p>
                </div>
            </div>
            
            <div class="permission-groups space-y-6 mb-10" x-data="{ openSections: {} }">
                @foreach ($pluginPermissionGroups as $pluginGroup)
                    @include('admin.members.partials.roles-plugin-permission-group', [
                        'pluginGroup' => $pluginGroup,
                        'pluginPermissions' => $pluginPermissions[$pluginGroup['slug']] ?? collect(),
                        'roles' => $roles
                    ])
                @endforeach
            </div>
        </div>
    @endif
</form>

@endsection


@section('save')
    <!-- 更新ボタンとモーダル-->
    <x-save
        id="confirmationModal"
        :label="__('common.save')"
        :title="__('common.save_confirmation_title')"
        :message="__('common.save_confirmation_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="member-roles-form"
    />
@endsection

@push('styles')
<style>
/* Permission Management Styles */
.permission-management {
    .permission-form {
        max-width: none;
    }

    .permission-groups {
        display: grid;
        gap: 1.5rem;
    }

    .permission-group {
        transition: box-shadow 0.2s ease-in-out;

        &:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        &__header {
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;

            .dark & {
                border-bottom-color: #374151;
            }
        }

        &__title {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        &__key {
            font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace;
            background-color: #f3f4f6;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;

            .dark & {
                background-color: #374151;
            }
        }

        &__content {
            gap: 2rem;

            @media (max-width: 768px) {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }
    }

    .permission-section {
        &__title {
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #374151;

            .dark & {
                color: #d1d5db;
            }
        }

        &__options {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
    }

    .permission-section-header {
        &__title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;

            .dark & {
                color: #f9fafb;
            }
        }
    }
}
</style>
@endpush


