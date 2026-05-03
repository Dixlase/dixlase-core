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
<div class="max-w-4xl mx-auto">
    <header class="mb-6">
        <h1 class="text-2xl font-semibold mb-2">{{ __('admin/privacy/users.heading') }}</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/privacy/users.description') }}</p>
    </header>

    @if (session('status'))
        <div class="mb-4 p-4 rounded border border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/30 text-sm">
            {{ session('status') }}
            @if (session('errors_detail'))
                <ul class="mt-2 list-disc list-inside text-xs">
                    @foreach (session('errors_detail') as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 p-4 rounded border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/30 text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Search --}}
    <section class="mb-6">
        <form action="{{ route('admin.privacy.users.index') }}" method="GET"
              class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                {{ __('admin/privacy/users.search.label') }}
            </label>
            <div class="flex gap-2">
                <x-form-text id="search" name="search" :value="$search"
                             :placeholder="__('admin/privacy/users.search.placeholder')" />
                <x-form-button type="submit" :label="__('admin/privacy/users.search.submit')" variant="primary" />
            </div>
            <p class="mt-2 text-xs text-gray-500">{{ __('admin/privacy/users.search.help') }}</p>
        </form>
    </section>

    @if ($search !== '' && $member === null)
        <div class="mb-4 p-4 rounded border border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/30 text-sm">
            {{ __('admin/privacy/users.search.not_found') }}
        </div>
    @endif

    @if ($member !== null)
        {{-- Subject summary --}}
        <section class="mb-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <h2 class="text-lg font-semibold mb-3">{{ __('admin/privacy/users.subject.heading') }}</h2>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-y-2 text-sm">
                <dt class="text-gray-500">{{ __('admin/privacy/users.subject.id') }}</dt>
                <dd>{{ $member->id }}</dd>
                <dt class="text-gray-500">{{ __('admin/privacy/users.subject.email') }}</dt>
                <dd>{{ $member->email }}</dd>
                <dt class="text-gray-500">{{ __('admin/privacy/users.subject.account_name') }}</dt>
                <dd>{{ $member->account_name }}</dd>
                <dt class="text-gray-500">{{ __('admin/privacy/users.subject.deleted_at') }}</dt>
                <dd>{{ $member->deleted_at ?? '—' }}</dd>
            </dl>
        </section>

        {{-- Export --}}
        <section class="mb-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <h2 class="text-lg font-semibold mb-3">{{ __('admin/privacy/users.export.heading') }}</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                {{ __('admin/privacy/users.export.description') }}
            </p>
            <div class="flex gap-2">
                <a href="{{ route('admin.privacy.users.export', ['id' => $member->id, 'scope' => 'site']) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm">
                    <i class="fas fa-download"></i>
                    {{ __('admin/privacy/users.export.download_site') }}
                </a>
                <a href="{{ route('admin.privacy.users.export', ['id' => $member->id, 'scope' => 'network']) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm">
                    <i class="fas fa-globe"></i>
                    {{ __('admin/privacy/users.export.download_network') }}
                </a>
            </div>
        </section>

        {{-- Delete --}}
        <section class="bg-white dark:bg-gray-800 rounded-lg border border-red-300 dark:border-red-800 p-4"
                 x-data="{ confirmed: false }">
            <h2 class="text-lg font-semibold text-red-700 dark:text-red-400 mb-3">
                {{ __('admin/privacy/users.delete.heading') }}
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                {{ __('admin/privacy/users.delete.description') }}
            </p>

            <form action="{{ route('admin.privacy.users.delete', ['id' => $member->id]) }}" method="POST"
                  x-on:submit="if (!confirm('{{ __('admin/privacy/users.delete.confirm_prompt') }}')) $event.preventDefault();">
                @csrf

                <fieldset class="mb-4">
                    <legend class="text-sm font-medium mb-2">{{ __('admin/privacy/users.delete.scope_label') }}</legend>
                    @foreach ($scopeOptions as $value => $label)
                        <label class="block text-sm">
                            <input type="radio" name="scope" value="{{ $value }}"
                                   {{ $value === 'site' ? 'checked' : '' }}
                                   class="mr-2">
                            {{ $label }}
                        </label>
                    @endforeach
                </fieldset>

                <fieldset class="mb-4">
                    <legend class="text-sm font-medium mb-2">{{ __('admin/privacy/users.delete.mode_label') }}</legend>
                    @foreach ($deletionModeOptions as $value => $label)
                        <label class="block text-sm">
                            <input type="radio" name="mode" value="{{ $value }}"
                                   {{ $value === 'anonymize' ? 'checked' : '' }}
                                   class="mr-2">
                            {{ $label }}
                        </label>
                    @endforeach
                </fieldset>

                <label class="block text-sm mb-4">
                    <input type="checkbox" name="confirm" value="1" x-model="confirmed" class="mr-2">
                    {{ __('admin/privacy/users.delete.confirm_checkbox') }}
                </label>

                <button type="submit"
                        x-bind:disabled="!confirmed"
                        x-bind:class="confirmed ? 'bg-red-600 hover:bg-red-700' : 'bg-gray-400 cursor-not-allowed'"
                        class="inline-flex items-center gap-2 px-4 py-2 text-white rounded text-sm">
                    <i class="fas fa-user-slash"></i>
                    {{ __('admin/privacy/users.delete.submit') }}
                </button>
            </form>
        </section>
    @endif
</div>
@endsection
