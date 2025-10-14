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

        {{-- Hero Section --}}
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                {{ __('themes::admin.settings.hero.title') }}
            </h3>
            <div class="space-y-4">
                <x-form.text name="hero_background_image" :label="__('themes::admin.settings.hero.background_image')" :value="old('hero_background_image', $settings->hero_background_image ?? '')" :help="__('themes::admin.settings.hero.background_image_help')" />
                <x-form.text name="hero_main_title" :label="__('themes::admin.settings.hero.main_title')" :value="old('hero_main_title', $settings->hero_main_title ?? '')" required :help="__('themes::admin.settings.hero.main_title_help')" />
                <x-form.textarea name="hero_sub_title" :label="__('themes::admin.settings.hero.sub_title')" :value="old('hero_sub_title', $settings->hero_sub_title ?? '')" rows="2" :help="__('themes::admin.settings.hero.sub_title_help')" />
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-form.text name="hero_button_text" :label="__('themes::admin.settings.hero.button_text')" :value="old('hero_button_text', $settings->hero_button_text ?? '')" />
                    <x-form.text name="hero_button_link" :label="__('themes::admin.settings.hero.button_link')" :value="old('hero_button_link', $settings->hero_button_link ?? '')" />
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-form.text name="hero_button_secondary_text" :label="__('themes::admin.settings.hero.button_secondary_text')" :value="old('hero_button_secondary_text', $settings->hero_button_secondary_text ?? '')" />
                    <x-form.text name="hero_button_secondary_link" :label="__('themes::admin.settings.hero.button_secondary_link')" :value="old('hero_button_secondary_link', $settings->hero_button_secondary_link ?? '')" />
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

                <x-form.text name="footer_copyright" :label="__('themes::admin.settings.footer.copyright')" :value="old('footer_copyright', $settings->footer_copyright ?? '')" :help="__('themes::admin.settings.footer.copyright_help')" />
                
                {{-- SNS Links --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        {{ __('themes::admin.settings.footer.sns_title') }}
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-form.text name="footer_sns_facebook" :label="__('themes::admin.settings.footer.sns_facebook')" :value="old('footer_sns_facebook', $settings->footer_sns_facebook ?? '')" type="url" />
                        <x-form.text name="footer_sns_twitter" :label="__('themes::admin.settings.footer.sns_twitter')" :value="old('footer_sns_twitter', $settings->footer_sns_twitter ?? '')" type="url" />
                        <x-form.text name="footer_sns_instagram" :label="__('themes::admin.settings.footer.sns_instagram')" :value="old('footer_sns_instagram', $settings->footer_sns_instagram ?? '')" type="url" />
                        <x-form.text name="footer_sns_linkedin" :label="__('themes::admin.settings.footer.sns_linkedin')" :value="old('footer_sns_linkedin', $settings->footer_sns_linkedin ?? '')" type="url" />
                        <x-form.text name="footer_sns_youtube" :label="__('themes::admin.settings.footer.sns_youtube')" :value="old('footer_sns_youtube', $settings->footer_sns_youtube ?? '')" type="url" />
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
    <!-- 保存ボタンとモーダル -->
    @include('components.save', [
        'id' => 'confirmationModal',
        'label' => __('common.save'),
        'onclick' => "openModal('confirmationModal')",
        'title' => __('common.save_confirmation_title'),
        'message' => __('common.save_confirmation_message'),
        'confirm_label' => __('common.save'),
        'cancel_label' => __('common.cancel'),
        'form' => 'theme-settings-form',
    ])
@endsection

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
