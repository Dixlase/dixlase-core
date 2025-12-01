{{--
This file is part of Dixlase.

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

@extends('layouts.admin')

@section('content')
<div class="mx-auto">
    <!-- インストール済みプラグイン一覧セクション -->
    <section>
        <h2>{{ __('admin.settings.plugins.index.installed_heading') }}</h2>

        <!-- レスポンシブテーブル -->
        <div class="responsive-table">
            <table>
                <caption class="sr-only">{{ __('admin.settings.plugins.index.table.caption') }}</caption>
                <thead>
                    <tr>
                        <th>{{ __('common.id') }}</th>
                        <th>{{ __('admin.settings.plugins.index.table.name') }}</th>
                        <th>{{ __('common.details') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plugins as $plugin)
                        <tr>
                            <td data-label="{{ __('admin.settings.plugins.index.table.id') }}">
                                <span class="text-sm font-mono px-2 py-1 rounded">{{ $plugin->id }}</span>
                            </td>
                            <td data-label="{{ __('admin.settings.plugins.index.table.name') }}">
                                <div>
                                    <strong class="text-lg">{{ $plugin->translated_name }}</strong>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $plugin->translated_description }}</div>
                                </div>
                            </td>
                            <td data-label="{{ __('common.details') }}">
                                <div class="text-sm space-y-1">
                                    <!-- 作者情報 -->
                                    <div class="mb-2">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('common.author') }}:</span>
                                        @if($plugin->author)
                                            <span>{{ $plugin->author }}</span>
                                            @if($plugin->email)
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $plugin->email }}</div>
                                            @endif
                                            @if($plugin->web)
                                                <div class="text-xs">
                                                    <a href="{{ $plugin->web }}" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">{{ $plugin->web }}</a>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-gray-400">{{ __('common.unknown') }}</span>
                                        @endif
                                    </div>
                                    
                                    <!-- バージョン情報 -->
                                    <div class="inline-block font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs">{{ $plugin->version }}</div>
                                    <!-- ライセンス情報 -->
                                    <div>
                                        @if($plugin->license)
                                            <div class="inline-block bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded text-xs">{{ $plugin->license }}</div>
                                        @else
                                            <div class="inline-block text-gray-400">{{ __('common.unknown') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td data-label="{{ __('common.status') }}">
                                <div class="space-y-2">
                                    <span class="status-badge status-badge--{{ $plugin->isEnabled() ? 'enabled' : 'disabled' }}">
                                        {{ $plugin->isEnabled() ? __('common.enabled') : __('common.disabled') }}
                                    </span>
                                    
                                    {{-- 権限・署名ステータスバッジ（モーダル表示） --}}
                                    @if(isset($plugin->permission_summary))
                                        @php
                                            $hasPermissions = $plugin->permission_summary['has_permissions'];
                                            $riskLevel = $plugin->permission_summary['risk_level'];
                                            $categories = $plugin->permission_summary['categories'] ?? [];
                                            $signature = $plugin->permission_summary['signature'] ?? ['status' => 'unsigned'];
                                            $permissionModalId = 'permissionModal-' . $plugin->id;
                                            
                                            // 署名ステータスに基づくバッジ設定
                                            $signatureStatus = $signature['status'] ?? 'unsigned';
                                            $signatureType = $signature['type'] ?? null;
                                            
                                            // バッジの色とアイコンを決定
                                            if ($signatureStatus === 'valid' || $signatureStatus === 'pending_verification') {
                                                // 署名あり（検証OK or 検証待ち）
                                                $badgeColors = [
                                                    'official' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                                    'verified' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                    'partner' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                                ];
                                                $badgeIcons = [
                                                    'official' => 'fas fa-crown',
                                                    'verified' => 'fas fa-check-circle',
                                                    'partner' => 'fas fa-handshake',
                                                ];
                                                $badgeLabels = [
                                                    'official' => __('admin.settings.plugins.permissions.signature_official'),
                                                    'verified' => __('admin.settings.plugins.permissions.signature_verified'),
                                                    'partner' => __('admin.settings.plugins.permissions.signature_partner'),
                                                ];
                                                $badgeColor = $badgeColors[$signatureType] ?? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
                                                $badgeIcon = $badgeIcons[$signatureType] ?? 'fas fa-check-circle';
                                                $badgeLabel = $badgeLabels[$signatureType] ?? __('admin.settings.plugins.permissions.signature_signed');
                                            } elseif ($signatureStatus === 'invalid') {
                                                // 署名無効
                                                $badgeColor = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
                                                $badgeIcon = 'fas fa-times-circle';
                                                $badgeLabel = __('admin.settings.plugins.permissions.signature_invalid');
                                            } elseif ($hasPermissions) {
                                                // 未署名 + 権限定義あり
                                                $riskColors = [
                                                    'low' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                    'medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                                    'high' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                                ];
                                                $riskIcons = [
                                                    'low' => 'fas fa-shield-alt',
                                                    'medium' => 'fas fa-exclamation-triangle',
                                                    'high' => 'fas fa-exclamation-circle',
                                                ];
                                                $badgeColor = $riskColors[$riskLevel] ?? $riskColors['low'];
                                                $badgeIcon = $riskIcons[$riskLevel] ?? $riskIcons['low'];
                                                $badgeLabel = __('admin.settings.plugins.permissions.risk_' . $riskLevel);
                                            } else {
                                                // 未署名 + 権限未定義
                                                $badgeColor = 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
                                                $badgeIcon = 'fas fa-question-circle';
                                                $badgeLabel = __('admin.settings.plugins.permissions.unknown');
                                            }
                                        @endphp
                                        <div class="mt-1">
                                            <button type="button" 
                                                    class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $badgeColor }} cursor-pointer hover:opacity-80 transition-opacity"
                                                    onclick="openModal('{{ $permissionModalId }}')">
                                                <i class="{{ $badgeIcon }} mr-1"></i>
                                                {{ $badgeLabel }}
                                                <i class="fas fa-info-circle ml-1 text-xs opacity-60"></i>
                                            </button>
                                            
                                            {{-- 権限・署名詳細モーダル --}}
                                            <x-modal
                                                :id="$permissionModalId"
                                                :title="__('admin.settings.plugins.permissions.details_title') . ' - ' . $plugin->translated_name"
                                                icon_type="info"
                                                :close_only="true"
                                                :close_label="__('common.close')"
                                            >
                                                <div class="text-left">
                                                    {{-- 署名ステータス --}}
                                                    <div class="mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('admin.settings.plugins.permissions.signature_status') }}</h4>
                                                        @if($signatureStatus === 'valid' || $signatureStatus === 'pending_verification')
                                                            <div class="flex items-center mb-2">
                                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $badgeColor }}">
                                                                    <i class="{{ $badgeIcon }} mr-1"></i>
                                                                    {{ $badgeLabel }}
                                                                </span>
                                                            </div>
                                                            @if($signature['signed_by'])
                                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                                    {{ __('admin.settings.plugins.permissions.signed_by') }}: {{ $signature['signed_by'] }}
                                                                </p>
                                                            @endif
                                                        @elseif($signatureStatus === 'invalid')
                                                            <div class="flex items-center mb-2">
                                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                                    <i class="fas fa-times-circle mr-1"></i>
                                                                    {{ __('admin.settings.plugins.permissions.signature_invalid') }}
                                                                </span>
                                                            </div>
                                                            <p class="text-sm text-red-600 dark:text-red-400">
                                                                {{ __('admin.settings.plugins.permissions.signature_invalid_warning') }}
                                                            </p>
                                                        @else
                                                            <div class="flex items-center mb-2">
                                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                                    <i class="fas fa-file-signature mr-1"></i>
                                                                    {{ __('admin.settings.plugins.permissions.signature_unsigned') }}
                                                                </span>
                                                            </div>
                                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                                {{ __('admin.settings.plugins.permissions.signature_unsigned_info') }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                    
                                                    {{-- 権限情報 --}}
                                                    <div>
                                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('admin.settings.plugins.permissions.permission_info') }}</h4>
                                                        @if($hasPermissions)
                                                            {{-- リスクレベル表示 --}}
                                                            <div class="mb-3 flex items-center">
                                                                <span class="text-sm text-gray-700 dark:text-gray-300 mr-2">{{ __('admin.settings.plugins.permissions.risk_level') }}:</span>
                                                                @php
                                                                    $riskColors = [
                                                                        'low' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                                        'medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                                                        'high' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                                                    ];
                                                                    $riskIcons = [
                                                                        'low' => 'fas fa-shield-alt',
                                                                        'medium' => 'fas fa-exclamation-triangle',
                                                                        'high' => 'fas fa-exclamation-circle',
                                                                    ];
                                                                @endphp
                                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $riskColors[$riskLevel] ?? $riskColors['low'] }}">
                                                                    <i class="{{ $riskIcons[$riskLevel] ?? $riskIcons['low'] }} mr-1"></i>
                                                                    {{ __('admin.settings.plugins.permissions.risk_' . $riskLevel) }}
                                                                </span>
                                                            </div>
                                                            
                                                            {{-- 権限カテゴリ一覧 --}}
                                                            @if(!empty($categories))
                                                                <div class="space-y-3">
                                                                    @foreach($categories as $category => $permissions)
                                                                        <div class="border-b border-gray-200 dark:border-gray-700 pb-2 last:border-0">
                                                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.settings.plugins.permissions.category_' . $category) }}:</span>
                                                                            <div class="mt-1 flex flex-wrap gap-1">
                                                                                @foreach($permissions as $perm)
                                                                                    <span class="inline-block bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-2 py-1 rounded text-xs">
                                                                                        {{ __('admin.settings.plugins.permissions.perm_' . $perm) }}
                                                                                    </span>
                                                                                @endforeach
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @else
                                                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.settings.plugins.permissions.no_special_permissions') }}</p>
                                                            @endif
                                                        @else
                                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.settings.plugins.permissions.no_permissions_defined') }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </x-modal>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td data-label="{{ __('common.actions') }}">
                                <div class="action-buttons">
                                    <!-- 設定画面リンク -->
                                    @if ($plugin->isEnabled() && isset($plugin->has_settings) && $plugin->has_settings)
                                        @php
                                            $settingsUrl = app('App\Http\Controllers\Admin\Settings\AdminPluginsSettingsController')->getPluginSettingsUrl($plugin);
                                        @endphp
                                        @if ($settingsUrl)
                                            <a href="{{ $settingsUrl }}" class="inline-block">
                                                <x-form.button
                                                    type="button"
                                                    label="設定"
                                                    variant="primary"
                                                    size="sm"
                                                    icon="fas fa-cog"
                                                />
                                            </a>
                                        @endif
                                    @endif

                                    @if ($plugin->isEnabled())
                                        <!-- 有効化中：無効化ボタンのみ -->
                                        <form action="{{ route('admin.settings.plugins.disable', $plugin->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            <x-form.button
                                                type="submit"
                                                :label="__('common.disable')"
                                                variant="warning"
                                                size="sm"
                                                icon="fas fa-pause"
                                            />
                                        </form>
                                    @else
                                        <!-- 無効化中：有効化とアンインストールボタン -->
                                        <form action="{{ route('admin.settings.plugins.enable', $plugin->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            <x-form.button
                                                type="submit"
                                                :label="__('common.enable')"
                                                variant="success"
                                                size="sm"
                                                icon="fas fa-play"
                                            />
                                        </form>

                                        <form action="{{ route('admin.settings.plugins.uninstall', $plugin->id) }}" method="POST" class="inline-block" id="uninstallForm-{{ $plugin->id }}">
                                            @csrf
                                            <x-form.button
                                                type="button"
                                                :label="__('common.uninstall')"
                                                variant="danger"
                                                size="sm"
                                                icon="fas fa-trash"
                                                onclick="openModal('uninstallModal-{{ $plugin->id }}')"
                                            />

                                            <!-- 確認画面のモーダル -->
                                            <x-modal
                                                id="uninstallModal-{{ $plugin->id }}"
                                                :title="__('admin.settings.plugins.index.uninstall.confirm_title')"
                                                :message="str_replace('{name}', $plugin->name, __('admin.settings.plugins.index.uninstall.confirm_message'))"
                                                :confirm_label="__('common.uninstall')"
                                                :cancel_label="__('common.cancel')"
                                                :checkbox="true"
                                                checkbox_name="remove_db_data"
                                                checkbox_label="{!! __('admin.settings.plugins.index.uninstall.remove_data_checkbox') !!}"
                                                form="uninstallForm-{{ $plugin->id }}"
                                                icon_type="danger"
                                                confirm_color="red"
                                            />
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-8">
                                <div class="empty-state">
                                    <i class="fas fa-puzzle-piece text-4xl text-gray-400 mb-4"></i>
                                    <p class="text-gray-500">{{ __('admin.settings.plugins.index.no_plugins') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- アンインストール済みプラグイン一覧セクション -->
    @if(count($uninstalledPlugins) > 0)
    <section class="mt-8">
        <h2>{{ __('admin.settings.plugins.index.uninstalled_heading') }}</h2>

        <!-- レスポンシブテーブル -->
        <div class="responsive-table">
            <table>
                <caption class="sr-only">{{ __('admin.settings.plugins.index.uninstalled_table.caption') }}</caption>
                <thead>
                    <tr>
                        <th>{{ __('admin.settings.plugins.index.table.name') }}</th>
                        <th>{{ __('common.details') }}</th>
                        <th>{{ __('admin.settings.plugins.permissions.risk_level') }}</th>
                        <th>{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($uninstalledPlugins as $plugin)
                        <tr>
                            <td data-label="{{ __('admin.settings.plugins.index.table.name') }}">
                                <div>
                                    <strong class="text-lg">{{ $plugin['name'] }}</strong>
                                    @if($plugin['description'])
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $plugin['description'] }}</div>
                                    @endif
                                </div>
                            </td>
                            <td data-label="{{ __('common.details') }}">
                                <div class="text-sm space-y-1">
                                    <!-- 作者情報 -->
                                    <div class="mb-2">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('common.author') }}:</span>
                                        @if($plugin['author'])
                                            <span>{{ $plugin['author'] }}</span>
                                            @if($plugin['email'])
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $plugin['email'] }}</div>
                                            @endif
                                            @if($plugin['url'])
                                                <div class="text-xs">
                                                    <a href="{{ $plugin['url'] }}" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">{{ $plugin['url'] }}</a>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-gray-400">{{ __('common.unknown') }}</span>
                                        @endif
                                    </div>
                                    
                                    <!-- バージョン情報 -->
                                    <div class="inline-block font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs">{{ $plugin['version'] }}</div>
                                    <!-- ライセンス情報 -->
                                    <div>
                                        @if($plugin['license'])
                                            <div class="inline-block bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded text-xs">{{ $plugin['license'] }}</div>
                                        @else
                                            <div class="inline-block text-gray-400">{{ __('common.unknown') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td data-label="{{ __('admin.settings.plugins.permissions.risk_level') }}">
                                {{-- 権限・署名ステータスバッジ（モーダル表示） --}}
                                @if(isset($plugin['permission_summary']))
                                    @php
                                        $hasPermissions = $plugin['permission_summary']['has_permissions'];
                                        $riskLevel = $plugin['permission_summary']['risk_level'];
                                        $categories = $plugin['permission_summary']['categories'] ?? [];
                                        $signature = $plugin['permission_summary']['signature'] ?? ['status' => 'unsigned'];
                                        $permissionModalId = 'permissionModal-uninstalled-' . $plugin['directory'];
                                        
                                        // 署名ステータスに基づくバッジ設定
                                        $signatureStatus = $signature['status'] ?? 'unsigned';
                                        $signatureType = $signature['type'] ?? null;
                                        
                                        // バッジの色とアイコンを決定
                                        if ($signatureStatus === 'valid' || $signatureStatus === 'pending_verification') {
                                            $badgeColors = [
                                                'official' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                                'verified' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                'partner' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                            ];
                                            $badgeIcons = [
                                                'official' => 'fas fa-crown',
                                                'verified' => 'fas fa-check-circle',
                                                'partner' => 'fas fa-handshake',
                                            ];
                                            $badgeLabels = [
                                                'official' => __('admin.settings.plugins.permissions.signature_official'),
                                                'verified' => __('admin.settings.plugins.permissions.signature_verified'),
                                                'partner' => __('admin.settings.plugins.permissions.signature_partner'),
                                            ];
                                            $badgeColor = $badgeColors[$signatureType] ?? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
                                            $badgeIcon = $badgeIcons[$signatureType] ?? 'fas fa-check-circle';
                                            $badgeLabel = $badgeLabels[$signatureType] ?? __('admin.settings.plugins.permissions.signature_signed');
                                        } elseif ($signatureStatus === 'invalid') {
                                            $badgeColor = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
                                            $badgeIcon = 'fas fa-times-circle';
                                            $badgeLabel = __('admin.settings.plugins.permissions.signature_invalid');
                                        } elseif ($hasPermissions) {
                                            $riskColors = [
                                                'low' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                'medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                                'high' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                            ];
                                            $riskIcons = [
                                                'low' => 'fas fa-shield-alt',
                                                'medium' => 'fas fa-exclamation-triangle',
                                                'high' => 'fas fa-exclamation-circle',
                                            ];
                                            $badgeColor = $riskColors[$riskLevel] ?? $riskColors['low'];
                                            $badgeIcon = $riskIcons[$riskLevel] ?? $riskIcons['low'];
                                            $badgeLabel = __('admin.settings.plugins.permissions.risk_' . $riskLevel);
                                        } else {
                                            $badgeColor = 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
                                            $badgeIcon = 'fas fa-question-circle';
                                            $badgeLabel = __('admin.settings.plugins.permissions.unknown');
                                        }
                                    @endphp
                                    <button type="button" 
                                            class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $badgeColor }} cursor-pointer hover:opacity-80 transition-opacity"
                                            onclick="openModal('{{ $permissionModalId }}')">
                                        <i class="{{ $badgeIcon }} mr-1"></i>
                                        {{ $badgeLabel }}
                                        <i class="fas fa-info-circle ml-1 text-xs opacity-60"></i>
                                    </button>
                                    
                                    {{-- 権限・署名詳細モーダル --}}
                                    <x-modal
                                        :id="$permissionModalId"
                                        :title="__('admin.settings.plugins.permissions.details_title') . ' - ' . $plugin['name']"
                                        icon_type="info"
                                        :close_only="true"
                                        :close_label="__('common.close')"
                                    >
                                        <div class="text-left">
                                            {{-- 署名ステータス --}}
                                            <div class="mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                                                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('admin.settings.plugins.permissions.signature_status') }}</h4>
                                                @if($signatureStatus === 'valid' || $signatureStatus === 'pending_verification')
                                                    <div class="flex items-center mb-2">
                                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $badgeColor }}">
                                                            <i class="{{ $badgeIcon }} mr-1"></i>
                                                            {{ $badgeLabel }}
                                                        </span>
                                                    </div>
                                                    @if($signature['signed_by'] ?? null)
                                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                                            {{ __('admin.settings.plugins.permissions.signed_by') }}: {{ $signature['signed_by'] }}
                                                        </p>
                                                    @endif
                                                @elseif($signatureStatus === 'invalid')
                                                    <div class="flex items-center mb-2">
                                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                            <i class="fas fa-times-circle mr-1"></i>
                                                            {{ __('admin.settings.plugins.permissions.signature_invalid') }}
                                                        </span>
                                                    </div>
                                                    <p class="text-sm text-red-600 dark:text-red-400">
                                                        {{ __('admin.settings.plugins.permissions.signature_invalid_warning') }}
                                                    </p>
                                                @else
                                                    <div class="flex items-center mb-2">
                                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                            <i class="fas fa-file-signature mr-1"></i>
                                                            {{ __('admin.settings.plugins.permissions.signature_unsigned') }}
                                                        </span>
                                                    </div>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                                        {{ __('admin.settings.plugins.permissions.signature_unsigned_info') }}
                                                    </p>
                                                @endif
                                            </div>
                                            
                                            {{-- 権限情報 --}}
                                            <div>
                                                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('admin.settings.plugins.permissions.permission_info') }}</h4>
                                                @if($hasPermissions)
                                                    {{-- リスクレベル表示 --}}
                                                    <div class="mb-3 flex items-center">
                                                        <span class="text-sm text-gray-700 dark:text-gray-300 mr-2">{{ __('admin.settings.plugins.permissions.risk_level') }}:</span>
                                                        @php
                                                            $riskColors = [
                                                                'low' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                                'medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                                                'high' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                                            ];
                                                            $riskIcons = [
                                                                'low' => 'fas fa-shield-alt',
                                                                'medium' => 'fas fa-exclamation-triangle',
                                                                'high' => 'fas fa-exclamation-circle',
                                                            ];
                                                        @endphp
                                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $riskColors[$riskLevel] ?? $riskColors['low'] }}">
                                                            <i class="{{ $riskIcons[$riskLevel] ?? $riskIcons['low'] }} mr-1"></i>
                                                            {{ __('admin.settings.plugins.permissions.risk_' . $riskLevel) }}
                                                        </span>
                                                    </div>
                                                    
                                                    {{-- 権限カテゴリ一覧 --}}
                                                    @if(!empty($categories))
                                                        <div class="space-y-3">
                                                            @foreach($categories as $category => $permissions)
                                                                <div class="border-b border-gray-200 dark:border-gray-700 pb-2 last:border-0">
                                                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('admin.settings.plugins.permissions.category_' . $category) }}:</span>
                                                                    <div class="mt-1 flex flex-wrap gap-1">
                                                                        @foreach($permissions as $perm)
                                                                            <span class="inline-block bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-2 py-1 rounded text-xs">
                                                                                {{ __('admin.settings.plugins.permissions.perm_' . $perm) }}
                                                                            </span>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.settings.plugins.permissions.no_special_permissions') }}</p>
                                                    @endif
                                                @else
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.settings.plugins.permissions.no_permissions_defined') }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </x-modal>
                                @endif
                            </td>
                            <td data-label="{{ __('common.actions') }}">
                                <div class="action-buttons">
                                    <!-- インストールボタン -->
                                    <form action="{{ route('admin.settings.plugins.install') }}" method="POST" class="inline-block" id="installForm-{{ $plugin['directory'] }}">
                                        @csrf
                                        <input type="hidden" name="directory" value="{{ $plugin['directory'] }}">
                                        <x-form.button
                                            type="button"
                                            :label="__('common.install')"
                                            variant="success"
                                            size="sm"
                                            icon="fas fa-download"
                                            onclick="openModal('installModal-{{ $plugin['directory'] }}')"
                                        />

                                        <!-- インストール確認モーダル -->
                                        <x-modal
                                            id="installModal-{{ $plugin['directory'] }}"
                                            :title="__('admin.settings.plugins.index.install.confirm_title')"
                                            :message="str_replace('{name}', $plugin['name'], __('admin.settings.plugins.index.install.confirm_message'))"
                                            :confirm_label="__('common.install')"
                                            :cancel_label="__('common.cancel')"
                                            form="installForm-{{ $plugin['directory'] }}"
                                            icon_type="info"
                                            confirm_color="green"
                                        />
                                    </form>

                                    <!-- 削除ボタン -->
                                    <form action="{{ route('admin.settings.plugins.delete') }}" method="POST" class="inline-block" id="deleteForm-{{ $plugin['directory'] }}">
                                        @csrf
                                        <input type="hidden" name="directory" value="{{ $plugin['directory'] }}">
                                        <x-form.button
                                            type="button"
                                            :label="__('common.delete')"
                                            variant="danger"
                                            size="sm"
                                            icon="fas fa-trash"
                                            onclick="openModal('deleteModal-{{ $plugin['directory'] }}')"
                                        />

                                        <!-- 削除確認モーダル -->
                                        <x-modal
                                            id="deleteModal-{{ $plugin['directory'] }}"
                                            :title="__('admin.settings.plugins.index.delete.confirm_title')"
                                            :message="str_replace('{name}', $plugin['name'], __('admin.settings.plugins.index.delete.confirm_message'))"
                                            :confirm_label="__('common.delete')"
                                            :cancel_label="__('common.cancel')"
                                            form="deleteForm-{{ $plugin['directory'] }}"
                                            icon_type="danger"
                                            confirm_color="red"
                                        />
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @endif
</div>

@endsection
