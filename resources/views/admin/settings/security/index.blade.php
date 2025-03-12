{{--
This file is part of MySoftware.

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

@extends('admin::partials.layout')

@section('content')
<div class="w-full min-h-screen">
    <div class="max-w-4xl mx-auto">
        <div class="max-w-4xl mx-auto rounded-lg">
            <div class="p-6">
                <!-- Flash message for success or error -->
                @include('components::flash_message')

                <div x-data="{
                    enableAllowedIPs: {{ $settings['enable_allowed_admin_ips'] ? 'true' : 'false' }},
                    blockedAdminIps: {{ $settings['enable_blocked_admin_ips'] ? 'true' : 'false' }}
                }">
                    <form method="POST" action="{{ route('admin.settings.security.update') }}">
                        @csrf
                        @method('POST')

                        @include('components::form.label', [
                            'for' => 'admin_url',
                            'text' => 'admin.features.settings.security.admin_url',
                        ])
                        @include('components::form.text', [
                            'id' => 'admin_url',
                            'name' => 'admin_url',
                            'value' => old('admin_url', $settings['admin_url']),
                            'required' => true,
                        ])


                        @include('components::form.checkbox', [
                            'label' => '特定のIPアドレスのみ許可',
                            'id' => 'enable_allowed_admin_ips',
                            'name' => 'enable_allowed_admin_ips',
                            'value' => old('enable_allowed_admin_ips', $settings['enable_allowed_admin_ips']),
                            'xModel' => 'enableAllowedIPs' // Alpine.jsに状態をバインド
                        ])

                        @include('components::form.textarea', [
                            'id' => 'allowed_admin_ips',
                            'name' => 'allowed_admin_ips',
                            'value' => $settings['allowed_admin_ips'],
                            'rows' => 10,
                            'placeholder' => '',
                            'class' => '',
                            'readonly' => !$settings['enable_allowed_admin_ips'], // 初期状態
                            'xBindReadonly' => '!enableAllowedIPs', // Alpine.jsでreadonlyを動的に管理
                            'xBindClass' => "{ 'bg-gray-100': !enableAllowedIPs, 'bg-white': enableAllowedIPs }", //readonlyの有無でクラスを切り替え
                        ])

                        @include('components::form.checkbox', [
                            'label' => '特定のIPアドレスを拒否',
                            'id' => 'enable_blocked_admin_ips',
                            'name' => 'enable_blocked_admin_ips',
                            'value' => old('enable_blocked_admin_ips', $settings['enable_blocked_admin_ips']),
                            'xModel' => 'blockedAdminIps' // Alpine.jsに状態をバインド
                        ])

                        @include('components::form.textarea', [
                            'id' => 'blocked_admin_ips',
                            'name' => 'blocked_admin_ips',
                            'value' => $settings['blocked_admin_ips'],
                            'rows' => 10,
                            'placeholder' => '',
                            'required' => false,
                            'class' => '',
                            'readonly' => !$settings['blocked_admin_ips'], //初期状態
                            'xBindReadonly' => '!blockedAdminIps', // Alpine.jsでreadonlyを動的に管理
                            'xBindClass' => "{ 'bg-gray-100': !enableAllowedIPs, 'bg-white': blockedAdminIps }", //readonlyの有無でクラスを切り替え

                        ])

                        @include('components::form.checkbox', [
                            'label' => 'SSLを強制',
                            'id' => 'force_ssl',
                            'name' => 'force_ssl',
                            'value' => old('force_ssl', $settings['force_ssl']),
                        ])




                        <!-- 保存ボタンとモーダル -->
                        @include('components::form.save', [
                            'id' => 'confirmationModal',
                            'onclick' => "openModal('confirmationModal')",
                            'title' => '保存の確認',
                            'message' => '変更内容を保存しますか？',
                            'confirm_label' => '保存',
                            'cancel_label' => '戻る',
                        ])


                        <button type="submit">保存</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function toggleInput(checkbox, textarea) {
        textarea.disabled = !checkbox.checked;
    }

    document.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
        const textarea = checkbox.parentElement.nextElementSibling;
        toggleInput(checkbox, textarea);

        checkbox.addEventListener('change', function() {
            toggleInput(checkbox, textarea);
        });
    });
});
</script>

@endsection
