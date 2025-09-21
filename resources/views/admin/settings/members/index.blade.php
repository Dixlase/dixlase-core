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

    <h3 class="text-lg font-semibold">{{ __('admin.settings.members.index.search_title') }}</h3>

    <!-- {{ __('admin.settings.members.index.search_title') }} -->
    <form action="{{ route('admin.settings.members.index') }}" method="GET" class="mb-6">
        <div class="flex items-center">
            @csrf
            @include('components::form.text', [
                'name' => 'search',
                'placeholder' => __('admin.settings.members.index.search_placeholder'),
                'value' => $search,

            ])

            @include('components::form.button', [
                'type' => "submit",
                'label' => __('admin.settings.members.index.search_button'),
            ])

        </div>
    </form>

    <!-- {{ __('admin.settings.members.index.heading') }} -->
    <div class="shadow-md rounded p-6">

        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm text-left rtl:text-right">
                <thead class="text-xs uppercase {{ config('appearance.appearance_class.table.header') }}">
                    <tr>
                        <th class="border px-4 py-2">{{ __('admin.settings.members.index.table.id') }}</th>
                        <th class="border px-4 py-2">{{ __('admin.settings.members.index.table.name') }}</th>
                        <th class="border px-4 py-2">{{ __('admin.settings.members.index.table.email') }}</th>
                        <th class="border px-4 py-2">{{ __('admin.settings.members.index.table.role') }}</th>
                        <th class="border px-4 py-2">{{ __('admin.settings.members.index.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($members as $member)
                        <tr class="{{ config('appearance.appearance_class.table.row') }}">
                            <td class="border px-4 py-2">{{ $member->id }}</td>
                            <td class="border px-4 py-2">{{ $member->name }}</td>
                            <td class="border px-4 py-2">{{ $member->email }}</td>
                            <td class="border px-4 py-2">{{ $member->role->label() }}</td>
                            <td class="border px-4 py-2">
                                <a href="{{ route('admin.settings.members.edit', ['member' => $member->id]) }}"
                                    class="{{ config('appearance.appearance_class.link') }}">
                                    {{ __('admin.settings.members.index.table.edit') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="block md:hidden">
            @foreach ($members as $member)
                <div class="border rounded-lg p-4 mb-4 shadow">
                    <p><strong>{{ __('admin.settings.members.index.table.id') }}:</strong> {{ $member->id }}</p>
                    <p><strong>{{ __('admin.settings.members.index.table.name') }}:</strong> {{ $member->name }}</p>
                    <p><strong>{{ __('admin.settings.members.index.table.email') }}:</strong> {{ $member->email }}</p>

                    <p><strong>{{ __('admin.settings.members.index.table.role') }}:</strong>{{ ($member->role instanceof \App\Enums\MemberRole ? $member->role : \App\Enums\MemberRole::tryFrom($member->role))?->label() ?? __('admin.settings.members.index.table.unknown_role') }}</p>
                    <div class="mt-2">
                        <a href="{{ route('admin.settings.members.edit', ['member' => $member->id]) }}"
                            class="text-blue-600 hover:text-blue-800">
                            {{ __('admin.settings.members.index.table.edit') }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $members->links() }}
        </div>
    </div>
@endsection
