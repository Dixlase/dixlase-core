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
    <section>
        <h2>{{ __('admin.settings.members.index.search_title') }}</h2>
        
        <form action="{{ route('admin.settings.members.index') }}" method="GET">
            <fieldset>
                <legend class="sr-only">{{ __('admin.settings.members.index.search_title') }}</legend>
                <div class="search-form">
                    @include('components::form.text', [
                        'name' => 'search',
                        'placeholder' => __('admin.settings.members.index.search_placeholder'),
                        'value' => $search,
                    ])

                    @include('components::form.button', [
                        'type' => 'submit',
                        'label' => __('admin.settings.members.index.search_button'),
                    ])
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
            'totalLabel' => 'admin.common.total_count',
            'perPageLabel' => 'admin.common.per_page_label'
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
                        <th>{{ __('admin.settings.members.index.table.id') }}</th>
                        <th>{{ __('admin.settings.members.index.table.name') }}</th>
                        <th>{{ __('admin.settings.members.index.table.email') }}</th>
                        <th>{{ __('admin.settings.members.index.table.role') }}</th>
                        <th>{{ __('admin.settings.members.index.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($members as $member)
                        <tr>
                            <td data-label="{{ __('admin.settings.members.index.table.id') }}">{{ $member->id }}</td>
                            <td data-label="{{ __('admin.settings.members.index.table.name') }}">{{ $member->name }}</td>
                            <td data-label="{{ __('admin.settings.members.index.table.email') }}">{{ $member->email }}</td>
                            <td data-label="{{ __('admin.settings.members.index.table.role') }}">{{ $member->role->label() }}</td>
                            <td data-label="{{ __('admin.settings.members.index.table.actions') }}">
                                <a href="{{ route('admin.settings.members.edit', ['member' => $member->id]) }}" 
                                   title="{{ __('admin.settings.members.index.table.edit') }}"
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
            'totalLabel' => 'admin.common.total_count',
            'perPageLabel' => 'admin.common.per_page_label'
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
    </section>
@endsection

