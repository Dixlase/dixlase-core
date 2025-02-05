{{--
This file is part of MySoftware.

Copyright (C) 2025 exc-D inc.
Website: https://exc-d.com

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

                <form action="{{ route('admin.settings.systems.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div>
                        @include('components::form.label', [
                            'for' => 'site_name',
                            'text' => 'common.site_name',
                        ])
                        @include('components::form.text', [
                            'id' => 'site_name',
                            'name' => 'site_name',
                            'value' => old('site_name', $settings['site_name']),
                            'required' => true,
                        ])
                    </div>

                    <div class="mt-4">
                        @include('components::form.label', [
                            'for' => 'language',
                            'text' => 'common.language',
                        ])

                        @include('components::form.select', [
                            'id' => 'language',
                            'name' => 'language',
                            'options' => config('admin.languages.available'),
                            'value' => $settings['language'],
                            'required' => true,
                        ])

                    <div class="mt-4">
                        @include('components::form.label', [
                            'text' => 'admin.pages.settings.systems.is_member_site',
                        ])
                        @include('components::form.hidden', [
                            'id' => 'is_member_site',
                            'name' => 'is_member_site',
                            'value' => '0'
                        ])
                        @include('components::form.radio-group', [
                            'name' => 'is_member_site',
                            'options' => [
                                1 => 'common.yes',
                                0 => 'common.no'
                            ],
                            'value' => $settings['is_member_site'],
                        ])
                    </div>

                    <div class="mt-4">
                        @include('components::form.label', [
                            'text' => 'ゲスト申し込みを許可する',
                        ])
                        @include('components::form.hidden', [
                            'id' => 'allow_guest_registration',
                            'name' => 'allow_guest_registration',
                            'value' => '0'
                        ])
                        @include('components::form.radio-group', [
                            'name' => 'allow_guest_registration',
                            'options' => [
                                1 => 'common.yes',
                                0 => 'common.no'
                            ],
                            'value' => $settings['allow_guest_registration'],
                        ])
                    </div>

                    <div class="mt-4">
                        @include('components::form.label', [
                            'text' => '外部からユーザー登録を可能にする',
                        ])
                        @include('components::form.hidden', [
                            'id' => 'allow_external_registration',
                            'name' => 'allow_external_registration',
                            'value' => '0'
                        ])
                        @include('components::form.radio-group', [
                            'name' => 'allow_external_registration',
                            'options' => [
                                1 => 'common.yes',
                                0 => 'common.no'
                            ],
                            'value' => $settings['allow_external_registration'],
                        ])
                    </div>

                    <div class="mt-4">
                        @include('components::form.label', [
                            'text' => '必須項目の設定',
                        ])
                        @include('components::form.checkbox-group', [
                            'name' => 'required_fields',
                            'options' => [
                                'address' => 'common.address',
                                'phone' => 'common.phone',
                                'gender' => 'common.gender',
                                'birthday' => 'common.birthday',
                            ],
                            'values' => old('required_fields', $settings['required_fields'] ?? []),
                        ])
                    </div>

                    <div class="mt-4">
                        @include('components::form.label', [
                            'text' => 'メンテナンスモード',
                        ])
                        @include('components::form.hidden', [
                            'id' => 'maintenance_mode',
                            'name' => 'maintenance_mode',
                            'value' => '0'
                        ])
                        @include('components::form.radio-group', [
                            'name' => 'maintenance_mode',
                            'options' => [
                                1 => 'はい',
                                0 => 'いいえ'
                            ],
                            'value' => $settings['maintenance_mode'],
                        ])
                    </div>

                    <!-- 保存ボタンとモーダル -->
                    @include('components::form.save', [
                        'id' => 'confirmationModal',
                        'onclick' => "openModal('confirmationModal')",
                        'title' => '保存の確認',
                        'message' => '変更内容を保存しますか？',
                        'confirm_label' => '保存',
                        'cancel_label' => '戻る',
                    ])

                </form>
            </div>
        </div>
    </div>
</div>
@endsection
