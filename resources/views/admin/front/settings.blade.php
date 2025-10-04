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
<div class="container mx-auto px-4 py-6">
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg dark:bg-green-900/20 dark:border-green-800 dark:text-green-400">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.front.settings.store') }}" method="POST" id="front-settings-form" class="space-y-6">
        @csrf

        <!-- OGP設定 -->
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">{{ __('admin.settings.front.ogp_settings') }}</h2>

            <fieldset class="mb-6">
                <legend class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('admin.settings.front.front_ogp_image') }}
                </legend>
                
                @include('components.media-picker', [
                    'name' => 'front_ogp_image_id',
                    'value' => $settings['front_ogp_image_id'],
                    'media' => $frontOgpImage,
                    'help' => __('admin.settings.front.front_ogp_image_help'),
                    'error' => $errors->first('front_ogp_image_id'),
                    'aspectRatio' => 'ogp'
                ])
            </fieldset>
        </section>
    </form>
</div>
@endsection

@section('save')
    <!-- 保存ボタンとモーダル -->
    @include('components.save', [
        'id' => 'frontSettingsConfirmationModal',
        'label' => __('common.save'),
        'onclick' => "openModal('frontSettingsConfirmationModal')",
        'title' => __('admin.settings.front.save_confirmation_title'),
        'message' => __('admin.settings.front.save_confirmation_message'),
        'confirm_label' => __('common.save'),
        'cancel_label' => __('common.cancel'),
        'form' => 'front-settings-form',
    ])
@endsection
