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
<div x-data="{
    enableAllowedIPs: {{ old('enable_allowed_admin_ips', $settings['enable_allowed_admin_ips']) ? 'true' : 'false' }},
    blockedAdminIps: {{ old('enable_blocked_admin_ips', $settings['enable_blocked_admin_ips']) ? 'true' : 'false' }},
    enableAllowedFrontIPs: {{ old('enable_allowed_front_ips', $settings['enable_allowed_front_ips']) ? 'true' : 'false' }},
    enableBlockedFrontIps: {{ old('enable_blocked_front_ips', $settings['enable_blocked_front_ips']) ? 'true' : 'false' }}
}">
    <form id="security-ip-form" method="POST" action="{{ route('admin.settings.security.ip.update') }}">
        @csrf
        
        <!-- IPアクセス制御設定 -->
        <section>
            <h2>{{ __('admin/settings/security/ip.title') }}</h2>
            <p class="mb-2">{{ __('admin/settings/security/ip.description') }}</p>
            
            <!-- 管理画面IP制御 -->
            <section>
                <h3>{{ __('admin/settings/security/ip.admin_access_control') }}</h3>
                
                <!-- 許可IP設定 -->
                <fieldset>
                    <x-form-toggle
                        :label="__('admin/settings/security/ip.enable_allowed_admin_ips')"
                        id="enable_allowed_admin_ips"
                        name="enable_allowed_admin_ips"
                        :checked="old('enable_allowed_admin_ips', $settings['enable_allowed_admin_ips'])"
                        xModel="enableAllowedIPs"
                    />

                    <div :class="{ 'opacity-50': !enableAllowedIPs }">
                        <x-form-label
                            for="allowed_admin_ips"
                            :text="__('admin/settings/security/ip.allowed_admin_ips_list')"
                            class="text-sm font-medium"
                        />
                        
                        <x-form-textarea
                            id="allowed_admin_ips"
                            name="allowed_admin_ips"
                            :value="$settings['allowed_admin_ips']"
                            :rows="8"
                            :placeholder="__('admin/settings/security/ip.ip_list_placeholder')"
                            class="input-xl"
                            x-bind:disabled="!enableAllowedIPs"
                        />
                        
                        <p class="mb-3">{{ __('admin/settings/security/ip.admin_ip_help') }}</p>
                    </div>
                </fieldset>

                <!-- ブロックIP設定 -->
                <fieldset>
                    <x-form-toggle
                        :label="__('admin/settings/security/ip.enable_blocked_admin_ips')"
                        id="enable_blocked_admin_ips"
                        name="enable_blocked_admin_ips"
                        :checked="old('enable_blocked_admin_ips', $settings['enable_blocked_admin_ips'])"
                        xModel="blockedAdminIps"
                    />

                    <div :class="{ 'opacity-50': !blockedAdminIps }">
                        <x-form-label
                            for="blocked_admin_ips"
                            :text="__('admin/settings/security/ip.blocked_admin_ips_list')"
                            class="text-sm font-medium"
                        />
                        
                        <x-form-textarea
                            id="blocked_admin_ips"
                            name="blocked_admin_ips"
                            :value="$settings['blocked_admin_ips']"
                            :rows="8"
                            :placeholder="__('admin/settings/security/ip.ip_list_placeholder')"
                            class="input-xl"
                            x-bind:disabled="!blockedAdminIps"
                        />
                        
                        <p>{{ __('admin/settings/security/ip.admin_ip_help') }}</p>
                    </div>
                </fieldset>
            </section>

            <!-- フロントエンドIP制御 -->
            <section>
                <h3>{{ __('admin/settings/security/ip.front_access_control') }}</h3>
                
                <!-- 許可IP設定 -->
                <fieldset>
                    <x-form-toggle
                        :label="__('admin/settings/security/ip.enable_allowed_front_ips')"
                        id="enable_allowed_front_ips"
                        name="enable_allowed_front_ips"
                        :checked="old('enable_allowed_front_ips', $settings['enable_allowed_front_ips'])"
                        xModel="enableAllowedFrontIPs"
                    />

                    <div :class="{ 'opacity-50': !enableAllowedFrontIPs }">
                        <x-form-label
                            for="allowed_front_ips"
                            :text="__('admin/settings/security/ip.allowed_front_ips_list')"
                            class="text-sm font-medium"
                        />
                        
                        <x-form-textarea
                            id="allowed_front_ips"
                            name="allowed_front_ips"
                            :value="$settings['allowed_front_ips']"
                            :rows="8"
                            :placeholder="__('admin/settings/security/ip.ip_list_placeholder')"
                            class="input-xl"
                            x-bind:disabled="!enableAllowedFrontIPs"
                        />
                        
                        <p class="mb-3">{{ __('admin/settings/security/ip.front_ip_help') }}</p>
                    </div>
                </fieldset>

                <!-- ブロックIP設定 -->
                <fieldset>
                    <x-form-toggle
                        :label="__('admin/settings/security/ip.enable_blocked_front_ips')"
                        id="enable_blocked_front_ips"
                        name="enable_blocked_front_ips"
                        :checked="old('enable_blocked_front_ips', $settings['enable_blocked_front_ips'])"
                        xModel="enableBlockedFrontIps"
                    />

                    <div :class="{ 'opacity-50': !enableBlockedFrontIps }">
                        <x-form-label
                            for="blocked_front_ips"
                            :text="__('admin/settings/security/ip.blocked_front_ips_list')"
                            class="text-sm font-medium"
                        />
                        
                        <x-form-textarea
                            id="blocked_front_ips"
                            name="blocked_front_ips"
                            :value="$settings['blocked_front_ips']"
                            :rows="8"
                            :placeholder="__('admin/settings/security/ip.ip_list_placeholder')"
                            class="input-xl"
                            x-bind:disabled="!enableBlockedFrontIps"
                        />
                        
                        <p>{{ __('admin/settings/security/ip.front_ip_help') }}</p>
                    </div>
                </fieldset>
            </section>
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
        form="security-ip-form"
    />
@endsection
