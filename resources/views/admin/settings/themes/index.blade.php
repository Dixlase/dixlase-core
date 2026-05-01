{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see LICENSE
      for full exception terms); or

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
<div class="mx-auto">
    {{-- インストール済みテーマ一覧セクション --}}
    <section>
        <div class="flex items-center justify-between mb-6">
            <h2 class="mb-0">{{ __('admin/settings/themes/index.installed_heading') }}</h2>
            <div class="flex items-center gap-3">
                <div x-data="{
                    checking: false,
                    async check() {
                        this.checking = true;
                        try {
                            const response = await fetch('{{ route('admin.settings.themes.check-updates') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                },
                            });
                            const data = await response.json();
                            if (data.success) {
                                window.location.reload();
                            }
                        } catch (e) {}
                        this.checking = false;
                    }
                }">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors disabled:opacity-50"
                        :disabled="checking"
                        @click="check()"
                    >
                        <i class="fas" :class="checking ? 'fa-spinner fa-spin' : 'fa-sync-alt'"></i>
                        <span x-text="checking ? '{{ __('admin/settings/themes/index.updates.checking') }}' : '{{ __('admin/settings/themes/index.updates.check') }}'"></span>
                    </button>
                </div>

                {{-- 全テーマ再スキャンボタン --}}
                <form action="{{ route('admin.settings.themes.audit-all') }}" method="POST" class="inline-block" id="bulkAuditThemesForm">
                    @csrf
                    <x-form-button
                        type="button"
                        :label="__('admin/settings/themes/index.audit.audit_all_button')"
                        variant="secondary"
                        icon="fas fa-search"
                        @click="openModal('bulkAuditThemesModal')"
                    />

                    <x-ui-modal
                        id="bulkAuditThemesModal"
                        :title="__('admin/settings/themes/index.audit.audit_all_confirm_title')"
                        :message="__('admin/settings/themes/index.audit.audit_all_confirm_message')"
                        icon_type="info"
                        confirm_color="blue"
                        :confirm_label="__('admin/settings/themes/index.audit.audit_all_button')"
                        :cancel_label="__('common.cancel')"
                        form="bulkAuditThemesForm"
                    />
                </form>

                {{-- すべて更新ボタン（更新可能なテーマがある時のみ） --}}
                @if(count($updatableExtensions) > 0)
                    <form action="{{ route('admin.settings.themes.update-all') }}" method="POST" class="inline-block" id="bulkUpdateThemesForm">
                        @csrf
                        <x-form-button
                            type="button"
                            :label="__('admin/settings/themes/index.updates.update_all_button')"
                            variant="primary"
                            icon="fas fa-cloud-download-alt"
                            @click="openModal('bulkUpdateThemesModal')"
                        />
                    </form>

                    <x-ui-modal
                        id="bulkUpdateThemesModal"
                        :title="__('admin/settings/themes/index.updates.update_all_confirm_title')"
                        :message="__('admin/settings/themes/index.updates.update_all_confirm_message', ['count' => count($updatableExtensions)])"
                        icon_type="info"
                        confirm_color="blue"
                        :confirm_label="__('admin/settings/themes/index.updates.update_all_button')"
                        :cancel_label="__('common.cancel')"
                        form="bulkUpdateThemesForm"
                    />
                @endif

                <a href="{{ route('admin.settings.themes.add') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    {{ __('admin/settings/themes/index.add_theme') }}
                </a>
            </div>
        </div>

        @if($themes->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach ($themeCards as $card)
                    @include('admin.settings.themes.partials.theme-card', ['card' => $card])
                @endforeach
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                <div class="max-w-md mx-auto">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                        <i class="fas fa-palette text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ __('admin/settings/themes/index.no_themes') }}</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">{{ __('admin/settings/themes/index.no_themes_description') }}</p>
                    <a href="{{ route('admin.settings.themes.add') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        {{ __('admin/settings/themes/index.add_theme') }}
                    </a>
                </div>
            </div>
        @endif
    </section>

    {{-- アンインストール済みテーマ一覧セクション --}}
    @if(count($uninstalledThemes ?? []) > 0)
    <section class="mt-12">
        <h2>{{ __('admin/settings/themes/index.uninstalled_heading') }}</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6">{{ __('admin/settings/themes/index.uninstalled_description') }}</p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach ($uninstalledThemeCards as $card)
                @include('admin.settings.themes.partials.theme-card', ['card' => $card])
            @endforeach
        </div>
    </section>
    @endif
</div>

@endsection

{{-- 監査スクリプト --}}
@include('admin.settings.themes.partials.audit-script')
