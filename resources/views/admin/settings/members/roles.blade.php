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

@extends('admin::partials.layout')

@section('content')

<form method="POST" action="{{ route('admin.settings.members.roles.update') }}" id="member-roles-form" class="permission-management permission-form" novalidate>
    @csrf
    <div class="permission-groups space-y-6 mb-10" x-data="{ openSections: {} }">
        @php
            $currentSection = null;
            $sectionItems = [];
        @endphp
        
        @foreach ($permissionItems as $index => $item)
            @if ($item['type'] === 'heading')
                {{-- 前のセクションがあれば出力 --}}
                @if ($currentSection !== null)
                    @include('admin.settings.members.partials.roles-permission-group', [
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
            @include('admin.settings.members.partials.roles-permission-group', [
                'sectionTitle' => $currentSection,
                'sectionId' => 'section_' . md5($currentSection),
                'items' => $sectionItems,
                'permissions' => $permissions,
                'roles' => $roles
            ])
        @endif
    </div>
</form>

@endsection


@section('save')
    <!-- 更新ボタンとモーダル-->
    @include('components::form.save', [
        'id' => 'confirmationModal',
        'label' => __('admin.settings.members.roles.confirm_label'),
        'onclick' => "openModal('confirmationModal')",
        'title' => __('admin.settings.members.roles.confirm_title'),
        'message' => __('admin.settings.members.roles.confirm_message'),
        'confirm_label' => __('admin.settings.members.roles.confirm_label'),
        'cancel_label' => __('admin.settings.members.roles.cancel_label'),
        'form' => 'member-roles-form',
    ])
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

    .permission-option {
        cursor: pointer;
        border-radius: 0.375rem;
        padding: 0.5rem;
        transition: background-color 0.15s ease-in-out;

        &:hover {
            background-color: #f9fafb;

            .dark & {
                background-color: #374151;
            }
        }

        &__label {
            font-weight: 500;
            user-select: none;
        }
    }

    .permission-checkbox {
        width: 1rem;
        height: 1rem;
        border-radius: 0.25rem;
        border: 1px solid #d1d5db;
        background-color: #ffffff;
        color: #4f46e5;
        flex-shrink: 0;

        &:focus {
            outline: 2px solid transparent;
            outline-offset: 2px;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            border-color: #4f46e5;
        }

        &:checked {
            background-color: #4f46e5;
            border-color: #4f46e5;
        }

        .dark & {
            border-color: #6b7280;
            background-color: #374151;

            &:checked {
                background-color: #4f46e5;
                border-color: #4f46e5;
            }
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


