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
<div class="mx-auto max-w-5xl">

    {{-- Back link (top) --}}
    <div class="mb-4">
        <a href="{{ route('admin.settings.themes.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
            <i class="fas fa-arrow-left"></i>{{ __('admin/settings/themes/show.back_to_list') }}
        </a>
    </div>

    {{-- Header (extension detail card common component) --}}
    <x-admin.extension-detail
        :title="$card['name']"
        :version="$card['version']"
        :description="$card['description']"
        :thumbnail-url="$card['thumbnailUrl']"
        :fallback-thumbnail-url="asset('assets/images/theme-default.svg')"
    >
        <x-slot:badges>
            @if($isInstalled)
                @if($card['isEnabled'])
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                        <i class="fas fa-star mr-1"></i>{{ __('common.active') }}
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                        <i class="fas fa-pause-circle mr-1"></i>{{ __('common.inactive') }}
                    </span>
                @endif
            @else
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                    <i class="fas fa-download mr-1"></i>{{ __('common.not_installed') }}
                </span>
            @endif

            @if($card['hasUpdateAvailable'] ?? false)
                <a href="{{ route('admin.settings.systems.updates.index', ['target' => 'theme:' . $card['slug']]) }}"
                   class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors">
                    <i class="fas fa-arrow-up"></i>v{{ $card['availableVersion'] }} {{ __('admin/settings/themes/show.update_available') }}
                    <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            @endif
        </x-slot:badges>

        <x-slot:metadata>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2">
                {{-- Left column: Author → License → Email → URL --}}
                <div class="space-y-2">
                    @if(! empty($card['authorName']))
                        <div class="flex items-start gap-3">
                            <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/themes/show.author') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 min-w-0 break-words">{{ $card['authorName'] }}</dd>
                        </div>
                    @endif

                    @if(! empty($card['license']))
                        <div class="flex items-start gap-3">
                            <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/themes/show.license') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 min-w-0 break-words">{{ $card['license'] }}</dd>
                        </div>
                    @endif

                    @if(! empty($rawData['email']))
                        <div class="flex items-start gap-3">
                            <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/themes/show.email') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 min-w-0 break-all">
                                <a href="mailto:{{ $rawData['email'] }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ $rawData['email'] }}</a>
                            </dd>
                        </div>
                    @endif

                    @if(! empty($rawData['url']))
                        <div class="flex items-start gap-3">
                            <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/themes/show.url') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 min-w-0 break-all">
                                <a href="{{ $rawData['url'] }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ $rawData['url'] }} <i class="fas fa-external-link-alt text-[10px]"></i>
                                </a>
                            </dd>
                        </div>
                    @endif
                </div>

                {{-- Right column: Namespace → Slug → Package name → Directory --}}
                <div class="space-y-2">
                    @if(! empty($rawData['namespace']))
                        <div class="flex items-start gap-3">
                            <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/themes/show.namespace') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs break-all min-w-0">{{ $rawData['namespace'] }}</dd>
                        </div>
                    @endif

                    <div class="flex items-start gap-3">
                        <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/themes/show.slug') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs break-all min-w-0">{{ $card['slug'] }}</dd>
                    </div>

                    @if(! empty($rawData['package_name']))
                        <div class="flex items-start gap-3">
                            <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/themes/show.package_name') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs break-all min-w-0">{{ $rawData['package_name'] }}</dd>
                        </div>
                    @endif

                    @if(! empty($card['directory']))
                        <div class="flex items-start gap-3">
                            <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">{{ __('admin/settings/themes/show.directory') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs break-all min-w-0">{{ $card['directory'] }}</dd>
                        </div>
                    @endif
                </div>
            </dl>
        </x-slot:metadata>

        <x-slot:actions>
            {{-- Updates consolidated to unified update management page --}}

            {{-- Action buttons (uses same modal flow as list page) --}}
            @if($isInstalled)
                @include('admin.settings.themes.partials.installed-actions', ['card' => $card])
            @else
                @include('admin.settings.themes.partials.uninstalled-actions', ['card' => $card])
            @endif
        </x-slot:actions>
    </x-admin.extension-detail>

    {{-- Badge modal (shared with list page) --}}
    @if($card['permissionSummary'])
        @include('admin.settings.themes.partials.permission-modal', ['card' => $card])
    @endif

    {{-- Scan results section. Mirrors the plugin detail page: a
         header with a (re)scan button, a "not scanned yet" hint when
         no audit exists, and the full scan-details breakdown (health
         score, signature, permission consistency, CSP, owned tables,
         capabilities, etc.) once an audit has run. --}}
    <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="flex items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-3">
                <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('admin/settings/themes/show.sections.scan_result') }}</span>
                <x-form-button
                    type="button"
                    :label="empty($card['auditedAt']) ? __('admin/settings/themes/show.scan.scan') : __('admin/settings/themes/show.scan.rescan')"
                    :variant="empty($card['auditedAt']) ? 'warning' : 'secondary'"
                    size="sm"
                    icon="fas fa-sync-alt"
                    class="theme-audit-btn"
                    :data-slug="$card['slug']"
                />
            </div>
            @if($card['auditedAtFormatted'] ?? null)
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin/settings/themes/show.last_scanned_at', ['date' => $card['auditedAtFormatted']]) }}</span>
            @endif
        </div>

        {{-- Message when not scanned --}}
        @if(empty($card['auditedAt']))
            <div class="mb-4 p-4 rounded-lg bg-gray-50 dark:bg-gray-700/50 text-center">
                <i class="fas fa-info-circle text-2xl text-gray-400 mb-2"></i>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/settings/themes/show.scan.not_scanned_message') }}</p>
            </div>
        @endif

        {{-- Full scan breakdown (same component family as the plugin
             detail page; sections self-hide when their data is empty). --}}
        @if($card['permissionSummary'] || $card['healthScore'] !== null)
            @include('admin.settings.themes.partials.scan-details', ['card' => $card])
        @endif
    </section>

    {{-- Back link (bottom) --}}
    <div class="mt-6">
        <a href="{{ route('admin.settings.themes.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
            <i class="fas fa-arrow-left"></i>{{ __('admin/settings/themes/show.back_to_list') }}
        </a>
    </div>
</div>

{{-- Audit-related script (shared with list page) --}}
@include('admin.settings.themes.partials.audit-script')
@endsection
