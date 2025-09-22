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

<form method="POST" action="{{ route('admin.settings.members.roles.update') }}" id="member-roles-form" class="permission-form" novalidate>
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


