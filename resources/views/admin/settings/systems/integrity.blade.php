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
      (see LICENSE.commercial, or contact info@dixlase.org).

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
    <div class="max-w-4xl mx-auto px-4 py-8 space-y-6">

        {{-- Status --}}
        <section class="bg-white dark:bg-gray-800 shadow rounded-2xl overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                <h1 class="text-lg font-semibold text-gray-800 dark:text-white">{{ __('admin/settings/systems/integrity.title') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('admin/settings/systems/integrity.lead') }}</p>
            </div>
            <div class="px-6 py-5 space-y-4">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-medium {{ $badge['classes'] }}">
                    <i class="{{ $badge['icon'] }}"></i>
                    {{ __('admin/settings/systems/integrity.status.' . $badge['key']) }}
                </span>

                <p class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin/settings/systems/integrity.desc.' . $badge['key']) }}</p>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                    @if (! empty($result['version']))
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/systems/integrity.field.version') }}</dt>
                            <dd class="text-gray-800 dark:text-gray-100 break-all">{{ $result['version'] }}</dd>
                        </div>
                    @endif
                    @if (! empty($result['key_id']))
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/systems/integrity.field.key_id') }}</dt>
                            <dd class="text-gray-800 dark:text-gray-100 break-all">{{ $result['key_id'] }}</dd>
                        </div>
                    @endif
                    @if (! empty($result['signed_at']))
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/systems/integrity.field.signed_at') }}</dt>
                            <dd class="text-gray-800 dark:text-gray-100 break-all">{{ $result['signed_at'] }}</dd>
                        </div>
                    @endif
                </dl>

                <form method="POST" action="{{ route('admin.settings.systems.integrity.recheck') }}">
                    @csrf
                    <x-form-button
                        type="submit"
                        variant="secondary"
                        size="sm"
                        icon="fas fa-sync"
                        :label="__('admin/settings/systems/integrity.recheck')"
                    />
                </form>
            </div>
        </section>

        {{-- Changed files --}}
        @if (! empty($changedFiles))
            <section class="bg-white dark:bg-gray-800 shadow rounded-2xl overflow-hidden">
                <div class="px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/40">
                    <h2 class="text-base font-semibold text-gray-800 dark:text-white">
                        {{ __('admin/settings/systems/integrity.changed_files_title', ['count' => count($changedFiles)]) }}
                    </h2>
                </div>
                <ul class="divide-y divide-gray-100 dark:divide-gray-700 text-sm font-mono">
                    @foreach ($changedFiles as $row)
                        <li class="px-6 py-2 flex items-center gap-3">
                            <span class="w-5 text-center font-bold text-gray-500 dark:text-gray-400">{{ $row['marker'] }}</span>
                            <span class="text-xs uppercase tracking-wide text-gray-400">{{ $row['label'] }}</span>
                            <span class="text-gray-800 dark:text-gray-100 break-all">{{ $row['path'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Active waiver --}}
        @if ($waiver)
            <section class="bg-white dark:bg-gray-800 shadow rounded-2xl overflow-hidden border-l-4 border-yellow-400">
                <div class="px-6 py-5">
                    <h2 class="text-base font-semibold text-gray-800 dark:text-white">{{ __('admin/settings/systems/integrity.waiver_active.title') }}</h2>
                    <dl class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-2 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/systems/integrity.waiver_active.reason') }}</dt>
                            <dd class="text-gray-800 dark:text-gray-100 break-words">{{ $waiver['reason'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/systems/integrity.waiver_active.by') }}</dt>
                            <dd class="text-gray-800 dark:text-gray-100">{{ $waiver['by'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/systems/integrity.waiver_active.at') }}</dt>
                            <dd class="text-gray-800 dark:text-gray-100">{{ $waiver['at'] ?? '-' }}</dd>
                        </div>
                    </dl>

                    <form id="core-unwaive-form" method="POST" action="{{ route('admin.settings.systems.integrity.unwaive') }}" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                    <x-form-button
                        variant="secondary"
                        size="sm"
                        icon="fas fa-undo"
                        class="mt-4"
                        :label="__('admin/settings/systems/integrity.unwaive.button')"
                        @click="openModal('coreUnwaiveModal')"
                    />
                </div>
            </section>

            <x-ui-modal
                id="coreUnwaiveModal"
                :title="__('admin/settings/systems/integrity.unwaive.confirm_title')"
                :message="__('admin/settings/systems/integrity.unwaive.confirm_message')"
                :confirm-label="__('admin/settings/systems/integrity.unwaive.confirm_button')"
                :cancel-label="__('common.cancel')"
                form="core-unwaive-form"
                icon-type="info"
                confirm-color="blue"
            />
        @endif

        {{-- Danger zone --}}
        <section class="bg-white dark:bg-gray-800 shadow rounded-2xl overflow-hidden border border-red-200 dark:border-red-900">
            <div class="px-6 py-3 border-b border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-900/20">
                <h2 class="text-base font-semibold text-red-700 dark:text-red-300">{{ __('admin/settings/systems/integrity.danger.title') }}</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('admin/settings/systems/integrity.danger.lead') }}</p>
            </div>
            <div class="px-6 py-5 space-y-8">
                @if (! $gateOpen)
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin/settings/systems/integrity.danger.disabled_notice') }}</p>
                @else
                    @unless ($waiver)
                        <div>
                            <h3 class="font-semibold text-gray-800 dark:text-white">{{ __('admin/settings/systems/integrity.waive.title') }}</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('admin/settings/systems/integrity.waive.lead') }}</p>
                            <form id="core-waive-form" method="POST" action="{{ route('admin.settings.systems.integrity.waive') }}" class="mt-3 space-y-2">
                                @csrf
                                <x-form-label
                                    for="core-waive-reason"
                                    :text="__('admin/settings/systems/integrity.waive.reason_label')"
                                    required
                                />
                                <x-form-textarea
                                    id="core-waive-reason"
                                    name="reason"
                                    :rows="2"
                                    required
                                    :value="old('reason', '')"
                                    :placeholder="__('admin/settings/systems/integrity.waive.reason_placeholder')"
                                />
                                <x-form-error :messages="$errors->get('reason')" />
                            </form>
                            <x-form-button
                                variant="warning"
                                size="sm"
                                icon="fas fa-user-shield"
                                class="mt-2"
                                :label="__('admin/settings/systems/integrity.waive.button')"
                                @click="openModal('coreWaiveModal')"
                            />
                        </div>

                        <x-ui-modal
                            id="coreWaiveModal"
                            :title="__('admin/settings/systems/integrity.waive.confirm_title')"
                            :message="__('admin/settings/systems/integrity.waive.confirm_message')"
                            :confirm-label="__('admin/settings/systems/integrity.waive.confirm_button')"
                            :cancel-label="__('common.cancel')"
                            form="core-waive-form"
                            icon-type="warning"
                            confirm-color="yellow"
                        />
                    @endunless

                    <div>
                        <h3 class="font-semibold text-gray-800 dark:text-white">{{ __('admin/settings/systems/integrity.remove.title') }}</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('admin/settings/systems/integrity.remove.lead') }}</p>
                        <form id="core-remove-form" method="POST" action="{{ route('admin.settings.systems.integrity.remove') }}" class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                        <x-form-button
                            variant="danger"
                            size="sm"
                            icon="fas fa-trash"
                            class="mt-2"
                            :label="__('admin/settings/systems/integrity.remove.button')"
                            @click="openModal('coreRemoveModal')"
                        />
                    </div>

                    <x-ui-modal
                        id="coreRemoveModal"
                        :title="__('admin/settings/systems/integrity.remove.confirm_title')"
                        :message="__('admin/settings/systems/integrity.remove.confirm_message')"
                        :confirm-label="__('admin/settings/systems/integrity.remove.confirm_button')"
                        :cancel-label="__('common.cancel')"
                        form="core-remove-form"
                        icon-type="danger"
                        confirm-color="red"
                    />
                @endif
            </div>
        </section>

    </div>
@endsection
