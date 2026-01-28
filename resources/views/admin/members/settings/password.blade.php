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
    <!-- パスワードポリシー設定 -->
    <section>
        <h2>{{ __('admin/members/settings/password.password_policy') }}</h2>
        <p>{{ __('admin/members/settings/password.password_policy_description') }}</p>
        
        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <div class="flex items-start gap-3">
                <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                <div>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mb-2">
                        {{ __('admin/members/settings/password.managed_in_security_settings') }}
                    </p>
                    <a href="{{ route('admin.settings.security.password') }}" class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">
                        <i class="fas fa-external-link-alt"></i>
                        {{ __('admin/members/settings/password.go_to_security_settings') }}
                    </a>
                </div>
            </div>
        </div>
        
        <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ __('admin/members/settings/password.current_policy') }}
            </h3>
            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                <li>• {{ __('admin/members/settings/password.min_length') }}: {{ $passwordMinLength }}{{ __('admin/members/settings/password.characters') }}</li>
                @if($passwordRequireUppercase)
                    <li>• {{ __('admin/members/settings/password.require_uppercase') }}</li>
                @endif
                @if($passwordRequireNumber)
                    <li>• {{ __('admin/members/settings/password.require_number') }}</li>
                @endif
                @if($passwordRequireSymbol)
                    <li>• {{ __('admin/members/settings/password.require_symbol') }}</li>
                @endif
                @if($passwordResetEnabled)
                    <li>• {{ __('admin/members/settings/password.reset_enabled') }}</li>
                @endif
            </ul>
        </div>
    </section>
</div>
@endsection
