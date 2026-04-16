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
<div class="mx-auto max-w-5xl">

    {{-- ヘッダー --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-0">
            {{-- サムネイル --}}
            <div class="relative aspect-video md:aspect-square bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 overflow-hidden">
                <img
                    src="{{ $card['thumbnailUrl'] }}"
                    alt="{{ $card['name'] }}"
                    class="w-full h-full object-cover"
                    x-on:error="$el.src = '{{ asset('assets/images/plugin-default.svg') }}'; $el.onerror = null;"
                >
            </div>

            {{-- 基本情報 --}}
            <div class="md:col-span-2 p-6 flex flex-col">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="min-w-0 flex-1">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">{{ $card['name'] }}</h1>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="inline-block font-mono text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded">v{{ $card['version'] }}</span>

                            @if($isInstalled)
                                @if($card['isEnabled'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                        <i class="fas fa-check-circle mr-1"></i>{{ __('common.enabled') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        <i class="fas fa-pause-circle mr-1"></i>{{ __('common.disabled') }}
                                    </span>
                                @endif
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                    <i class="fas fa-download mr-1"></i>{{ __('common.not_installed') }}
                                </span>
                            @endif

                            @if($card['hasUpdateAvailable'] ?? false)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                    <i class="fas fa-arrow-up mr-1"></i>v{{ $card['availableVersion'] }} {{ __('admin/settings/plugins/show.update_available') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- 短い説明 --}}
                @if(! empty($card['description']))
                    <p class="text-gray-600 dark:text-gray-300 mb-4">{{ $card['description'] }}</p>
                @endif

                {{-- 主要メタ情報 --}}
                <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm mb-4">
                    @if(! empty($card['authorName']))
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.author') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-200">
                            @if(! empty($rawData['url']))
                                <a href="{{ $rawData['url'] }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ $card['authorName'] }} <i class="fas fa-external-link-alt text-[10px]"></i>
                                </a>
                            @else
                                {{ $card['authorName'] }}
                            @endif
                        </dd>
                    @endif

                    @if(! empty($card['license']))
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.license') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-200">{{ $card['license'] }}</dd>
                    @endif

                    <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.slug') }}</dt>
                    <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs">{{ $card['slug'] }}</dd>

                    @if(! empty($rawData['package_name']))
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.package_name') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs">{{ $rawData['package_name'] }}</dd>
                    @endif
                </dl>

                {{-- アクションボタン --}}
                <div class="mt-auto flex flex-wrap gap-2">
                    @if($isInstalled)
                        @if($card['isEnabled'] && $card['settingsUrl'])
                            <a href="{{ $card['settingsUrl'] }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <i class="fas fa-cog"></i>{{ __('admin/settings/plugins/show.actions.settings') }}
                            </a>
                        @endif

                        @if($card['isEnabled'])
                            <form method="POST" action="{{ route('admin.settings.plugins.disable', $card['id']) }}" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors">
                                    <i class="fas fa-pause"></i>{{ __('admin/settings/plugins/show.actions.disable') }}
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.settings.plugins.enable', $card['id']) }}" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                                    <i class="fas fa-play"></i>{{ __('admin/settings/plugins/show.actions.enable') }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.settings.plugins.uninstall', $card['id']) }}" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                                    <i class="fas fa-trash"></i>{{ __('admin/settings/plugins/show.actions.uninstall') }}
                                </button>
                            </form>
                        @endif
                    @else
                        <form method="POST" action="{{ route('admin.settings.plugins.install') }}" class="inline">
                            @csrf
                            <input type="hidden" name="directory" value="{{ $card['directory'] }}">
                            <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <i class="fas fa-download"></i>{{ __('admin/settings/plugins/show.actions.install') }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.settings.plugins.delete') }}" class="inline">
                            @csrf
                            <input type="hidden" name="directory" value="{{ $card['directory'] }}">
                            <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <i class="fas fa-trash"></i>{{ __('admin/settings/plugins/show.actions.delete') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- 説明（全文） --}}
    @if(! empty($rawData['description']))
        <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">{{ __('admin/settings/plugins/show.sections.description') }}</h2>
            <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $card['description'] }}</div>
        </section>
    @endif

    {{-- 詳細情報 --}}
    <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('admin/settings/plugins/show.sections.details') }}</h2>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
            @if(! empty($rawData['namespace']))
                <div>
                    <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('admin/settings/plugins/show.namespace') }}</dt>
                    <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs break-all">{{ $rawData['namespace'] }}</dd>
                </div>
            @endif

            @if(! empty($card['directory']))
                <div>
                    <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('admin/settings/plugins/show.directory') }}</dt>
                    <dd class="text-gray-900 dark:text-gray-200 font-mono text-xs break-all">{{ $card['directory'] }}</dd>
                </div>
            @endif

            @if(! empty($rawData['email']))
                <div>
                    <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('admin/settings/plugins/show.email') }}</dt>
                    <dd class="text-gray-900 dark:text-gray-200">
                        <a href="mailto:{{ $rawData['email'] }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ $rawData['email'] }}</a>
                    </dd>
                </div>
            @endif

            @if(! empty($rawData['url']))
                <div>
                    <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('admin/settings/plugins/show.url') }}</dt>
                    <dd class="text-gray-900 dark:text-gray-200">
                        <a href="{{ $rawData['url'] }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 dark:text-indigo-400 hover:underline break-all">
                            {{ $rawData['url'] }} <i class="fas fa-external-link-alt text-[10px]"></i>
                        </a>
                    </dd>
                </div>
            @endif
        </dl>
    </section>

    {{-- スキャン結果（インストール済み・未インストール両方で表示） --}}
    @if($card['auditedAtFormatted'] || $card['healthScore'] !== null)
        <section class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('admin/settings/plugins/show.sections.scan_result') }}</h2>
                @if($card['auditedAtFormatted'])
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin/settings/plugins/show.last_scanned_at', ['date' => $card['auditedAtFormatted']]) }}</span>
                @endif
            </div>

            {{-- 健全性スコア --}}
            @if($card['healthScore'] !== null)
                <div class="mb-4 p-4 rounded-lg {{ $card['healthStatusColors'][$card['healthStatus']] ?? 'bg-gray-100 dark:bg-gray-700' }}">
                    <div class="flex items-center gap-3">
                        <div class="text-3xl font-bold">{{ $card['healthScore'] }}<span class="text-sm font-normal">/100</span></div>
                        <div>
                            <div class="font-medium">
                                <i class="fas {{ $card['healthStatusIcons'][$card['healthStatus']] ?? 'fa-question-circle' }} mr-1"></i>
                                {{ __('admin/settings/plugins/index.permissions.'.($card['healthStatusLabelKeys'][$card['healthStatus']] ?? 'health_status_not_verified')) }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- 検証ステータス --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('admin/settings/plugins/show.scan.signature') }}</div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ __('admin/settings/plugins/index.verification.signature_'.$card['signatureStatus']) }}</div>
                </div>

                <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('admin/settings/plugins/show.scan.permission') }}</div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                        @if($card['hasMismatches'])
                            {{ __('admin/settings/plugins/index.verification.permission_mismatch') }}
                        @elseif(! $card['hasPermissions'])
                            {{ __('admin/settings/plugins/index.verification.permission_undefined') }}
                        @else
                            {{ __('admin/settings/plugins/index.verification.permission_ok') }}
                        @endif
                    </div>
                </div>

                <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('admin/settings/plugins/show.scan.csp') }}</div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ __('admin/settings/plugins/index.verification.'.$cspStatusLabelKey) }}
                    </div>
                </div>

                <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('admin/settings/plugins/show.scan.operation') }}</div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ __('admin/settings/plugins/index.operation_status.'.($card['operationStatus']['status'] ?? 'unknown')) }}
                    </div>
                </div>
            </div>

            {{-- 健全性の指摘事項 --}}
            @if(! empty($card['healthIssues']))
                <div class="mt-4 mb-4">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">{{ __('admin/settings/plugins/show.scan.issues') }}</h3>
                    <ul class="space-y-2">
                        @foreach($card['healthIssues'] as $issue)
                            <li class="flex items-start gap-2 p-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded text-sm">
                                <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5"></i>
                                <div class="flex-1">
                                    <span class="text-gray-700 dark:text-gray-200">{{ $issue['description'] ?? ($issue['type'] ?? '') }}</span>
                                    @if(! empty($issue['deduction']))
                                        <span class="text-xs text-yellow-700 dark:text-yellow-300 ml-1">({{ $issue['deduction'] }})</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- CSP モード互換性バロメータ --}}
            @if(! empty($card['cspBarometerItems']))
                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                        <i class="fas fa-shield-alt mr-1 {{ $card['cspTierIconColor'] }}"></i>
                        {{ __('admin/settings/plugins/index.badge_labels.csp_full') ?? __('admin/settings/plugins/index.badge_labels.csp') }}
                    </h3>
                    <x-ui-barometer :items="$card['cspBarometerItems']" />
                </div>
            @endif

            {{-- セキュリティプリセット互換性バロメータ --}}
            @if(! empty($card['presetBarometerItems']))
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                        <i class="fas fa-layer-group mr-1 {{ $card['presetTierIconColor'] }}"></i>
                        {{ __('admin/settings/plugins/index.badge_labels.preset') }}
                    </h3>
                    <x-ui-barometer :items="$card['presetBarometerItems']" />
                </div>
            @endif

            {{-- 詳細なスキャン情報（バッジモーダル互換） --}}
            @if($card['permissionSummary'])
                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                        <i class="fas fa-clipboard-list mr-1"></i>
                        {{ __('admin/settings/plugins/show.sections.scan_details') }}
                    </h3>
                    @include('admin.settings.plugins.partials.scan-details', ['card' => $card])
                </div>
            @endif

            {{-- 再スキャンボタン --}}
            <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700 flex gap-2">
                <button
                    type="button"
                    class="audit-rescan-btn inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                    data-plugin-slug="{{ $card['slug'] }}"
                    data-audit-url="{{ route('admin.settings.plugins.audit') }}"
                    data-csrf-token="{{ csrf_token() }}"
                >
                    <i class="fas fa-sync-alt"></i>{{ __('admin/settings/plugins/show.scan.rescan') }}
                </button>
            </div>
        </section>
    @endif

    {{-- 戻るボタン --}}
    <div class="mt-6">
        <a href="{{ route('admin.settings.plugins.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
            <i class="fas fa-arrow-left"></i>{{ __('admin/settings/plugins/show.back_to_list') }}
        </a>
    </div>
</div>

@push('scripts')
<script @cspNonce>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.audit-rescan-btn').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const slug = btn.dataset.pluginSlug;
                const url = btn.dataset.auditUrl;
                const csrfToken = btn.dataset.csrfToken;

                btn.disabled = true;
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>{{ __('admin/settings/plugins/show.scan.scanning') }}';

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ slug }),
                    });
                    const data = await response.json();
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Scan failed');
                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                    }
                } catch (error) {
                    alert(error.message);
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            });
        });
    });
</script>
@endpush
@endsection
