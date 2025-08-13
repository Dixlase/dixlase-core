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
    <form method="POST" action="{{ route('admin.settings.members.roles.update') }}" id="member-roles-form">
        @csrf

        <div class="space-y-8">
            @php
                function renderMenuPermissions($menuList, $permissions, $roles, $parentKey = '')
                {
                    foreach ($menuList as $key => $item) {
                        $menuKey = $parentKey ? $parentKey . '.' . $key : $key;

                        // ✨ 権限設定対象かどうか判定
                        $isTarget = isset($item['route']) && !in_array($menuKey, ['dashboard', 'front', 'media', 'settings']);

                        // ✨ 見出しだけ表示すべき親メニューかどうか
                        $isHeadingOnly = !$isTarget && isset($item['children']) && !in_array($menuKey, ['dashboard']);

                        if ($isHeadingOnly) {
                            echo '<div class="mt-8 mb-4">';
                            echo '<h3>' . __($item['text']) . '</h3>';
                            echo '</div>';
                        }

                        if ($isTarget) {
                            echo '<div class="border p-4 rounded">';
                            echo '<h4>' . __($item['text']) . '（' . $menuKey . '）</h4>';

                            echo '<div class="grid grid-cols-2 gap-6">';

                            // 編集権限
                            echo '<div>';
                            echo '<h5 class="text-sm font-medium mb-2">' . __('admin.settings.members.roles.access_roles') . '</h5>';
                            foreach ($roles as $role) {
                                if ($role->value !== \App\Enums\MemberRole::SUPER_ADMIN->value) {
                                    $checked = in_array($role->value, $permissions[$menuKey]->access_roles ?? []) ? 'checked' : '';
                                    echo '<label class="inline-flex items-center mr-4 mb-2">';
                                    echo '<input type="checkbox" name="permissions[' . $menuKey . '][access_roles][]" value="' . $role->value . '" ' . $checked . '>';
                                    echo '<span class="ml-2">' . $role->label() . '</span>';
                                    echo '</label>';
                                }
                            }
                            echo '</div>';

                            // 閲覧権限
                            echo '<div>';
                            echo '<h5 class="text-sm font-medium mb-2">' . __('admin.settings.members.roles.view_roles') . '</h5>';
                            foreach ($roles as $role) {
                                if ($role->value !== \App\Enums\MemberRole::SUPER_ADMIN->value) {
                                    $checked = in_array($role->value, $permissions[$menuKey]->view_roles ?? []) ? 'checked' : '';
                                    echo '<label class="inline-flex items-center mr-4 mb-2">';
                                    echo '<input type="checkbox" name="permissions[' . $menuKey . '][view_roles][]" value="' . $role->value . '" ' . $checked . '>';
                                    echo '<span class="ml-2">' . $role->label() . '</span>';
                                    echo '</label>';
                                }
                            }
                            echo '</div>';

                            echo '</div>'; // grid

                            echo '</div>'; // card
                        }

                        // 子メニューがあるなら必ず潜る
                        if (isset($item['children'])) {
                            renderMenuPermissions($item['children'], $permissions, $roles, $menuKey);
                        }
                    }
                }
            @endphp

            {{-- 実行 --}}
            @php
                renderMenuPermissions($menuList, $permissions, $roles);
            @endphp
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


