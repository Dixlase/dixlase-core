{{--
This file is part of Dixlase.
Copyright (C) 2025 exc-D inc.
--}}

@extends('layouts.admin')

@section('content')
<div class="max-w-6xl mx-auto mt-12">
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-400 rounded-lg">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="theme-settings-form" action="{{ route('admin.settings.themes.settings.update') }}" method="POST" class="space-y-6" x-data="themeSettings()">
        @csrf
        @method('PUT')

        {{-- Header & Favicon Settings --}}
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                {{ __('themes::admin.settings.header.title') }}
            </h3>
            <div class="space-y-4">
                <div>
                    @include('components.media-picker', [
                        'name' => 'header_logo_id',
                        'value' => $settings->header_logo_id ?? null,
                        'media' => $headerLogo ?? null,
                        'label' => __('themes::admin.settings.header.header_logo'),
                        'help' => __('themes::admin.settings.header.header_logo_help'),
                        'error' => $errors->first('header_logo_id'),
                        'buttonText' => __('themes::admin.settings.select_logo_image')
                    ])
                </div>
                <div>
                    @include('components.media-picker', [
                        'name' => 'favicon_id',
                        'value' => $settings->favicon_id ?? null,
                        'media' => $favicon ?? null,
                        'label' => __('themes::admin.settings.header.favicon'),
                        'help' => __('themes::admin.settings.header.favicon_help'),
                        'error' => $errors->first('favicon_id'),
                        'aspectRatio' => 'square',
                        'buttonText' => __('themes::admin.settings.select_favicon_image')
                    ])
                </div>
            </div>
        </div>

        {{-- Hero Section --}}
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                {{ __('themes::admin.settings.hero.title') }}
            </h3>
            <div class="space-y-4">
                {{-- Basic Settings --}}
                <div>
                    <div class="space-y-4">
                        <div>
                            @include('components.media-picker', [
                                'name' => 'hero_background_image_id',
                                'value' => $settings->hero_background_image_id ?? null,
                                'media' => $heroBackgroundImage ?? null,
                                'label' => __('themes::admin.settings.hero.background_image'),
                                'help' => __('themes::admin.settings.hero.background_image_help'),
                                'error' => $errors->first('hero_background_image_id'),
                                'aspectRatio' => 'hero',
                                'buttonText' => __('themes::admin.settings.hero.select_background_image')
                            ])
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">
                                {{ __('themes::admin.settings.hero.content_section') }}
                            </label>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-500 mb-1">
                                        {{ __('themes::admin.settings.hero.main_title_label') }}
                                    </label>
                                    <x-form.text name="hero_main_title" :label="__('themes::admin.settings.hero.main_title')" :value="old('hero_main_title', $settings->hero_main_title ?? '')" required :help="__('themes::admin.settings.hero.main_title_help')" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-500 mb-1">
                                        {{ __('themes::admin.settings.hero.sub_title_label') }}
                                    </label>
                                    <x-form.textarea name="hero_sub_title" :label="__('themes::admin.settings.hero.sub_title')" :value="old('hero_sub_title', $settings->hero_sub_title ?? '')" rows="2" :help="__('themes::admin.settings.hero.sub_title_help')" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Primary Button --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('themes::admin.settings.hero.primary_button') }}
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-form.text name="hero_button_text" :label="__('themes::admin.settings.hero.button_text')" :value="old('hero_button_text', $settings->hero_button_text ?? '')" />
                        <x-form.text name="hero_button_link" :label="__('themes::admin.settings.hero.button_link')" :value="old('hero_button_link', $settings->hero_button_link ?? '')" />
                    </div>
                </div>
                
                {{-- Secondary Button --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('themes::admin.settings.hero.secondary_button') }}
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-form.text name="hero_button_secondary_text" :label="__('themes::admin.settings.hero.button_secondary_text')" :value="old('hero_button_secondary_text', $settings->hero_button_secondary_text ?? '')" />
                        <x-form.text name="hero_button_secondary_link" :label="__('themes::admin.settings.hero.button_secondary_link')" :value="old('hero_button_secondary_link', $settings->hero_button_secondary_link ?? '')" />
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer Settings --}}
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                {{ __('themes::admin.settings.footer.title') }}
            </h3>
            <div class="space-y-4">
                <x-form.textarea name="footer_description" :label="__('themes::admin.settings.footer.description')" :value="old('footer_description', $settings->footer_description ?? '')" rows="3" :help="__('themes::admin.settings.footer.description_help')" />
                
                {{-- Footer Links --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('themes::admin.settings.footer.links') }}
                    </label>
                    <div class="space-y-3">
                        <template x-for="(link, index) in footerLinks" :key="index">
                            <div class="flex gap-3 items-start">
                                <input type="text" :name="'footer_links[' + index + '][title]'" x-model="link.title" :placeholder="'{{ __('themes::admin.settings.footer.link_title') }}'" class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <input type="text" :name="'footer_links[' + index + '][url]'" x-model="link.url" :placeholder="'{{ __('themes::admin.settings.footer.link_url') }}'" class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <button type="button" @click="removeFooterLink(index)" class="px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="addFooterLink()" class="mt-3 px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                        <i class="fas fa-plus mr-2"></i>{{ __('themes::admin.settings.footer.add_link') }}
                    </button>
                </div>

                {{-- Copyright --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('themes::admin.settings.footer.copyright_section') }}
                    </label>
                    <x-form.text name="footer_copyright" :label="__('themes::admin.settings.footer.copyright')" :value="old('footer_copyright', $settings->footer_copyright ?? '')" :help="__('themes::admin.settings.footer.copyright_help')" />
                </div>
                
                {{-- SNS Links --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        {{ __('themes::admin.settings.footer.sns_title') }}
                    </label>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">
                                {{ __('themes::admin.settings.footer.sns_social_media') }}
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                {{-- Instagram --}}
                                <div>
                                    <label class="block text-xs font-medium mb-1">Instagram</label>
                                    <div class="flex items-stretch">
                                        <span class="inline-flex items-center w-30 mr-2 text-sm whitespace-nowrap">
                                            instagram.com/
                                        </span>
                                        <input type="text" name="footer_sns_instagram" value="{{ old('footer_sns_instagram', $settings->footer_sns_instagram ?? '') }}" class="flex-1 min-w-0 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md" placeholder="{{ __('themes::admin.settings.footer.sns_username') }}">
                                    </div>
                                </div>
                                
                                {{-- X (Twitter) --}}
                                <div>
                                    <label class="block text-xs font-medium mb-1">X (Twitter)</label>
                                    <div class="flex items-stretch">
                                        <span class="inline-flex items-center w-30 mr-2 text-sm whitespace-nowrap">
                                            x.com/
                                        </span>
                                        <input type="text" name="footer_sns_x" value="{{ old('footer_sns_x', $settings->footer_sns_x ?? '') }}" class="flex-1 min-w-0 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md" placeholder="{{ __('themes::admin.settings.footer.sns_username') }}">
                                    </div>
                                </div>
                                
                                {{-- Facebook --}}
                                <div>
                                    <label class="block text-xs font-medium mb-1">Facebook</label>
                                    <div class="flex items-stretch">
                                        <span class="inline-flex items-center w-30 mr-2 text-sm whitespace-nowrap">
                                            facebook.com/
                                        </span>
                                        <input type="text" name="footer_sns_facebook" value="{{ old('footer_sns_facebook', $settings->footer_sns_facebook ?? '') }}" class="flex-1 min-w-0 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md" placeholder="{{ __('themes::admin.settings.footer.sns_username') }}">
                                    </div>
                                </div>
                                
                                {{-- TikTok --}}
                                <div>
                                    <label class="block text-xs font-medium mb-1">TikTok</label>
                                    <div class="flex items-stretch">
                                        <span class="inline-flex items-center w-30 mr-2 text-sm whitespace-nowrap">
                                            tiktok.com/@
                                        </span>
                                        <input type="text" name="footer_sns_tiktok" value="{{ old('footer_sns_tiktok', $settings->footer_sns_tiktok ?? '') }}" class="flex-1 min-w-0 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md" placeholder="{{ __('themes::admin.settings.footer.sns_username') }}">
                                    </div>
                                </div>
                                
                                {{-- Bluesky --}}
                                <div>
                                    <label class="block text-xs font-medium mb-1">Bluesky</label>
                                    <div class="flex items-stretch">
                                        <span class="inline-flex items-center w-30 mr-2 text-sm whitespace-nowrap">
                                            bsky.app/profile/
                                        </span>
                                        <input type="text" name="footer_sns_bluesky" value="{{ old('footer_sns_bluesky', $settings->footer_sns_bluesky ?? '') }}" class="flex-1 min-w-0 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md" placeholder="{{ __('themes::admin.settings.footer.sns_username') }}">
                                    </div>
                                </div>
                                
                                {{-- Threads --}}
                                <div>
                                    <label class="block text-xs font-medium mb-1">Threads</label>
                                    <div class="flex items-stretch">
                                        <span class="inline-flex items-center w-30 mr-2 text-sm whitespace-nowrap">
                                            threads.net/@
                                        </span>
                                        <input type="text" name="footer_sns_threads" value="{{ old('footer_sns_threads', $settings->footer_sns_threads ?? '') }}" class="flex-1 min-w-0 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md" placeholder="{{ __('themes::admin.settings.footer.sns_username') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">
                                {{ __('themes::admin.settings.footer.sns_professional') }}
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                {{-- LinkedIn --}}
                                <div>
                                    <label class="block text-xs font-medium mb-1">LinkedIn</label>
                                    <div class="flex items-stretch">
                                        <span class="inline-flex items-center w-30 mr-2 text-sm whitespace-nowrap">
                                            linkedin.com/in/
                                        </span>
                                        <input type="text" name="footer_sns_linkedin" value="{{ old('footer_sns_linkedin', $settings->footer_sns_linkedin ?? '') }}" class="flex-1 min-w-0 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md" placeholder="{{ __('themes::admin.settings.footer.sns_username') }}">
                                    </div>
                                </div>
                                
                                {{-- YouTube --}}
                                <div>
                                    <label class="block text-xs font-medium mb-1">YouTube</label>
                                    <div class="flex items-stretch">
                                        <span class="inline-flex items-center w-30 mr-2 text-sm whitespace-nowrap">
                                            youtube.com/@
                                        </span>
                                        <input type="text" name="footer_sns_youtube" value="{{ old('footer_sns_youtube', $settings->footer_sns_youtube ?? '') }}" class="flex-1 min-w-0 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md" placeholder="{{ __('themes::admin.settings.footer.sns_username') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">
                                {{ __('themes::admin.settings.footer.sns_other') }}
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                {{-- Pinterest --}}
                                <div>
                                    <label class="block text-xs font-medium mb-1">Pinterest</label>
                                    <div class="flex items-stretch">
                                        <span class="inline-flex items-center w-30 mr-2 text-sm whitespace-nowrap">
                                            pinterest.com/
                                        </span>
                                        <input type="text" name="footer_sns_pinterest" value="{{ old('footer_sns_pinterest', $settings->footer_sns_pinterest ?? '') }}" class="flex-1 min-w-0 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md" placeholder="{{ __('themes::admin.settings.footer.sns_username') }}">
                                    </div>
                                </div>
                                
                                {{-- Discord --}}
                                <div>
                                    <label class="block text-xs font-medium mb-1">Discord</label>
                                    <div class="flex items-stretch">
                                        <span class="inline-flex items-center w-30 mr-2 text-sm whitespace-nowrap">
                                            discord.gg/
                                        </span>
                                        <input type="text" name="footer_sns_discord" value="{{ old('footer_sns_discord', $settings->footer_sns_discord ?? '') }}" class="flex-1 min-w-0 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md" placeholder="{{ __('themes::admin.settings.footer.sns_invite_code') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Color Settings --}}
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                {{ __('themes::admin.settings.colors.title') }}
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-form.color name="primary_color" :label="__('themes::admin.settings.colors.primary')" :value="old('primary_color', $settings->primary_color ?? '#3b82f6')" />
                <x-form.color name="secondary_color" :label="__('themes::admin.settings.colors.secondary')" :value="old('secondary_color', $settings->secondary_color ?? '#6b7280')" />
                <x-form.color name="accent_color" :label="__('themes::admin.settings.colors.accent')" :value="old('accent_color', $settings->accent_color ?? '#10b981')" />
            </div>
        </div>

    </form>
</div>
@endsection

@section('save')
    <!-- 保存ボタン -->
    @include('components.save', [
        'id_confirmation' => 'confirmationModal',
        'label' => __('common.save'),
        'title' => __('common.save_confirmation_title'),
        'message' => __('common.save_confirmation_message'),
        'confirm_label' => __('common.save'),
        'cancel_label' => __('common.cancel'),
        'form' => 'theme-settings-form',
    ])
@endsection

@push('modals')
    @include('components.modal', [
        'id' => 'confirmationModal',
        'title' => __('common.save_confirmation_title'),
        'message' => __('common.save_confirmation_message'),
        'confirm_label' => __('common.save'),
        'cancel_label' => __('common.cancel'),
        'form' => 'theme-settings-form',
    ])
@endpush

@push('scripts')
<script>
function themeSettings() {
    return {
        footerLinks: @json(old('footer_links', $settings->footer_links ?? [])),
        
        addFooterLink() {
            this.footerLinks.push({ title: '', url: '' });
        },
        
        removeFooterLink(index) {
            this.footerLinks.splice(index, 1);
        }
    }
}
</script>
@endpush
