{{--
This file is part of Dixlase Legal.

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
    <form id="legal-pages-form" action="{{ route('dixlase-legal::admin.legal-pages.update') }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="space-y-6">
            @foreach ($pages as $slug => $page)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                            <i class="{{ $page['icon'] }} text-gray-600 dark:text-gray-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $page['name'] }}</h3>
                                @if ($page['required'])
                                    <x-ui-status-badge
                                        :label="__('dixlase-legal::admin/legal-pages/index.required_badge')"
                                        variant="danger"
                                        size="xs"
                                    />
                                @else
                                    <x-ui-status-badge
                                        :label="__('dixlase-legal::admin/legal-pages/index.optional_badge')"
                                        variant="gray"
                                        size="xs"
                                    />
                                @endif
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $page['description'] }}</p>
                        </div>
                    </div>

                    <x-form-text
                        :id="'url-' . $slug"
                        :name="'urls[' . $slug . ']'"
                        type="url"
                        :value="$page['url']"
                        :placeholder="__('dixlase-legal::admin/legal-pages/index.url_placeholder')"
                    />
                    <x-form-error :name="'urls.' . $slug" />
                </div>
            @endforeach
        </div>
    </form>
@endsection

@section('save')
    <x-admin.save-button
        id_confirmation="confirmLegalPagesModal"
        :label="__('common.save')"
        :title="__('dixlase-legal::admin/legal-pages/index.confirm_title')"
        :message="__('dixlase-legal::admin/legal-pages/index.confirm_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="legal-pages-form"
    />
@endsection
