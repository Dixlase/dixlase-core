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
<div x-data="{ cspEnabled: {{ old('csp_enabled', $settings['csp_enabled']) ? 'true' : 'false' }} }">
    <form id="security-csp-form" method="POST" action="{{ route('admin.settings.security.csp.update') }}">
        @csrf
        
        <!-- CSP設定 -->
        <section>
            <h2>{{ __('admin/settings/security/csp.title') }}</h2>
            <p>{{ __('admin/settings/security/csp.description') }}</p>

            <!-- CSP有効/無効 -->
            <fieldset class="mb-4">
                <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('admin/settings/security/csp.enabled') }}</legend>
                
                <x-form.toggle
                    :label="__('admin/settings/security/csp.enabled')"
                    id="csp_enabled"
                    name="csp_enabled"
                    :checked="old('csp_enabled', $settings['csp_enabled'] ?? true)"
                    xModel="cspEnabled"
                />
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('admin/settings/security/csp.enabled_help') }}</p>
            </fieldset>

            <!-- CSP詳細設定（CSP有効時のみ操作可能） -->
            <div :class="{ 'opacity-50 pointer-events-none': !cspEnabled }">
                <!-- CSPモード -->
                <fieldset class="mb-4">
                    <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('admin/settings/security/csp.mode') }}</legend>
                    
                    <!-- CSP無効時のデフォルト値 -->
                    <template x-if="!cspEnabled">
                        <input type="hidden" name="csp_mode" value="{{ \App\Enums\CspMode::default()->value }}">
                    </template>
                    
                    <x-form.radio-card-group
                        name="csp_mode"
                        :options="\App\Enums\CspMode::getRadioCardOptions()"
                        :value="old('csp_mode', $settings['csp_mode'] ?? \App\Enums\CspMode::default()->value)"
                        :columns="3"
                    />
                </fieldset>

                <!-- 違反をログに記録 -->
                <fieldset class="mb-4">
                    <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('admin/settings/security/csp.log_violations') }}</legend>
                    
                    <x-form.toggle
                        :label="__('admin/settings/security/csp.log_violations')"
                        id="csp_log_violations"
                        name="csp_log_violations"
                        :checked="old('csp_log_violations', $settings['csp_log_violations'] ?? true)"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('admin/settings/security/csp.log_violations_help') }}</p>
                </fieldset>

                <!-- 開発ツール関連の違反を除外 -->
                <fieldset class="mb-4">
                    <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('admin/settings/security/csp.exclude_dev_tools') }}</legend>
                    
                    <x-form.toggle
                        :label="__('admin/settings/security/csp.exclude_dev_tools')"
                        id="csp_exclude_dev_tools"
                        name="csp_exclude_dev_tools"
                        :checked="old('csp_exclude_dev_tools', $settings['csp_exclude_dev_tools'] ?? true)"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('admin/settings/security/csp.exclude_dev_tools_help') }}</p>
                </fieldset>

                <!-- 信頼済みドメイン -->
                <fieldset class="mb-4">
                    <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('admin/settings/security/csp.trusted_domains') }}</legend>
                    
                    <x-form.textarea
                        id="csp_trusted_domains"
                        name="csp_trusted_domains"
                        :value="old('csp_trusted_domains', $settings['csp_trusted_domains'] ?? '')"
                        :placeholder="__('admin/settings/security/csp.trusted_domains_placeholder')"
                        rows="4"
                        class="input-xl"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('admin/settings/security/csp.trusted_domains_help') }}</p>
                </fieldset>

                <!-- 拒否ドメイン -->
                <fieldset class="mb-4">
                    <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('admin/settings/security/csp.denied_domains') }}</legend>
                    
                    <x-form.textarea
                        id="csp_denied_domains"
                        name="csp_denied_domains"
                        :value="old('csp_denied_domains', $settings['csp_denied_domains'] ?? '')"
                        :placeholder="__('admin/settings/security/csp.denied_domains_placeholder')"
                        rows="4"
                        class="input-xl"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{!! __('admin/settings/security/csp.denied_domains_help') !!}</p>
                </fieldset>

                <!-- ブロックリスト照合設定 -->
                <fieldset class="mb-4" x-data="{ blocklistEnabled: {{ old('csp_blocklist_check_enabled', $settings['csp_blocklist_check_enabled'] ?? false) ? 'true' : 'false' }} }">
                    <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('admin/settings/security/csp.blocklist_check_title') }}</legend>
                    
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">{!! __('admin/settings/security/csp.blocklist_check_description') !!}</p>

                    <!-- 有効/無効 -->
                    <div class="mb-4">
                        <x-form.toggle
                            :label="__('admin/settings/security/csp.blocklist_check_enabled')"
                            id="csp_blocklist_check_enabled"
                            name="csp_blocklist_check_enabled"
                            :checked="old('csp_blocklist_check_enabled', $settings['csp_blocklist_check_enabled'] ?? false)"
                            xModel="blocklistEnabled"
                        />
                    </div>

                    <!-- 検出時のアクション -->
                    <div class="mb-4 pl-6" :class="{ 'opacity-50 pointer-events-none': !blocklistEnabled }">
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">{{ __('admin/settings/security/csp.blocklist_action_label') }}</p>
                        
                        <!-- ブロックリスト無効時のデフォルト値 -->
                        <template x-if="!blocklistEnabled">
                            <input type="hidden" name="csp_blocklist_action" value="{{ \App\Enums\CspBlocklistAction::default()->value }}">
                        </template>
                        
                        <x-form.radio-card-group
                            name="csp_blocklist_action"
                            :options="[
                                [
                                    'value' => '0',
                                    'label' => __('admin/settings/security/csp.blocklist_action_warn'),
                                    'description' => __('admin/settings/security/csp.blocklist_action_warn_desc'),
                                    'icon' => 'fas fa-exclamation-triangle',
                                    'color' => 'yellow',
                                ],
                                [
                                    'value' => '1',
                                    'label' => __('admin/settings/security/csp.blocklist_action_block'),
                                    'description' => __('admin/settings/security/csp.blocklist_action_block_desc'),
                                    'icon' => 'fas fa-ban',
                                    'color' => 'red',
                                ],
                            ]"
                            :value="old('csp_blocklist_action', $settings['csp_blocklist_action'] ?? \App\Enums\CspBlocklistAction::default()->value)"
                            :columns="2"
                        />
                    </div>

                    <!-- カテゴリ選択 -->
                    <div class="space-y-3 pl-6 mb-4" :class="{ 'opacity-50 pointer-events-none': !blocklistEnabled }">
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">{{ __('admin/settings/security/csp.blocklist_check_categories') }}</p>
                        @php
                            $enabledCategories = explode(',', old('csp_blocklist_enabled_categories', $settings['csp_blocklist_enabled_categories'] ?? ''));
                            $blocklistSources = config('csp.blocklist_sources', []);
                            $categoryIcons = [
                                'tracking' => 'fas fa-ad',
                                'malware' => 'fas fa-virus',
                                'cryptominer' => 'fas fa-coins',
                            ];
                        @endphp
                        @foreach($blocklistSources as $categoryKey => $categoryData)
                        @php
                            $isChecked = in_array($categoryKey, $enabledCategories);
                            $toggleId = 'csp_blocklist_category_' . $categoryKey;
                        @endphp
                        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
                            <label for="{{ $toggleId }}" class="flex items-center gap-3 cursor-pointer">
                                <div class="relative inline-flex items-center flex-shrink-0">
                                    <input type="checkbox"
                                           id="{{ $toggleId }}"
                                           name="csp_blocklist_categories[]"
                                           value="{{ $categoryKey }}"
                                           {{ $isChecked ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-11 h-6 rounded-full transition-colors peer-focus:outline-none bg-gray-200 dark:bg-gray-600 peer-checked:bg-indigo-600"></div>
                                    <div class="absolute left-1 top-1 w-4 h-4 bg-white border border-gray-300 rounded-full transition-all peer-checked:translate-x-full peer-checked:border-white"></div>
                                </div>
                                <i class="{{ $categoryIcons[$categoryKey] ?? 'fas fa-list' }} text-gray-500 dark:text-gray-400"></i>
                                <div class="flex-1">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ app()->getLocale() === 'en' ? ($categoryData['name_en'] ?? $categoryData['name']) : $categoryData['name'] }}</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ app()->getLocale() === 'en' ? ($categoryData['description_en'] ?? $categoryData['description']) : $categoryData['description'] }}</p>
                                </div>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </fieldset>

                <!-- カスタムディレクティブ -->
                <fieldset class="mb-4">
                    <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('admin/settings/security/csp.custom_directives') }}</legend>
                    
                    <x-form.textarea
                        id="csp_custom_directives"
                        name="csp_custom_directives"
                        :value="old('csp_custom_directives', $settings['csp_custom_directives'] ?? '')"
                        :placeholder="__('admin/settings/security/csp.custom_directives_placeholder')"
                        rows="4"
                        class="input-xl font-mono text-sm"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('admin/settings/security/csp.custom_directives_help') }}</p>
                </fieldset>
            </div>
        </section>

    </form>
</div>
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
        form="security-csp-form"
    />
@endsection
