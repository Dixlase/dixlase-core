{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
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
<div class="mx-auto">
<form id="base-site-form" action="{{ route('admin.settings.base.site.update') }}" method="POST">
    @csrf

    <!-- サイト設定 -->
    <section>
        <h2>{{ __('admin/settings/base/site.site_settings') }}</h2>
        
        <fieldset>
            <legend>{{ __('admin/settings/base/site.app_name') }}</legend>
            <x-form-text
                name="app_name"
                :value="old('app_name', $settings['app_name'])"
                :required="true"
                class="input-full"
            />
        </fieldset>

        <fieldset>
            <legend>{{ __('admin/settings/base/site.site_description') }}</legend>
            <x-form-textarea
                name="site_description"
                :value="old('site_description', $settings['site_description'])"
                :rows="3"
                class="input-full"
            />
            <p>{{ __('admin/settings/base/site.site_description_help') }}</p>
        </fieldset>

        <fieldset>
            <legend>{{ __('admin/settings/base/site.site_keywords') }}</legend>
            <x-form-text
                name="site_keywords"
                :value="old('site_keywords', $settings['site_keywords'])"
                class="input-full"
            />
            <p>{{ __('admin/settings/base/site.site_keywords_help') }}</p>
        </fieldset>
    </section>

    <!-- 言語・地域設定 -->
    <section>
        <h2>{{ __('admin/settings/base/site.language_region_settings') }}</h2>

        <fieldset>
            <legend>{{ __('admin/settings/base/site.locale') }}</legend>
            <x-form-select
                name="locale"
                :options="$locales"
                :value="old('locale', $settings['locale'])"
                :required="true"
            />
        </fieldset>

        <fieldset>
            <legend>{{ __('common.timezone') }}</legend>
            <x-form-select
                name="timezone"
                :options="$timezones"
                :value="$settings['timezone']"
            />
        </fieldset>
    </section>

    <!-- OGP・SEO設定 -->
    <section>
        <h2>{{ __('admin/settings/base/site.ogp_seo_settings') }}</h2>

        <fieldset>
            <legend>{{ __('admin/settings/base/site.default_ogp_image') }}</legend>
            
            <x-media.picker
                name="default_ogp_image_id"
                :value="$settings['default_ogp_image_id']"
                :media="$defaultOgpImage"
                :help="__('admin/settings/base/site.default_ogp_image_help')"
                :error="$errors->first('default_ogp_image_id')"
                aspectRatio="ogp"
            />
        </fieldset>

        <fieldset>
            <legend>{{ __('admin/settings/base/site.twitter_card_type') }}</legend>
            <x-form-select
                name="twitter_card_type"
                :options="[
                    'summary' => __('admin/settings/base/site.twitter_card_summary'),
                    'summary_large_image' => __('admin/settings/base/site.twitter_card_summary_large'),
                ]"
                :value="old('twitter_card_type', $settings['twitter_card_type'])"
            />
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('admin/settings/base/site.twitter_card_type_help') }}</p>
        </fieldset>
    </section>

</form>
</div>
@endsection

@section('save')
    <x-admin.save-button
        id_confirmation="confirmationModal"
        :label="__('common.save')"
        :title="__('common.save_confirmation_title')"
        :message="__('common.save_confirmation_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="base-site-form"
    />
@endsection
