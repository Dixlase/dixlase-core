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


    {{-- プロフィール編集フォーム --}}
    <form method="POST" action="{{ route('admin.settings.members.profile.update') }}"
          class="bg-white dark:bg-gray-800 p-6 rounded shadow">
        @csrf

        <!-- 名前 -->
        <div class="mb-4">
            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">名前</label>
            <input type="text" name="name" value="{{ old('name', $member->name) }}"
                   class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded px-3 py-2">
            @error('name')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- 説明 -->
        <div class="mb-4">
            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">説明</label>
            <textarea name="description"
                      class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded px-3 py-2"
                      rows="3">{{ old('description', $member->description) }}</textarea>
            @error('description')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- メールアドレス -->
        <div class="mb-4">
            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">メールアドレス</label>
            <input type="email" name="email" value="{{ old('email', $member->email) }}"
                   class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded px-3 py-2">
            @error('email')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- パスワード -->
        <div class="mb-4">
            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">パスワード（変更する場合のみ）</label>
            <input type="password" name="password"
                   class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded px-3 py-2">
            @error('password')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- パスワード確認 -->
        <div class="mb-6">
            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300">パスワード確認</label>
            <input type="password" name="password_confirmation"
                   class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded px-3 py-2">
        </div>

        <!-- 2段階認証の設定 -->
        <div class="mb-6">
            <label class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">
                2段階認証の設定
            </label>

            @php
                $twoFactorOptions = collect(config('admin.members_two_factor_mode'))
                    ->mapWithKeys(fn ($value) => [$value => __('admin.settings.members.two_factor_mode.options_members.' . $value)])
                    ->toArray();
            @endphp

            @include('components.form.radio-group', [
                'name' => 'two_factor_mode',
                'options' => $twoFactorOptions,
                'value' => old('two_factor_mode', (string) optional($member->two_factor_mode)->value ?? '0'),
                'disabled' => in_array((int) $force2fa, [1, 2]), // ←ここで無効化
            ])

            @php
                $force2fa_name = __('admin.settings.members.two_factor_mode.options_global.' . $force2fa);
            @endphp
            @if ((int) $force2fa !== 0)
                <p class="text-sm mt-3 text-gray-600 dark:text-gray-400">
                    {{__('admin.settings.members.two_factor_mode.force_setting_1')}} {{ $force2fa_name }}」{{__('admin.settings.members.two_factor_mode.force_setting_2')}}
                </p>
            @endif


        </div>

        <div>
            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">
                プロフィールを更新
            </button>
        </div>
    </form>

    <hr class="my-8 border-gray-300 dark:border-gray-600">




@endsection
