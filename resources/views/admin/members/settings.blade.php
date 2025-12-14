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
<div class="max-w-7xl mx-auto">
    <section>
        <h2>{{ __('admin/members/settings.heading') }}</h2>

        <div class="space-y-2">
            <a href="{{ route('admin.members.settings.password') }}" class="block">
                {{ __('admin/members/settings.nav.password') }}
            </a>
            <a href="{{ route('admin.members.settings.session') }}" class="block">
                {{ __('admin/members/settings.nav.session') }}
            </a>
            <a href="{{ route('admin.members.settings.authentication') }}" class="block">
                {{ __('admin/members/settings.nav.authentication') }}
            </a>
        </div>
    </section>

    <!-- 全メンバー強制ログアウト -->
    <section>
        <h2>{{ __('admin/members/settings.force_logout_heading') }}</h2>
        <p class="mb-3">{{ __('admin/members/settings.force_logout_description') }}</p>
        <!-- 全メンバー強制ログアウト用フォーム -->
        <form id="force-logout-all-form" action="{{ route('admin.members.force-logout-all') }}" method="POST">
            @csrf
        </form>

        <!-- 全メンバー強制ログアウトボタン -->
        <x-form.button
            type="button"
            :label="__('admin/members/settings.force_logout_all_button')"
            variant="warning"
            onclick="openModal('forceLogoutAllModal')"
        />
    </section>
</div>
@endsection
@section('modals')
    <!-- 全メンバー強制ログアウト確認モーダル -->
    <x-modal
        id="forceLogoutAllModal"
        :title="__('admin/members/settings.force_logout_all_modal.title')"
        :message="__('admin/members/settings.force_logout_all_modal.message')"
        :confirm_label="__('admin/members/settings.force_logout_all_modal.confirm_label')"
        :cancel_label="__('common.cancel')"
        form="force-logout-all-form"
        icon_type="warning"
        confirm_color="yellow"
    />
@endsection
@section('scripts')
    <script>
        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            const modals = document.querySelectorAll('[id$="Modal"]');

            modals.forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeModal(this.id);
                    }
                });
            });

            const forceLogoutAllModal = document.getElementById('forceLogoutAllModal');

            if (forceLogoutAllModal) {
                const forceLogoutButton = forceLogoutAllModal.querySelector('button[type="submit"]');
                if (forceLogoutButton) {
                    forceLogoutButton.addEventListener('click', () => {
                        document.getElementById('force-logout-all-form').submit();
                    });
                }
            }

            // 二段階認証方法の動的制御は不要（メール認証は常に有効、Passkeyは単純なチェックボックス）
        });
    </script>
@endsection
