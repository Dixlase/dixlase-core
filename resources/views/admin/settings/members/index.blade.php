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
    <!-- 検索セクション -->
    <section class="mb-6">
        <h2 class="text-lg font-semibold mb-4">{{ __('admin.settings.members.index.search_title') }}</h2>
        
        <form action="{{ route('admin.settings.members.index') }}" method="GET" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <fieldset>
                <legend class="sr-only">{{ __('admin.settings.members.index.search_title') }}</legend>
                
                <!-- 検索フィールド -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <!-- キーワード検索 -->
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('components.filters.search_keyword') }}
                        </label>
                        @include('components::form.text', [
                            'id' => 'search',
                            'name' => 'search',
                            'placeholder' => __('components.forms.placeholder.search'),
                            'value' => $search,
                        ])
                    </div>

                    <!-- 権限フィルター -->
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('components.filters.role_filter') }}
                        </label>
                        @include('components::form.select', [
                            'id' => 'role',
                            'name' => 'role',
                            'options' => [
                                '' => __('common.filters.all_roles'),
                                '0' => __('common.roles.super_admin'),
                                '1' => __('common.roles.admin'),
                                '2' => __('common.roles.editor'),
                                '3' => __('common.roles.author'),
                                '4' => __('common.roles.contributor'),
                                '5' => __('common.roles.receptionist'),
                            ],
                            'value' => $roleFilter,
                        ])
                    </div>

                    <!-- ステータスフィルター -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('components.filters.status_filter') }}
                        </label>
                        @include('components::form.select', [
                            'id' => 'status',
                            'name' => 'status',
                            'options' => [
                                '' => __('common.filters.all_statuses'),
                                '1' => __('common.status.active'),
                                '0' => __('common.status.inactive'),
                            ],
                            'value' => $statusFilter,
                        ])
                    </div>

                    <!-- 検索ボタン -->
                    <div class="flex items-end">
                        <div class="flex gap-2 w-full">
                            @include('components::form.button', [
                                'type' => 'submit',
                                'label' => __('common.search'),
                                'class' => 'flex-1'
                            ])
                            <a href="{{ route('admin.settings.members.index') }}" 
                               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 flex items-center justify-center">
                                {{ __('components.filters.clear_button') }}
                            </a>
                        </div>
                    </div>
                </div>
            </fieldset>
        </form>
    </section>

    <!-- メンバー一覧セクション -->
    <section>
        <h2 class="sr-only">{{ __('admin.settings.members.index.heading') }}</h2>

        <!-- ページネーション制御 -->
        @include('components::pagination-controls', [
            'paginator' => $members,
            'currentPerPage' => request('per_page', 25),
            'totalLabel' => 'components.pagination.total_count',
            'perPageLabel' => 'components.pagination.per_page_label'
        ])

        <!-- ページネーション -->
        @include('components::pagination', [
            'pagination' => $pagination ?? null,
            'route' => 'admin.settings.members.index',
            'routeParams' => array_filter([
                'search' => request('search'),
                'per_page' => request('per_page')
            ]),
            'mobilePageRange' => 0,
            'desktopPageRange' => 2
        ])

        <!-- レスポンシブテーブル -->
        <div class="responsive-table">
            <table>
                <caption class="sr-only">{{ __('admin.settings.members.index.table.caption') }}</caption>
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
                            <td data-label="{{ __('common.name') }}">{{ $member->name }}</td>
                            <td data-label="{{ __('common.email') }}">{{ $member->email }}</td>
                            <td data-label="{{ __('common.role') }}">{{ $member->role->label() }}</td>
                            <td data-label="{{ __('common.actions') }}">
                                <a href="{{ route('admin.settings.members.edit', ['member' => $member->id]) }}" 
                                   title="{{ __('common.edit') }}"
                                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- ページネーション制御 -->
        @include('components::pagination-controls', [
            'paginator' => $members,
            'currentPerPage' => request('per_page', 25),
            'totalLabel' => 'components.pagination.total_count',
            'perPageLabel' => 'components.pagination.per_page_label'
        ])
        

        <!-- ページネーション -->
        @include('components::pagination', [
            'pagination' => $pagination ?? null,
            'route' => 'admin.settings.members.index',
            'routeParams' => array_filter([
                'search' => request('search'),
                'role' => request('role'),
                'status' => request('status'),
                'per_page' => request('per_page')
            ]),
            'mobilePageRange' => 0,
            'desktopPageRange' => 2
        ])
    </section>
@endsection

