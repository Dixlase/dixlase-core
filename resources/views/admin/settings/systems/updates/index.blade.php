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
--}}

@extends('layouts.admin')

@section('content')
<div class="mx-auto max-w-5xl">
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">{{ __('admin/settings/systems/updates.description') }}</p>

    {{-- ヘッダー：最終チェック時刻と再チェックボタン --}}
    <div class="flex items-center justify-between gap-3 mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            @if($lastCheckedAtFormatted)
                {{ __('admin/settings/systems/updates.last_checked_at', ['date' => $lastCheckedAtFormatted]) }}
            @else
                {{ __('admin/settings/systems/updates.never_checked') }}
            @endif
        </div>
        <form method="POST" action="{{ route('admin.settings.systems.updates.check') }}">
            @csrf
            <x-form-button
                type="submit"
                :label="__('admin/settings/systems/updates.check_now')"
                variant="secondary"
                size="sm"
                icon="fas fa-sync-alt"
            />
        </form>
    </div>

    {{-- メインフォーム：選択 + 一括適用 --}}
    <form method="POST"
          action="{{ route('admin.settings.systems.updates.apply') }}"
          id="systemUpdatesApplyForm"
          x-data="{
              selectedCount: 0,
              recompute() {
                  this.selectedCount = this.$root.querySelectorAll('input[type=checkbox][data-update-target]:checked').length;
              },
          }"
          x-init="recompute()"
          @change="recompute()">
        @csrf

        {{-- コアセクション（プレースホルダ） --}}
        <section class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                <i class="fas fa-cube mr-2"></i>{{ __('admin/settings/systems/updates.core.heading') }}
            </h2>
            @if(! empty($core['current_version']))
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                    {{ __('admin/settings/systems/updates.core.current_version', ['version' => $core['current_version']]) }}
                </p>
            @endif
            <p class="text-xs text-gray-500 dark:text-gray-500 italic">
                <i class="fas fa-info-circle mr-1"></i>{{ __('admin/settings/systems/updates.core.not_implemented') }}
            </p>
        </section>

        {{-- プラグインセクション --}}
        <section class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                <i class="fas fa-plug mr-2"></i>{{ __('admin/settings/systems/updates.plugins.heading') }}
                @if(count($plugins) > 0)
                    <span class="inline-flex items-center px-2 py-0.5 ml-2 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                        {{ __('admin/settings/systems/updates.plugins.count', ['count' => count($plugins)]) }}
                    </span>
                @endif
            </h2>

            @if(count($plugins) === 0)
                <p class="text-sm text-gray-500 dark:text-gray-400 italic">{{ __('admin/settings/systems/updates.plugins.none') }}</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 pr-2 w-8">
                                <input type="checkbox"
                                       class="rounded border-gray-300 dark:border-gray-600"
                                       @change="$root.querySelectorAll('input[type=checkbox][data-update-target=plugin]').forEach(cb => cb.checked = $event.target.checked); recompute()"
                                       aria-label="{{ __('admin/settings/systems/updates.select_all') }}">
                            </th>
                            <th class="py-2 pr-4">{{ __('admin/settings/systems/updates.table.name') }}</th>
                            <th class="py-2 pr-4">{{ __('admin/settings/systems/updates.table.current') }}</th>
                            <th class="py-2 pr-4">{{ __('admin/settings/systems/updates.table.available') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plugins as $plugin)
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <td class="py-2 pr-2">
                                    <input type="checkbox"
                                           name="plugins[]"
                                           value="{{ $plugin['id'] }}"
                                           data-update-target="plugin"
                                           @checked($plugin['preselected'])
                                           class="rounded border-gray-300 dark:border-gray-600"
                                           id="plugin-{{ $plugin['id'] }}">
                                </td>
                                <td class="py-2 pr-4">
                                    <label for="plugin-{{ $plugin['id'] }}" class="cursor-pointer text-gray-900 dark:text-gray-100 font-medium">
                                        {{ $plugin['name'] }}
                                    </label>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $plugin['slug'] }}</span>
                                </td>
                                <td class="py-2 pr-4 font-mono text-xs text-gray-600 dark:text-gray-400">v{{ $plugin['currentVersion'] }}</td>
                                <td class="py-2 pr-4 font-mono text-xs text-blue-700 dark:text-blue-300 font-semibold">
                                    <i class="fas fa-arrow-up text-[10px] mr-1"></i>v{{ $plugin['availableVersion'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        {{-- テーマセクション --}}
        <section class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                <i class="fas fa-palette mr-2"></i>{{ __('admin/settings/systems/updates.themes.heading') }}
                @if(count($themes) > 0)
                    <span class="inline-flex items-center px-2 py-0.5 ml-2 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                        {{ __('admin/settings/systems/updates.themes.count', ['count' => count($themes)]) }}
                    </span>
                @endif
            </h2>

            @if(count($themes) === 0)
                <p class="text-sm text-gray-500 dark:text-gray-400 italic">{{ __('admin/settings/systems/updates.themes.none') }}</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <th class="py-2 pr-2 w-8">
                                <input type="checkbox"
                                       class="rounded border-gray-300 dark:border-gray-600"
                                       @change="$root.querySelectorAll('input[type=checkbox][data-update-target=theme]').forEach(cb => cb.checked = $event.target.checked); recompute()"
                                       aria-label="{{ __('admin/settings/systems/updates.select_all') }}">
                            </th>
                            <th class="py-2 pr-4">{{ __('admin/settings/systems/updates.table.name') }}</th>
                            <th class="py-2 pr-4">{{ __('admin/settings/systems/updates.table.current') }}</th>
                            <th class="py-2 pr-4">{{ __('admin/settings/systems/updates.table.available') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($themes as $theme)
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <td class="py-2 pr-2">
                                    <input type="checkbox"
                                           name="themes[]"
                                           value="{{ $theme['id'] }}"
                                           data-update-target="theme"
                                           @checked($theme['preselected'])
                                           class="rounded border-gray-300 dark:border-gray-600"
                                           id="theme-{{ $theme['id'] }}">
                                </td>
                                <td class="py-2 pr-4">
                                    <label for="theme-{{ $theme['id'] }}" class="cursor-pointer text-gray-900 dark:text-gray-100 font-medium">
                                        {{ $theme['name'] }}
                                    </label>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $theme['slug'] }}</span>
                                </td>
                                <td class="py-2 pr-4 font-mono text-xs text-gray-600 dark:text-gray-400">v{{ $theme['currentVersion'] }}</td>
                                <td class="py-2 pr-4 font-mono text-xs text-blue-700 dark:text-blue-300 font-semibold">
                                    <i class="fas fa-arrow-up text-[10px] mr-1"></i>v{{ $theme['availableVersion'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        {{-- 一括適用ボタン（更新可能な対象がある時のみ） --}}
        @if($totalCount > 0)
            <div class="flex justify-end">
                <x-form-button
                    type="button"
                    :label="__('admin/settings/systems/updates.apply_selected')"
                    variant="primary"
                    icon="fas fa-cloud-arrow-down"
                    x-bind:disabled="selectedCount === 0"
                    @click="if (selectedCount > 0) openModal('confirmSystemUpdatesModal')"
                />
            </div>

            <x-ui-modal
                id="confirmSystemUpdatesModal"
                :title="__('admin/settings/systems/updates.confirm.title')"
                message=""
                icon_type="info"
                confirm_color="blue"
                :confirm_label="__('admin/settings/systems/updates.apply_selected')"
                :cancel_label="__('common.cancel')"
                form="systemUpdatesApplyForm"
            >
                <p class="text-sm text-gray-700 dark:text-gray-300 text-center" x-text="`{{ __('admin/settings/systems/updates.confirm.message', ['count' => '%count%']) }}`.replace('%count%', selectedCount)"></p>
            </x-ui-modal>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 text-center">
                <i class="fas fa-check-circle text-3xl text-green-500 mb-2"></i>
                <p class="text-sm text-gray-700 dark:text-gray-300 font-medium">{{ __('admin/settings/systems/updates.all_up_to_date') }}</p>
            </div>
        @endif
    </form>
</div>
@endsection
