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

    <form method="POST" action="{{ route('admin.profile.basic.update') }}" id="profile-basic-form">
        @csrf

        <!-- 基本情報 -->
        <section class="transition-colors-unified">
            <h2>{{ __('common.basic_info') }}</h2>
            
            <fieldset>
                <legend>{{ __('common.account_name') }}</legend>
                <x-form-text
                    name="account_name"
                    :value="old('account_name', $member->account_name)"
                    :required="true"
                    pattern="^[a-zA-Z0-9]+$"
                    minlength="3"
                    maxlength="20"
                    class="w-full"
                />
                <p class="description-text">{!! __('admin/profile/common.account_name_help') !!}</p>
                @error('account_name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </fieldset>

            <fieldset>
                <legend>{{ __('common.display_name') }}</legend>
                <x-form-text
                    name="display_name"
                    :value="old('display_name', $member->display_name)"
                    class="w-full"
                />
                <p class="description-text">{{ __('admin/profile/common.display_name_help') }}</p>
                @error('display_name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </fieldset>

            <fieldset>
                <legend>{{ __('common.description') }}</legend>
                <x-form-textarea
                    name="description"
                    :value="old('description', $member->description)"
                    :rows="3"
                    class="w-full"
                />
                @error('description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </fieldset>

            <x-form-email
                id="profile_email"
                name="email"
                :value="old('email', $member->email)"
                :required="false"
                :showConfirmation="true"
                :showConfirmationOnChange="true"
            />
            @error('email')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
            @error('email_confirmation')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
            
            @if($hasPendingEmail)
                <div class="mt-2 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        {!! __('admin/profile.pending_email_notice', ['email' => $pendingEmail]) !!}
                    </p>
                    <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">
                        {{ __('admin/profile.current_email', ['email' => $member->email]) }}
                    </p>
                </div>
            @else
                <p class="description-text">
                    @if($isMailServerTested)
                        {!! __('admin/profile.email_change_help') !!}
                    @else
                        {!! __('admin/profile.email_change_help_no_mail') !!}
                    @endif
                </p>
            @endif

            <fieldset>
                <legend>{{ __('common.locale') }}</legend>
                <x-form-select
                    name="locale"
                    :options="$localeOptions"
                    :value="old('locale', $member->locale?->value)"
                    :nullable="true"
                    :nullLabel="__('admin/profile/common.use_system_default')"
                />
                @error('locale')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p>{{ __('admin/profile/common.language_help') }}</p>
            </fieldset>
        </section>

    </form>

@endsection

@section('save')
    <x-admin.save-button
        id_confirmation="confirmProfileBasicModal"
        :label="__('common.update')"
        :title="__('admin/profile.confirm_title')"
        :message="__('admin/profile.confirm_message')"
        :confirm_label="__('common.update')"
        :cancel_label="__('common.cancel')"
        form="profile-basic-form"
    />
@endsection
