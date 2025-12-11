{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the  implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@extends('layouts.admin')

@section('content')
<div class="mx-auto">
    <form id="security-extensions-form" method="POST" action="{{ route('admin.settings.security.extensions.update') }}">
        @csrf
        
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
            <h2>{{ __('admin.settings.security.extensions.security.title') }}</h2>
            <p>{{ __('admin.settings.security.extensions.security.description') }}</p>

            <!-- プリセット選択 -->
            <fieldset>
                <legend>{{ __('admin.settings.security.extensions.security.preset_label') }}</legend>
                
                <div class="mt-3">
                    <x-form.radio-card-group
                        name="extension_security_preset"
                        :options="\App\Enums\ExtensionSecurityPreset::getRadioCardOptions()"
                        :value="old('extension_security_preset', $settings['extension_security_preset'])"
                        xModel="preset"
                        :columns="2"
                    />
                </div>
                
                <p class="mt-2">{{ __('admin.settings.security.extensions.security.preset_help') }}</p>
            </fieldset>

            <!-- カスタム設定 -->
            <div class="mt-6 space-y-6" :class="{ 'opacity-50 pointer-events-none': preset !== 'custom' }">
                <div class="flex items-center gap-2 mb-4" x-show="preset !== 'custom'">
                    <i class="fas fa-info-circle text-blue-500"></i>
                    <span class="text-sm text-blue-600 dark:text-blue-400">
                        {{ __('admin.settings.security.extensions.security.custom_mode_hint') }}
                    </span>
                </div>

                <!-- 署名要件 -->
                <fieldset>
                    <legend>{{ __('admin.settings.security.extensions.security.signature_settings') }}</legend>
                    
                    <x-form.toggle
                        :label="__('admin.settings.security.extensions.security.require_signature')"
                        id="extension_require_signature"
                        name="extension_require_signature"
                        :checked="old('extension_require_signature', $settings['extension_require_signature'])"
                        xModel="requireSignature"
                    />
                    <p>{{ __('admin.settings.security.extensions.security.require_signature_help') }}</p>
                </fieldset>

                <!-- 権限定義要件 -->
                <fieldset>
                    <legend>{{ __('admin.settings.security.extensions.security.permission_settings') }}</legend>
                    
                    <x-form.toggle
                        :label="__('admin.settings.security.extensions.security.require_permission_definition')"
                        id="extension_require_permission_definition"
                        name="extension_require_permission_definition"
                        :checked="old('extension_require_permission_definition', $settings['extension_require_permission_definition'])"
                        xModel="requirePermissionDefinition"
                    />
                    <p>{{ __('admin.settings.security.extensions.security.require_permission_definition_help') }}</p>
                    
                    <div class="mt-4">
                        <x-form.toggle
                            :label="__('admin.settings.security.extensions.security.allow_undefined_permissions')"
                            id="extension_allow_undefined_permissions"
                            name="extension_allow_undefined_permissions"
                            :checked="old('extension_allow_undefined_permissions', $settings['extension_allow_undefined_permissions'])"
                            xModel="allowUndefinedPermissions"
                        />
                        <p>{{ __('admin.settings.security.extensions.security.allow_undefined_permissions_help') }}</p>
                    </div>
                </fieldset>

                <!-- プラグイン健全性レベル -->
                <fieldset>
                    <legend>{{ __('admin.settings.security.extensions.security.plugin_health_level') }}</legend>
                    
                    <div class="mt-3">
                        <x-form.range
                            id="extension_plugin_max_health_level"
                            name="extension_plugin_max_health_level"
                            :value="old('extension_plugin_max_health_level', $settings['extension_plugin_max_health_level'])"
                            :min="0"
                            :max="3"
                            :step="1"
                            :labels="\App\Enums\ExtensionSecurityLevel::getRangeLabels()"
                            :labelColors="\App\Enums\ExtensionSecurityLevel::getRangeLabelColors()"
                            xModel="pluginMaxHealthLevel"
                        />
                    </div>
                    
                    <div class="mt-3 p-3 rounded-lg border" :class="getHealthLevelClass(pluginMaxHealthLevel)">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-puzzle-piece"></i>
                            <span class="font-medium">{{ __('admin.settings.security.extensions.security.current_setting') }}:</span>
                            <span x-text="[
                                '{{ __('admin.settings.security.extensions.security.health_level.healthy') }}',
                                '{{ __('admin.settings.security.extensions.security.health_level.warning') }}',
                                '{{ __('admin.settings.security.extensions.security.health_level.needs_attention') }}',
                                '{{ __('admin.settings.security.extensions.security.health_level.not_verified') }}'
                            ][pluginMaxHealthLevel]"></span>
                        </div>
                        <p class="mt-1 text-sm" x-text="[
                            '{{ __('admin.settings.security.extensions.security.health_level_description.healthy') }}',
                            '{{ __('admin.settings.security.extensions.security.health_level_description.warning') }}',
                            '{{ __('admin.settings.security.extensions.security.health_level_description.needs_attention') }}',
                            '{{ __('admin.settings.security.extensions.security.health_level_description.not_verified') }}'
                        ][pluginMaxHealthLevel]"></p>
                    </div>
                    
                    <p class="mt-2">{{ __('admin.settings.security.extensions.security.plugin_health_level_help') }}</p>
                </fieldset>

                <!-- テーマ健全性レベル -->
                <fieldset>
                    <legend>{{ __('admin.settings.security.extensions.security.theme_health_level') }}</legend>
                    
                    <div class="mt-3">
                        <x-form.range
                            id="extension_theme_max_health_level"
                            name="extension_theme_max_health_level"
                            :value="old('extension_theme_max_health_level', $settings['extension_theme_max_health_level'])"
                            :min="0"
                            :max="3"
                            :step="1"
                            :labels="\App\Enums\ExtensionSecurityLevel::getRangeLabels()"
                            :labelColors="\App\Enums\ExtensionSecurityLevel::getRangeLabelColors()"
                            xModel="themeMaxHealthLevel"
                        />
                    </div>
                    
                    <div class="mt-3 p-3 rounded-lg border" :class="getHealthLevelClass(themeMaxHealthLevel)">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-palette"></i>
                            <span class="font-medium">{{ __('admin.settings.security.extensions.security.current_setting') }}:</span>
                            <span x-text="[
                                '{{ __('admin.settings.security.extensions.security.health_level.healthy') }}',
                                '{{ __('admin.settings.security.extensions.security.health_level.warning') }}',
                                '{{ __('admin.settings.security.extensions.security.health_level.needs_attention') }}',
                                '{{ __('admin.settings.security.extensions.security.health_level.not_verified') }}'
                            ][themeMaxHealthLevel]"></span>
                        </div>
                        <p class="mt-1 text-sm" x-text="[
                            '{{ __('admin.settings.security.extensions.security.health_level_description.healthy') }}',
                            '{{ __('admin.settings.security.extensions.security.health_level_description.warning') }}',
                            '{{ __('admin.settings.security.extensions.security.health_level_description.needs_attention') }}',
                            '{{ __('admin.settings.security.extensions.security.health_level_description.not_verified') }}'
                        ][themeMaxHealthLevel]"></p>
                    </div>
                    
                    <p class="mt-2">{{ __('admin.settings.security.extensions.security.theme_health_level_help') }}</p>
                </fieldset>

                <!-- ロジックを含むテーマ -->
                <fieldset>
                    <legend>{{ __('admin.settings.security.extensions.security.logic_themes') }}</legend>
                    
                    <x-form.toggle
                        :label="__('admin.settings.security.extensions.security.allow_logic_themes')"
                        id="extension_allow_logic_themes"
                        name="extension_allow_logic_themes"
                        :checked="old('extension_allow_logic_themes', $settings['extension_allow_logic_themes'])"
                        xModel="allowLogicThemes"
                    />
                    <p>{{ __('admin.settings.security.extensions.security.allow_logic_themes_help') }}</p>
                    
                    <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <div class="flex items-start gap-2">
                            <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                            <div class="text-sm text-blue-700 dark:text-blue-300">
                                <p class="font-medium">{{ __('admin.settings.security.extensions.security.theme_types_title') }}</p>
                                <ul class="mt-1 list-disc list-inside space-y-1">
                                    <li>{{ __('admin.settings.security.extensions.security.theme_type_pure') }}</li>
                                    <li>{{ __('admin.settings.security.extensions.security.theme_type_logic') }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <!-- 権限不一致時の動作 -->
                <fieldset>
                    <legend>{{ __('admin.settings.security.extensions.security.permission_mismatch') }}</legend>
                    <p>{{ __('admin.settings.security.extensions.security.permission_mismatch_help') }}</p>
                    
                    <div class="mt-3">
                        <x-form.radio-card-group
                            name="extension_permission_mismatch_action"
                            :options="[
                                [
                                    'value' => 'warn',
                                    'label' => __('admin.settings.security.extensions.security.mismatch_action.warn'),
                                    'description' => __('admin.settings.security.extensions.security.mismatch_action.warn_description'),
                                    'icon' => 'fas fa-exclamation-triangle',
                                    'color' => 'yellow'
                                ],
                                [
                                    'value' => 'block',
                                    'label' => __('admin.settings.security.extensions.security.mismatch_action.block'),
                                    'description' => __('admin.settings.security.extensions.security.mismatch_action.block_description'),
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
            <h2>{{ __('admin.settings.security.extensions.notification.title') }}</h2>
            <p>{{ __('admin.settings.security.extensions.notification.description') }}</p>

            <!-- メールサーバー設定の確認メッセージ -->
            @if(!($mailConnectionTested && $mailSendTested && $mailReceiveTested))
                <div class="mt-4">
                    <x-message
                        type="warning"
                        :message="__('admin.settings.security.error_notification_mail_test_required', ['url' => route('admin.settings.base')])"
                    />
                </div>
            @endif

            <!-- インストール時に通知 -->
            <fieldset>
                <legend>{{ __('admin.settings.security.extensions.notification.notify_on_install') }}</legend>
                
                <x-form.hidden name="extension_notify_on_install" value="0" />
                
                <x-form.toggle
                    :label="__('admin.settings.security.extensions.notification.notify_on_install')"
                    id="extension_notify_on_install"
                    name="extension_notify_on_install"
                    :checked="old('extension_notify_on_install', $settings['extension_notify_on_install'] ?? true)"
                />
                <p>{{ __('admin.settings.security.extensions.notification.notify_on_install_help') }}</p>
            </fieldset>

            <!-- アンインストール時に通知 -->
            <fieldset>
                <legend>{{ __('admin.settings.security.extensions.notification.notify_on_uninstall') }}</legend>
                
                <x-form.hidden name="extension_notify_on_uninstall" value="0" />
                
                <x-form.toggle
                    :label="__('admin.settings.security.extensions.notification.notify_on_uninstall')"
                    id="extension_notify_on_uninstall"
                    name="extension_notify_on_uninstall"
                    :checked="old('extension_notify_on_uninstall', $settings['extension_notify_on_uninstall'] ?? true)"
                />
                <p>{{ __('admin.settings.security.extensions.notification.notify_on_uninstall_help') }}</p>
            </fieldset>

            <!-- 有効化時に通知 -->
            <fieldset>
                <legend>{{ __('admin.settings.security.extensions.notification.notify_on_enable') }}</legend>
                
                <x-form.hidden name="extension_notify_on_enable" value="0" />
                
                <x-form.toggle
                    :label="__('admin.settings.security.extensions.notification.notify_on_enable')"
                    id="extension_notify_on_enable"
                    name="extension_notify_on_enable"
                    :checked="old('extension_notify_on_enable', $settings['extension_notify_on_enable'] ?? true)"
                />
                <p>{{ __('admin.settings.security.extensions.notification.notify_on_enable_help') }}</p>
            </fieldset>

            <!-- 無効化時に通知 -->
            <fieldset>
                <legend>{{ __('admin.settings.security.extensions.notification.notify_on_disable') }}</legend>
                
                <x-form.hidden name="extension_notify_on_disable" value="0" />
                
                <x-form.toggle
                    :label="__('admin.settings.security.extensions.notification.notify_on_disable')"
                    id="extension_notify_on_disable"
                    name="extension_notify_on_disable"
                    :checked="old('extension_notify_on_disable', $settings['extension_notify_on_disable'] ?? false)"
                />
                <p>{{ __('admin.settings.security.extensions.notification.notify_on_disable_help') }}</p>
            </fieldset>

            <!-- 健全性問題検出時に通知 -->
            <fieldset>
                <legend>{{ __('admin.settings.security.extensions.notification.notify_on_unhealthy') }}</legend>
                
                <x-form.hidden name="extension_notify_on_unhealthy" value="0" />
                
                <x-form.toggle
                    :label="__('admin.settings.security.extensions.notification.notify_on_unhealthy')"
                    id="extension_notify_on_unhealthy"
                    name="extension_notify_on_unhealthy"
                    :checked="old('extension_notify_on_unhealthy', $settings['extension_notify_on_unhealthy'] ?? true)"
                />
                <p>{{ __('admin.settings.security.extensions.notification.notify_on_unhealthy_help') }}</p>
            </fieldset>

            <!-- 操作ログ記録 -->
            <fieldset>
                <legend>{{ __('admin.settings.security.extensions.notification.log_operations') }}</legend>
                
                <x-form.hidden name="extension_log_operations" value="0" />
                
                <x-form.toggle
                    :label="__('admin.settings.security.extensions.notification.log_operations')"
                    id="extension_log_operations"
                    name="extension_log_operations"
                    :checked="old('extension_log_operations', $settings['extension_log_operations'] ?? true)"
                />
                <p>{{ __('admin.settings.security.extensions.notification.log_operations_help') }}</p>
            </fieldset>
        </section>

    </form>
</div>
@endsection

@section('save')
    <x-save
        id_confirmation="confirmationModal"
        :label="__('common.save')"
        :title="__('common.save_confirmation_title')"
        :message="__('common.save_confirmation_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="security-extensions-form"
    />
@endsection
