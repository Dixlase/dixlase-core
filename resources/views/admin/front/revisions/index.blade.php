{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
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
<div class="mx-auto">
    <div class="mb-4">
        <a href="{{ route('admin.front.edit') }}" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
            <i class="fas fa-arrow-left"></i>
            {{ __('admin/front.revisions.back_to_edit') }}
        </a>
    </div>

    @if ($revisions->isEmpty())
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/front.revisions.no_revisions') }}</p>
        </div>
    @else
        <section>
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin/front.revisions.created_at') }}</th>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin/front.revisions.type') }}</th>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin/front.revisions.creator') }}</th>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin/front.revisions.note') }}</th>
                        <th scope="col" class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin/front.revisions.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($revisions as $revision)
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                {{ $revision->created_at?->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                <span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                    {{ $typeLabels[$revision->type] ?? $revision->type }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">
                                {{ $revision->creator->display_name ?? $revision->creator->account_name ?? __('admin/front.revisions.unknown_user') }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ $revision->note }}
                            </td>
                            <td class="px-4 py-2 text-sm text-right whitespace-nowrap">
                                <a href="{{ route('admin.front.revisions.show', $revision->id) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                    {{ __('admin/front.revisions.view_diff') }}
                                </a>
                                <form action="{{ route('admin.front.revisions.restore', $revision->id) }}" method="POST" class="inline ml-3"
                                      @submit.prevent="if (window.confirm(@js(__('admin/front.revisions.restore_confirm_message')))) $event.target.submit();">
                                    @csrf
                                    <button type="submit" class="text-amber-600 hover:text-amber-800 dark:text-amber-400">
                                        {{ __('admin/front.revisions.restore') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4">
                {{ $revisions->links() }}
            </div>
        </section>
    @endif
</div>
@endsection
