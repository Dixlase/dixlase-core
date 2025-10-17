<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Themes\DixlaseDefaultTheme\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateThemeSettingsRequest extends FormRequest
{
    /**
     * リクエストが認可されているか判定
     */
    public function authorize(): bool
    {
        // 管理者のみアクセス可能
        return true;
    }

    /**
     * バリデーションルールを取得
     */
    public function rules(): array
    {
        return [
            // Header & Favicon
            'header_logo_id' => 'nullable|integer|exists:media,id',
            'favicon_id' => 'nullable|integer|exists:media,id',
            
            // Hero Section
            'hero_background_image_id' => 'nullable|integer|exists:media,id',
            'hero_main_title' => 'required|string|max:255',
            'hero_sub_title' => 'nullable|string|max:1000',
            'hero_button_text' => 'nullable|string|max:100',
            'hero_button_link' => 'nullable|string|max:500',
            'hero_button_secondary_text' => 'nullable|string|max:100',
            'hero_button_secondary_link' => 'nullable|string|max:500',
            
            // Footer
            'footer_description' => 'nullable|string|max:1000',
            'footer_links' => 'nullable|array',
            'footer_links.*.title' => 'required|string|max:100',
            'footer_links.*.url' => 'required|string|max:500',
            'footer_copyright' => 'nullable|string|max:500',
            
            // SNS Links
            'footer_sns_instagram' => 'nullable|string|max:500',
            'footer_sns_x' => 'nullable|string|max:500',
            'footer_sns_facebook' => 'nullable|string|max:500',
            'footer_sns_tiktok' => 'nullable|string|max:500',
            'footer_sns_bluesky' => 'nullable|string|max:500',
            'footer_sns_threads' => 'nullable|string|max:500',
            'footer_sns_linkedin' => 'nullable|string|max:500',
            'footer_sns_youtube' => 'nullable|string|max:500',
            'footer_sns_pinterest' => 'nullable|string|max:500',
            'footer_sns_discord' => 'nullable|string|max:500',
            
            // Colors
            'primary_color' => 'required|string|max:7',
            'secondary_color' => 'required|string|max:7',
            'accent_color' => 'required|string|max:7',
        ];
    }

    /**
     * バリデーションエラーメッセージをカスタマイズ
     */
    public function messages(): array
    {
        return [
            'hero_main_title.required' => __('themes::admin.settings.hero.main_title') . 'は必須です。',
            'hero_background_image_id.exists' => '選択された画像が見つかりません。',
            'footer_links.*.title.required' => 'リンクタイトルは必須です。',
            'footer_links.*.url.required' => 'リンクURLは必須です。',
            'primary_color.required' => 'プライマリーカラーは必須です。',
            'secondary_color.required' => 'セカンダリーカラーは必須です。',
            'accent_color.required' => 'アクセントカラーは必須です。',
        ];
    }

    /**
     * バリデーション属性名をカスタマイズ
     */
    public function attributes(): array
    {
        return [
            'header_logo_id' => __('themes::admin.settings.header.header_logo'),
            'favicon_id' => __('themes::admin.settings.header.favicon'),
            'hero_background_image_id' => __('themes::admin.settings.hero.background_image'),
            'hero_main_title' => __('themes::admin.settings.hero.main_title'),
            'hero_sub_title' => __('themes::admin.settings.hero.sub_title'),
            'hero_button_text' => __('themes::admin.settings.hero.button_text'),
            'hero_button_link' => __('themes::admin.settings.hero.button_link'),
            'hero_button_secondary_text' => __('themes::admin.settings.hero.button_secondary_text'),
            'hero_button_secondary_link' => __('themes::admin.settings.hero.button_secondary_link'),
            'footer_description' => __('themes::admin.settings.footer.description'),
            'footer_copyright' => __('themes::admin.settings.footer.copyright'),
            'primary_color' => __('themes::admin.settings.colors.primary_color'),
            'secondary_color' => __('themes::admin.settings.colors.secondary_color'),
            'accent_color' => __('themes::admin.settings.colors.accent_color'),
        ];
    }

    /**
     * バリデーション成功後の処理
     * footer_linksをJSON文字列に変換
     */
    protected function passedValidation(): void
    {
        if ($this->has('footer_links') && is_array($this->footer_links)) {
            $this->merge([
                'footer_links' => json_encode($this->footer_links)
            ]);
        }
    }
}
