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
    @if($modeData['isReadOnly'] ?? false)
        <x-admin.mode-readonly-banner />
    @endif

    <form id="security-extensions-form" method="POST" action="{{ route('admin.settings.security.extensions.update') }}">
        @csrf
        <fieldset {{ ($modeData['isReadOnly'] ?? false) ? 'disabled' : '' }}>

        <!-- 拡張機能セキュリティ設定 -->
        <section x-data="{
            preset: '{{ old('extension_security_preset', $settings['extension_security_preset']) }}',
            requireSignature: {{ old('extension_require_signature', $settings['extension_require_signature']) ? 'true' : 'false' }},
            requirePermissionDefinition: {{ old('extension_require_permission_definition', $settings['extension_require_permission_definition']) ? 'true' : 'false' }},
            allowUndefinedPermissions: {{ old('extension_allow_undefined_permissions', $settings['extension_allow_undefined_permissions']) ? 'true' : 'false' }},
            pluginMaxHealthLevel: {{ old('extension_plugin_max_health_level', $settings['extension_plugin_max_health_level']) }},
            themeMaxHealthLevel: {{ old('extension_theme_max_health_level', $settings['extension_theme_max_health_level']) }},
            allowLogicThemes: {{ old('extension_allow_logic_themes', $settings['extension_allow_logic_themes']) ? 'true' : 'false' }},
            permissionMismatchAction: '{{ old('extension_permission_mismatch_action', $settings['extension_permission_mismatch_action']) }}',
            
            applyPreset(presetValue) {
                const presets = {
                    'strict': {
                        requireSignature: true,
                        requirePermissionDefinition: true,
                        allowUndefinedPermissions: false,
                        pluginMaxHealthLevel: 0,
                        themeMaxHealthLevel: 0,
                        allowLogicThemes: false
                    },
                    'balanced': {
                        requireSignature: false,
                        requirePermissionDefinition: false,
                        allowUndefinedPermissions: true,
                        pluginMaxHealthLevel: 1,
                        themeMaxHealthLevel: 2,
                        allowLogicThemes: true
                    },
                    'development': {
                        requireSignature: false,
                        requirePermissionDefinition: false,
                        allowUndefinedPermissions: true,
                        pluginMaxHealthLevel: 3,
                        themeMaxHealthLevel: 3,
                        allowLogicThemes: true
                    }
                };
                
                if (presets[presetValue]) {
                    const p = presets[presetValue];
                    this.requireSignature = p.requireSignature;
                    this.requirePermissionDefinition = p.requirePermissionDefinition;
                    this.allowUndefinedPermissions = p.allowUndefinedPermissions;
                    this.pluginMaxHealthLevel = p.pluginMaxHealthLevel;
                    this.themeMaxHealthLevel = p.themeMaxHealthLevel;
                    this.allowLogicThemes = p.allowLogicThemes;
                }
            },
            
            getHealthLevelClass(level) {
                const classes = {
                    0: 'text-green-600 dark:text-green-400',
                    1: 'text-yellow-600 dark:text-yellow-400',
                    2: 'text-orange-600 dark:text-orange-400',
                    3: 'text-gray-600 dark:text-gray-400'
                };
                return classes[level] || classes[0];
            }
        }" x-init="$watch('preset', (value) => { if (value !== 'custom') applyPreset(value); })">
            <h2>{{ __('admin/settings/security/extensions.security.title') }}</h2>
            <p>{{ __('admin/settings/security/extensions.security.description') }}</p>

            <!-- プリセット選択 -->
            <fieldset>
                <legend>{{ __('admin/settings/security/extensions.security.preset_label') }}</legend>
                
                <div class="mt-3">
                    <x-form-radio-card-group
                        name="extension_security_preset"
                        :options="$presetOptions"
                        :value="old('extension_security_preset', $settings['extension_security_preset'])"
                        xModel="preset"
                        :columns="2"
                    />
                </div>
                
                <p class="mt-2">{{ __('admin/settings/security/extensions.security.preset_help') }}</p>
            </fieldset>

            <!-- カスタム設定 -->
            <div class="mt-6 space-y-6" :class="{ 'opacity-50 pointer-events-none': preset !== 'custom' }">
                <div class="flex items-center gap-2 mb-4" x-show="preset !== 'custom'">
                    <i class="fas fa-info-circle text-blue-500"></i>
                    <span class="text-sm text-blue-600 dark:text-blue-400">
                        {{ __('admin/settings/security/extensions.security.custom_mode_hint') }}
                    </span>
                </div>

                <!-- 署名要件 -->
                <fieldset>
                    <legend>{{ __('admin/settings/security/extensions.security.signature_settings') }}</legend>

                    <x-form-toggle
                        :label="__('admin/settings/security/extensions.security.require_signature')"
                        id="extension_require_signature"
                        name="extension_require_signature"
                        :checked="old('extension_require_signature', $settings['extension_require_signature'])"
                        xModel="requireSignature"
                    />
                    <p>{{ __('admin/settings/security/extensions.security.require_signature_help') }}</p>

                    {{-- 必須にしている場合の追加警告（トグル ON 時のみ表示） --}}
                    {{-- form-toggle は内部で xModel を文字列 '0'/'1' に切り替えるため、
                         単純な truthy 判定だと '0' も真になってしまう。
                         初期値（boolean）と toggle 後（'1'/'0'）の両方に対応するため
                         数値的等価で比較する。 --}}
                    <div
                        x-show="requireSignature == 1"
                        x-cloak
                        class="mt-3 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg"
                    >
                        <p class="text-sm text-amber-800 dark:text-amber-200">
                            <span class="font-semibold">⚠️</span>
                            {{ __('admin/settings/security/extensions.security.signature_required_warning') }}
                        </p>
                    </div>

                    {{-- Authority URL の開示（トグル状態に関わらず常時表示） --}}
                    <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            {{ __('admin/settings/security/extensions.security.signature_authority_url_label') }}
                        </p>
                        <p class="mt-1 font-mono text-sm text-blue-900 dark:text-blue-100 break-all">
                            {{ $authorityUrl }}
                        </p>
                    </div>
                </fieldset>

                <!-- 権限定義要件 -->
                <fieldset>
                    <legend>{{ __('admin/settings/security/extensions.security.permission_settings') }}</legend>
                    
                    <x-form-toggle
                        :label="__('admin/settings/security/extensions.security.require_permission_definition')"
                        id="extension_require_permission_definition"
                        name="extension_require_permission_definition"
                        :checked="old('extension_require_permission_definition', $settings['extension_require_permission_definition'])"
                        xModel="requirePermissionDefinition"
                    />
                    <p>{{ __('admin/settings/security/extensions.security.require_permission_definition_help') }}</p>
                    
                    <div class="mt-4">
                        <x-form-toggle
                            :label="__('admin/settings/security/extensions.security.allow_undefined_permissions')"
                            id="extension_allow_undefined_permissions"
                            name="extension_allow_undefined_permissions"
                            :checked="old('extension_allow_undefined_permissions', $settings['extension_allow_undefined_permissions'])"
                            xModel="allowUndefinedPermissions"
                        />
                        <p>{{ __('admin/settings/security/extensions.security.allow_undefined_permissions_help') }}</p>
                    </div>
                </fieldset>

                <!-- プラグイン健全性レベル -->
                <fieldset>
                    <legend>{{ __('admin/settings/security/extensions.security.plugin_health_level') }}</legend>
                    
                    <div class="mt-3">
                        <x-form-range
                            id="extension_plugin_max_health_level"
                            name="extension_plugin_max_health_level"
                            :value="old('extension_plugin_max_health_level', $settings['extension_plugin_max_health_level'])"
                            :min="0"
                            :max="3"
                            :step="1"
                            :labels="$securityLevelRangeLabels"
                            :labelColors="$securityLevelRangeLabelColors"
                            xModel="pluginMaxHealthLevel"
                        />
                    </div>
                    
                    <div class="mt-3 p-3 rounded-lg border" :class="getHealthLevelClass(pluginMaxHealthLevel)">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-puzzle-piece"></i>
                            <span class="font-medium">{{ __('admin/settings/security/extensions.security.current_setting') }}:</span>
                            <span x-text="[
                                '{{ __('admin/settings/security/extensions.security.health_level.healthy') }}',
                                '{{ __('admin/settings/security/extensions.security.health_level.warning') }}',
                                '{{ __('admin/settings/security/extensions.security.health_level.needs_attention') }}',
                                '{{ __('admin/settings/security/extensions.security.health_level.not_verified') }}'
                            ][pluginMaxHealthLevel]"></span>
                        </div>
                        <p class="mt-1 text-sm" x-text="[
                            '{{ __('admin/settings/security/extensions.security.health_level_description.healthy') }}',
                            '{{ __('admin/settings/security/extensions.security.health_level_description.warning') }}',
                            '{{ __('admin/settings/security/extensions.security.health_level_description.needs_attention') }}',
                            '{{ __('admin/settings/security/extensions.security.health_level_description.not_verified') }}'
                        ][pluginMaxHealthLevel]"></p>
                    </div>
                    
                    <p class="mt-2">{{ __('admin/settings/security/extensions.security.plugin_health_level_help') }}</p>
                </fieldset>

                <!-- テーマ健全性レベル -->
                <fieldset>
                    <legend>{{ __('admin/settings/security/extensions.security.theme_health_level') }}</legend>
                    
                    <div class="mt-3">
                        <x-form-range
                            id="extension_theme_max_health_level"
                            name="extension_theme_max_health_level"
                            :value="old('extension_theme_max_health_level', $settings['extension_theme_max_health_level'])"
                            :min="0"
                            :max="3"
                            :step="1"
                            :labels="$securityLevelRangeLabels"
                            :labelColors="$securityLevelRangeLabelColors"
                            xModel="themeMaxHealthLevel"
                        />
                    </div>
                    
                    <div class="mt-3 p-3 rounded-lg border" :class="getHealthLevelClass(themeMaxHealthLevel)">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-palette"></i>
                            <span class="font-medium">{{ __('admin/settings/security/extensions.security.current_setting') }}:</span>
                            <span x-text="[
                                '{{ __('admin/settings/security/extensions.security.health_level.healthy') }}',
                                '{{ __('admin/settings/security/extensions.security.health_level.warning') }}',
                                '{{ __('admin/settings/security/extensions.security.health_level.needs_attention') }}',
                                '{{ __('admin/settings/security/extensions.security.health_level.not_verified') }}'
                            ][themeMaxHealthLevel]"></span>
                        </div>
                        <p class="mt-1 text-sm" x-text="[
                            '{{ __('admin/settings/security/extensions.security.health_level_description.healthy') }}',
                            '{{ __('admin/settings/security/extensions.security.health_level_description.warning') }}',
                            '{{ __('admin/settings/security/extensions.security.health_level_description.needs_attention') }}',
                            '{{ __('admin/settings/security/extensions.security.health_level_description.not_verified') }}'
                        ][themeMaxHealthLevel]"></p>
                    </div>
                    
                    <p class="mt-2">{{ __('admin/settings/security/extensions.security.theme_health_level_help') }}</p>
                </fieldset>

                <!-- ロジックを含むテーマ -->
                <fieldset>
                    <legend>{{ __('admin/settings/security/extensions.security.logic_themes') }}</legend>
                    
                    <x-form-toggle
                        :label="__('admin/settings/security/extensions.security.allow_logic_themes')"
                        id="extension_allow_logic_themes"
                        name="extension_allow_logic_themes"
                        :checked="old('extension_allow_logic_themes', $settings['extension_allow_logic_themes'])"
                        xModel="allowLogicThemes"
                    />
                    <p>{{ __('admin/settings/security/extensions.security.allow_logic_themes_help') }}</p>
                    
                    <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <div class="flex items-start gap-2">
                            <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                            <div class="text-sm text-blue-700 dark:text-blue-300">
                                <p class="font-medium">{{ __('admin/settings/security/extensions.security.theme_types_title') }}</p>
                                <ul class="mt-1 list-disc list-inside space-y-1">
                                    <li>{{ __('admin/settings/security/extensions.security.theme_type_pure') }}</li>
                                    <li>{{ __('admin/settings/security/extensions.security.theme_type_logic') }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <!-- 権限不一致時の動作 -->
                <fieldset>
                    <legend>{{ __('admin/settings/security/extensions.security.permission_mismatch') }}</legend>
                    <p>{{ __('admin/settings/security/extensions.security.permission_mismatch_help') }}</p>
                    
                    <div class="mt-3">
                        <x-form-radio-card-group
                            name="extension_permission_mismatch_action"
                            :options="[
                                [
                                    'value' => 'warn',
                                    'label' => __('admin/settings/security/extensions.security.mismatch_action.warn'),
                                    'description' => __('admin/settings/security/extensions.security.mismatch_action.warn_description'),
                                    'icon' => 'fas fa-exclamation-triangle',
                                    'color' => 'yellow'
                                ],
                                [
                                    'value' => 'block',
                                    'label' => __('admin/settings/security/extensions.security.mismatch_action.block'),
                                    'description' => __('admin/settings/security/extensions.security.mismatch_action.block_description'),
                                    'icon' => 'fas fa-ban',
                                    'color' => 'red'
                                ]
                            ]"
                            :value="old('extension_permission_mismatch_action', $settings['extension_permission_mismatch_action'])"
                            xModel="permissionMismatchAction"
                            :columns="2"
                        />
                    </div>
                </fieldset>
            </div>
        </section>

        <!-- 拡張機能操作通知設定 -->
        <section>
            <h2>{{ __('admin/settings/security/extensions.notification.title') }}</h2>
            <p>{{ __('admin/settings/security/extensions.notification.description') }}</p>

            <!-- メールサーバー設定の確認メッセージ -->
            @if(!($mailConnectionTested && $mailSendTested && $mailReceiveTested))
                <div class="mt-4">
                    <x-ui-message
                        type="warning"
                        :message="__('admin.settings.security.error_notification_mail_test_required', ['url' => route('admin.settings.base.mail')])"
                    />
                </div>
            @endif

            <!-- インストール時に通知 -->
            <fieldset>
                <legend>{{ __('admin/settings/security/extensions.notification.notify_on_install') }}</legend>
                
                <x-form-toggle
                    :label="__('admin/settings/security/extensions.notification.notify_on_install')"
                    id="extension_notify_on_install"
                    name="extension_notify_on_install"
                    :checked="old('extension_notify_on_install', $settings['extension_notify_on_install'] ?? true)"
                />
                <p>{{ __('admin/settings/security/extensions.notification.notify_on_install_help') }}</p>
            </fieldset>

            <!-- アンインストール時に通知 -->
            <fieldset>
                <legend>{{ __('admin/settings/security/extensions.notification.notify_on_uninstall') }}</legend>
                
                <x-form-toggle
                    :label="__('admin/settings/security/extensions.notification.notify_on_uninstall')"
                    id="extension_notify_on_uninstall"
                    name="extension_notify_on_uninstall"
                    :checked="old('extension_notify_on_uninstall', $settings['extension_notify_on_uninstall'] ?? true)"
                />
                <p>{{ __('admin/settings/security/extensions.notification.notify_on_uninstall_help') }}</p>
            </fieldset>

            <!-- 有効化時に通知 -->
            <fieldset>
                <legend>{{ __('admin/settings/security/extensions.notification.notify_on_enable') }}</legend>
                
                <x-form-toggle
                    :label="__('admin/settings/security/extensions.notification.notify_on_enable')"
                    id="extension_notify_on_enable"
                    name="extension_notify_on_enable"
                    :checked="old('extension_notify_on_enable', $settings['extension_notify_on_enable'] ?? true)"
                />
                <p>{{ __('admin/settings/security/extensions.notification.notify_on_enable_help') }}</p>
            </fieldset>

            <!-- 無効化時に通知 -->
            <fieldset>
                <legend>{{ __('admin/settings/security/extensions.notification.notify_on_disable') }}</legend>
                
                <x-form-toggle
                    :label="__('admin/settings/security/extensions.notification.notify_on_disable')"
                    id="extension_notify_on_disable"
                    name="extension_notify_on_disable"
                    :checked="old('extension_notify_on_disable', $settings['extension_notify_on_disable'] ?? false)"
                />
                <p>{{ __('admin/settings/security/extensions.notification.notify_on_disable_help') }}</p>
            </fieldset>

            <!-- 健全性問題検出時に通知 -->
            <fieldset>
                <legend>{{ __('admin/settings/security/extensions.notification.notify_on_unhealthy') }}</legend>
                
                <x-form-toggle
                    :label="__('admin/settings/security/extensions.notification.notify_on_unhealthy')"
                    id="extension_notify_on_unhealthy"
                    name="extension_notify_on_unhealthy"
                    :checked="old('extension_notify_on_unhealthy', $settings['extension_notify_on_unhealthy'] ?? true)"
                />
                <p>{{ __('admin/settings/security/extensions.notification.notify_on_unhealthy_help') }}</p>
            </fieldset>

            <!-- 操作ログ記録 -->
            <fieldset>
                <legend>{{ __('admin/settings/security/extensions.notification.log_operations') }}</legend>
                
                <x-form-toggle
                    :label="__('admin/settings/security/extensions.notification.log_operations')"
                    id="extension_log_operations"
                    name="extension_log_operations"
                    :checked="old('extension_log_operations', $settings['extension_log_operations'] ?? true)"
                />
                <p>{{ __('admin/settings/security/extensions.notification.log_operations_help') }}</p>
            </fieldset>
        </section>

        <!-- 拡張機能ソース設定 -->
        <section x-data="{
            sourceType: '{{ old('extension_source_type', $settings['extension_source_type'] ?? 'github') }}',
            testStatus: 'idle',
            testMessage: '',
            testDetails: {},

            async testConnection() {
                this.testStatus = 'testing';
                this.testMessage = '';
                this.testDetails = {};

                try {
                    const tokenInput = document.getElementById('extension_source_token');
                    const response = await fetch('{{ route('admin.settings.security.extensions.test-source') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            type: this.sourceType,
                            token: tokenInput ? tokenInput.value : '',
                        }),
                    });

                    const data = await response.json();
                    this.testStatus = data.success ? 'success' : 'failed';
                    this.testMessage = data.message || '';
                    this.testDetails = data.details || {};
                } catch (error) {
                    this.testStatus = 'failed';
                    this.testMessage = error.message;
                }
            }
        }">
            <h2>{{ __('admin/settings/security/extensions.source.title') }}</h2>
            <p>{{ __('admin/settings/security/extensions.source.description') }}</p>

            <!-- ソースタイプ選択 -->
            <fieldset>
                <legend>{{ __('admin/settings/security/extensions.source.source_type') }}</legend>

                <x-form-select
                    id="extension_source_type"
                    name="extension_source_type"
                    :value="old('extension_source_type', $settings['extension_source_type'] ?? 'github')"
                    :options="collect($sourcePresets)->pluck('label', 'value')->all()"
                    xModel="sourceType"
                />
                <x-form-error name="extension_source_type" />
                <p>{{ __('admin/settings/security/extensions.source.source_type_help') }}</p>

                <!-- 公式/サードパーティバッジ -->
                <div class="mt-2">
                    @foreach($sourcePresets as $preset)
                        <template x-if="sourceType === '{{ $preset['value'] }}'">
                            <span>
                                @if($preset['is_official'])
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                                        <i class="fas fa-key text-[10px]"></i>
                                        {{ __('admin/settings/security/extensions.source.official_badge') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                        {{ __('admin/settings/security/extensions.source.third_party_badge') }}
                                    </span>
                                @endif
                            </span>
                        </template>
                    @endforeach
                </div>
            </fieldset>

            <!-- GitHub ソース情報 -->
            <div x-show="sourceType === 'github'" x-cloak>
                <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fab fa-github text-lg"></i>
                        <span class="font-medium">{{ __('admin/settings/security/extensions.source.github_description') }}</span>
                    </div>

                    <!-- 参照先 URL -->
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-link text-xs"></i>
                        <span>{{ __('admin/settings/security/extensions.source.reference_url') }}:</span>
                        <a href="{{ $sourceReferenceUrl }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                            {{ $sourceReferenceUrl }}
                            <i class="fas fa-external-link-alt text-[10px] ml-0.5"></i>
                        </a>
                    </div>

                    <!-- 認証トークン -->
                    <fieldset class="mt-3">
                        <legend>{{ __('admin/settings/security/extensions.source.token') }}</legend>

                        <x-form-text
                            id="extension_source_token"
                            name="extension_source_token"
                            type="password"
                            value=""
                            :placeholder="__('admin/settings/security/extensions.source.token_placeholder')"
                            autocomplete="off"
                        />

                        <div class="mt-1 flex items-center gap-2 text-sm">
                            @if($hasSourceToken)
                                <span class="text-green-600 dark:text-green-400">
                                    <i class="fas fa-check-circle"></i>
                                    {{ __('admin/settings/security/extensions.source.token_saved') }}
                                </span>
                            @else
                                <span class="text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-info-circle"></i>
                                    {{ __('admin/settings/security/extensions.source.token_not_set') }}
                                </span>
                            @endif
                        </div>
                        <p>{{ __('admin/settings/security/extensions.source.token_help') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin/settings/security/extensions.source.token_clear_hint') }}</p>
                    </fieldset>

                    <!-- 接続テスト -->
                    <div class="mt-3">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50"
                            :disabled="testStatus === 'testing'"
                            @click="testConnection()"
                        >
                            <template x-if="testStatus === 'testing'">
                                <i class="fas fa-spinner fa-spin"></i>
                            </template>
                            <template x-if="testStatus !== 'testing'">
                                <i class="fas fa-plug"></i>
                            </template>
                            <span x-text="testStatus === 'testing' ? '{{ __('admin/settings/security/extensions.source.testing') }}' : '{{ __('admin/settings/security/extensions.source.test_connection') }}'"></span>
                        </button>

                        <!-- テスト結果: 成功 -->
                        <div x-show="testStatus === 'success'" x-cloak class="mt-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                            <div class="flex items-center gap-2 text-green-700 dark:text-green-300">
                                <i class="fas fa-check-circle"></i>
                                <span class="font-medium">{{ __('admin/settings/security/extensions.source.connection_success') }}</span>
                            </div>
                            <div class="mt-1 text-sm text-green-600 dark:text-green-400" x-text="testMessage"></div>
                            <template x-if="testDetails.rate_limit">
                                <div class="mt-1 text-xs text-green-500 dark:text-green-500">
                                    {{ __('admin/settings/security/extensions.source.rate_limit_remaining', ['count' => '']) }}<span x-text="testDetails.rate_limit"></span>
                                </div>
                            </template>
                        </div>

                        <!-- テスト結果: 失敗 -->
                        <div x-show="testStatus === 'failed'" x-cloak class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <div class="flex items-center gap-2 text-red-700 dark:text-red-300">
                                <i class="fas fa-times-circle"></i>
                                <span class="font-medium">{{ __('admin/settings/security/extensions.source.connection_failed') }}</span>
                            </div>
                            <div class="mt-1 text-sm text-red-600 dark:text-red-400" x-text="testMessage"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- アップデートチェック間隔 -->
            <fieldset>
                <legend>{{ __('admin/settings/security/extensions.source.update_check_interval') }}</legend>

                <x-form-select
                    id="extension_update_check_interval"
                    name="extension_update_check_interval"
                    :value="old('extension_update_check_interval', $settings['extension_update_check_interval'] ?? 86400)"
                    :options="$checkIntervalOptions"
                />
                <x-form-error name="extension_update_check_interval" />
                <p>{{ __('admin/settings/security/extensions.source.update_check_interval_help') }}</p>
            </fieldset>
        </section>

        </fieldset>
    </form>
</div>
@endsection

@section('save')
@if($modeData['isEditable'] ?? true)
    <x-admin.save-button
        id_confirmation="confirmationModal"
        :label="__('common.save')"
        :title="__('common.save_confirmation_title')"
        :message="__('common.save_confirmation_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="security-extensions-form"
    />
@endif
@endsection
