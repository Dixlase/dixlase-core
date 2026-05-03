{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE.commercial, or contact office@exc-d.com).

Unless you have entered into a commercial license agreement, this
file is governed by the AGPL terms below.

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
<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-4">
        <x-form-button
            type="link"
            :href="route('admin.members.create')"
            :label="__('common.create')"
            variant="primary"
            icon="fas fa-plus"
        />
        
        <x-form-button
            type="button"
            :label="__('admin/members/index.force_logout_all')"
            variant="warning"
            icon="fas fa-sign-out-alt"
            @click="openModal('forceLogoutAllModal')"
        />
    </div>


    <!-- 検索セクション -->
    <section class="mb-6">
        <h2 class="text-lg font-semibold mb-4">{{ __('admin/members/index.search_title') }}</h2>
        
        <form action="{{ route('admin.members.index') }}" method="GET" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <fieldset>
                <legend class="sr-only">{{ __('admin/members/index.search_title') }}</legend>
                
                <!-- 検索フィールド -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 items-end gap-4 mb-4">
                    <!-- キーワード検索 -->
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('common.filters.search_keyword') }}
                        </label>
                        <x-form-text
                            id="search"
                            name="search"
                            :placeholder="__('components/form.placeholder.search')"
                            :value="$search"
                        />
                    </div>

                    <!-- 権限フィルター -->
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('common.filters.role_filter') }}
                        </label>
                        <x-form-select
                            id="role"
                            name="role"
                            :options="array_merge(
                                ['' => 'common.all'],
                                collect($roles)->mapWithKeys(fn($role) => [$role->value => $role->label()])->toArray()
                            )"
                            :value="$role ?? ''"
                        />
                    </div>

                    <!-- ステータスフィルター -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('common.filters.status_filter') }}
                        </label>
                        <x-form-select
                            id="status"
                            name="status"
                            :options="[
                                '' => 'common.all',
                                '1' => 'components/ui-status-badge.active',
                                '0' => 'components/ui-status-badge.inactive',
                            ]"
                            :value="$status ?? ''"
                        />
                    </div>

                    <!-- 検索ボタン -->
                    <div class="flex gap-2">
                        <x-form-button
                            type="submit"
                            variant="primary"
                            :label="__('common.search')"
                            icon="fas fa-search"
                        />
                        <a href="{{ route('admin.members.index') }}"
                           class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 flex items-center justify-center">
                            {{ __('common.filters.clear_button') }}
                        </a>
                    </div>
                </div>
            </fieldset>
        </form>
    </section>

    <!-- メンバー一覧セクション -->
    <section>
        <h2 class="sr-only">{{ __('admin/members/index.heading') }}</h2>

        <!-- ページネーション制御 -->
        <x-ui-pagination-controls
            :paginator="$members"
            :perPageOptions="[10, 25, 50, 100]"
            :currentPerPage="request('per_page', 25)"
            route="admin.members.index"
            :routeParams="array_filter([
                'search' => request('search'),
                'role' => request('role'),
                'status' => request('status')
            ])"
        />

        <!-- ページネーション -->
        <x-ui-pagination
            :pagination="$pagination ?? null"
            :route="'admin.members.index'"
            :routeParams="array_filter([
                'search' => request('search'),
                'role' => request('role'),
                'status' => request('status'),
                'per_page' => request('per_page')
            ])"
            :mobilePageRange="0"
            :desktopPageRange="2"
        />

        <!-- レスポンシブテーブル -->
        <div class="responsive-table !border-0 !dark:border-0">
            <table class="border rounded-sm ">
                <caption class="sr-only">{{ __('admin/members/index.table.caption') }}</caption>
                <thead>
                    <tr>
                        <th>{{ __('common.id') }}</th>
                        <th>{{ __('common.name') }}</th>
                        <th>{{ __('common.email') }}</th>
                        <th>{{ __('common.role') }}</th>
                        <th>{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($members as $member)
                        <tr>
                            <td data-label="{{ __('common.id') }}">{{ $member->id }}</td>
                            <td data-label="{{ __('common.account_name') }}">
                                <a href="{{ route('admin.members.edit', ['member' => $member->id]) }}" 
                                   class="hover:underline">
                                    {{ $member->display_name ?? $member->account_name }}
                                </a>
                                @if($member->display_name)
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block">{{ $member->account_name }}</span>
                                @endif
                            </td>
                            <td data-label="{{ __('common.email') }}">{{ $member->email }}</td>
                            <td data-label="{{ __('common.role') }}">{{ $member->role->label() }}</td>
                            <td data-label="{{ __('common.actions') }}">
                                <div class="flex items-center justify-end gap-2">
                                    <x-form-button
                                        type="link"
                                        variant="ghost"
                                        size="sm"
                                        icon="fas fa-edit"
                                        :href="route('admin.members.edit', ['member' => $member->id])"
                                        title="{{ __('common.edit') }}"
                                    />

                                    <form action="{{ route('admin.members.destroy', ['member' => $member->id]) }}" method="POST" id="deleteForm-{{ $member->id }}">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    <x-form-button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        icon="fas fa-trash"
                                        class="!text-red-600 hover:!text-red-900 dark:!text-red-400 dark:hover:!text-red-300"
                                        :xClick="'openModal(\'deleteModal-' . $member->id . '\')'"
                                        title="{{ __('common.delete') }}"
                                    />
                                    <x-ui-modal
                                        :id="'deleteModal-' . $member->id"
                                        :title="__('admin/members/index.delete_confirm_title')"
                                        :message="__('admin/members/index.delete_confirm_message', ['name' => $member->display_name ?? $member->account_name])"
                                        :confirm_label="__('common.delete')"
                                        :cancel_label="__('common.cancel')"
                                        icon_type="danger"
                                        confirm_color="red"
                                        :form="'deleteForm-' . $member->id"
                                    />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>        

        <!-- ページネーション -->
        <x-ui-pagination
            :pagination="$pagination ?? null"
            route="admin.members.index"
            :routeParams="array_filter([
                'search' => request('search'),
                'role' => request('role'),
                'status' => request('status'),
                'per_page' => request('per_page')
            ])"
            :mobilePageRange="0"
            :desktopPageRange="2"
        />
    </section>
</div>
@endsection

@section('modals')
    <!-- 全メンバー強制ログアウト確認モーダル -->
    <x-ui-modal
        id="forceLogoutAllModal"
        :title="__('admin/members/index.force_logout_all_confirmation_title')"
        :message="__('admin/members/index.force_logout_all_confirmation_message')"
        :confirm_label="__('admin/members/index.force_logout_all')"
        :cancel_label="__('common.cancel')"
        form="forceLogoutAllForm"
        icon_type="warning"
        confirm_color="yellow"
    />
    
    <form id="forceLogoutAllForm" method="POST" action="{{ route('admin.members.force-logout-all') }}" style="display: none;">
        @csrf
    </form>
@endsection
