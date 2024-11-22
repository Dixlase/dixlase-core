{{--
This file is part of Your Software Name.

Copyright (C) 2024 exc-D inc.
Website: https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}




<x-admin-layout :title="$title" :theme="$theme">
    @php
    //$theme_class_header = $theme == 'dark' ? 'bg-gray-900 text-white' : 'bg-white text-gray-900';
    @endphp
    <div class="w-full min-h-screen" x-data="{
        theme: {{ json_encode($theme) }},
        contents_class: $theme === 'dark' ? 'bg-gray-900 text-white' : 'bg-white text-gray-900'}">

        <div class="max-w-4xl mx-auto">
            <div class="max-w-4xl mx-auto rounded-lg" :class="contents_class">
                <div class="p-6">
                    @if (session('success'))
                        <div class="mb-4 p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 p-4 text-red-800 bg-red-100 border border-red-200 rounded-lg">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.settings.systems.update') }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="site_name">サイト名</label>
                            <input type="text" id="site_name" name="site_name" class="mt-1 block w-full rounded-md shadow-sm {{ config('admin.theme_class.' . $theme . '.form_input_text') }}" value="{{ old('site_name', $settings['site_name']) }}" required>
                        </div>

                        <div>
                            <label for="admin_theme">管理画面のテーマ</label>
                            <select id="admin_theme" name="admin_theme" class="mt-1 block rounded-md shadow-sm {{ config('admin.theme_class.' . $theme . '.form_input_text') }}" value="{{ old('site_name', $settings['site_name']) }}" required>
                                <option value="light" {{ $settings['admin_theme'] === 'light' ? 'selected' : '' }}>ライト</option>
                                <option value="dark" {{ $settings['admin_theme'] === 'dark' ? 'selected' : '' }}>ダーク</option>
                            </select>
                        </div>

                        <div>
                            <label>会員サイトにする</label>
                            <input type="hidden" name="is_member_site" value="0">
                            <div class="flex items-center space-x-4">
                                <label><input type="radio" id="is_member_site_yes" name="is_member_site" value="1" class="{{ config('admin.theme_class.' . $theme . '.form_input') }}" {{ $settings['is_member_site'] ? 'checked' : '' }}> はい</label>
                                <label><input type="radio" id="is_member_site_no" name="is_member_site" value="0" class="{{ config('admin.theme_class.' . $theme . '.form_input') }}" {{ !$settings['is_member_site'] ? 'checked' : '' }}> いいえ</label>
                            </div>
                        </div>

                        <div>
                            <label>ゲスト申し込みを許可する</label>
                            <input type="hidden" name="allow_guest_registration" value="0">
                            <div class="flex items-center space-x-4">
                                <label><input type="radio" id="allow_guest_registration_yes" name="allow_guest_registration" value="1" class="{{ config('admin.theme_class.' . $theme . '.form_input') }}" {{ $settings['allow_guest_registration'] ? 'checked' : '' }}> はい</label>
                                <label><input type="radio" id="allow_guest_registration_no" name="allow_guest_registration" value="0" class="{{ config('admin.theme_class.' . $theme . '.form_input') }}" {{ !$settings['allow_guest_registration'] ? 'checked' : '' }}> いいえ</label>
                            </div>
                        </div>

                        <div>
                            <label>外部からユーザー登録を可能にする</label>
                            <input type="hidden" name="allow_external_registration" value="0">
                            <div class="flex items-center space-x-4">
                                <label><input type="radio" id="allow_external_registration_yes" name="allow_external_registration" value="1" class="{{ config('admin.theme_class.' . $theme . '.form_input') }}" {{ $settings['allow_external_registration'] ? 'checked' : '' }}> はい</label>
                                <label><input type="radio" id="allow_external_registration_no" name="allow_external_registration" value="0" class="{{ config('admin.theme_class.' . $theme . '.form_input') }}" {{ !$settings['allow_external_registration'] ? 'checked' : '' }}> いいえ</label>
                            </div>
                        </div>

                        <div>
                            <h5>必須項目の設定</h5>
                            <div class="flex flex-wrap gap-4">
                                <input type="hidden" name="required_fields[address]" value="0">
                                <input type="hidden" name="required_fields[phone]" value="0">
                                <input type="hidden" name="required_fields[gender]" value="0">
                                <input type="hidden" name="required_fields[birthday]" value="0">

                                <label><input type="checkbox" id="required_address" name="required_fields[address]" value="1" class="{{ config('admin.theme_class.' . $theme . '.form_input') }}" {{ old('required_fields.address', $settings['required_fields']['address'] ?? false) ? 'checked' : '' }}> 住所</label>
                                <label><input type="checkbox" id="required_phone" name="required_fields[phone]" value="1" class="{{ config('admin.theme_class.' . $theme . '.form_input') }}" {{ old('required_fields.phone', $settings['required_fields']['phone'] ?? false) ? 'checked' : '' }}> 電話番号</label>
                                <label><input type="checkbox" id="required_gender" name="required_fields[gender]" value="1" class="{{ config('admin.theme_class.' . $theme . '.form_input') }}" {{ old('required_fields.gender', $settings['required_fields']['gender'] ?? false) ? 'checked' : '' }}> 性別</label>
                                <label><input type="checkbox" id="required_birthday" name="required_fields[birthday]" value="1" class="{{ config('admin.theme_class.' . $theme . '.form_input') }}" {{ old('required_fields.birthday', $settings['required_fields']['birthday'] ?? false) ? 'checked' : '' }}> 誕生日</label>
                            </div>
                        </div>

                        <div>
                            <label>メンテナンスモード</label>
                            <input type="hidden" name="maintenance_mode" value="0">
                            <div class="flex items-center space-x-4">
                                <label><input type="radio" id="maintenance_mode_yes" name="maintenance_mode" value="1" class="{{ config('admin.theme_class.' . $theme . '.form_input') }}" {{ $settings['maintenance_mode'] ? 'checked' : '' }}> はい</label>
                                <label><input type="radio" id="maintenance_mode_no" name="maintenance_mode" value="0" class="{{ config('admin.theme_class.' . $theme . '.form_input') }}" {{ !$settings['maintenance_mode'] ? 'checked' : '' }}> いいえ</label>
                            </div>
                        </div>

                        <button type="submit" class="py-2 px-4 rounded-md shadow-sm {{ config('admin.theme_class.' . $theme . '.form_input_button') }}">保存</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
