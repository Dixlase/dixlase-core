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

namespace App\Http\Controllers\Admin\Settings\Base;

use App\Helpers\ConfigHelper;
use App\Helpers\EnvHelper;
use App\Helpers\TimezoneHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Contracts\Repositories\BaseSettingRepositoryInterface;
use Illuminate\Http\Request;

class AdminBaseSiteController extends AdminLoggedInController
{
    protected BaseSettingRepositoryInterface $baseSettingRepository;

    public function __construct(BaseSettingRepositoryInterface $baseSettingRepository)
    {
        parent::__construct();
        $this->baseSettingRepository = $baseSettingRepository;
    }

    /**
     * サイト設定ページ
     */
    public function index()
    {
        
        $settings = [
            'app_name' => ConfigHelper::getAppName(),
            'site_description' => $this->baseSettingRepository->get('site_description', ''),
            'site_keywords' => $this->baseSettingRepository->get('site_keywords', ''),
            'locale' => $this->getSystemLocale(),
            'timezone' => ConfigHelper::getAppTimezone(),
            'default_ogp_image_id' => $this->baseSettingRepository->get('default_ogp_image_id'),
            'twitter_card_type' => $this->baseSettingRepository->get('twitter_card_type', 'summary_large_image'),
        ];

        $timezones = TimezoneHelper::getTimezonesWithUtcOffset();
        
        $defaultOgpImage = null;
        if ($settings['default_ogp_image_id']) {
            $defaultOgpImage = \App\Models\Media::find($settings['default_ogp_image_id']);
        }

        $this->viewParams['settings'] = $settings;
        $this->viewParams['timezones'] = $timezones;
        $this->viewParams['locales'] = collect(config('admin.locale.available', []))->mapWithKeys(function ($locale, $key) {
            return [$key => $locale['name']];
        })->toArray();
        $this->viewParams['defaultOgpImage'] = $defaultOgpImage;

        return view('admin.settings.base.site', $this->viewParams);
    }

    /**
     * サイト設定の更新
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'site_description' => 'nullable|string|max:1000',
            'site_keywords' => 'nullable|string|max:500',
            'locale' => 'required|string|in:' . implode(',', array_keys(config('admin.locale.available', []))),
            'timezone' => 'required|string|timezone',
            'default_ogp_image_id' => 'nullable|integer|exists:media,id',
            'twitter_card_type' => 'required|string|in:summary,summary_large_image',
        ]);

        // .envに保存
        $envData = [
            'app_name' => $validated['app_name'],
            'locale' => $validated['locale'],
            'timezone' => $validated['timezone'],
        ];

        // APP_FAKER_LOCALEとAPP_FALLBACK_LOCALEを自動設定
        $availableLocales = config('admin.locale.available', []);
        $envData['faker_locale'] = $availableLocales[$validated['locale']]['faker_locale'] ?? 'ja_JA';
        $envData['fallback_locale'] = $validated['locale'];

        EnvHelper::update($envData);

        // DBに保存
        $dbSettings = [
            'app_name' => $validated['app_name'],
            'site_description' => $validated['site_description'] ?? '',
            'site_keywords' => $validated['site_keywords'] ?? '',
            'locale' => $validated['locale'],
            'timezone' => $validated['timezone'],
            'default_ogp_image_id' => $validated['default_ogp_image_id'],
            'twitter_card_type' => $validated['twitter_card_type'],
        ];

        $this->baseSettingRepository->setMultiple($dbSettings);

        return redirect()->route('admin.settings.base.site')
            ->with('success', __('admin/settings/base/site.settings_updated'));
    }

    /**
     * システムの基本言語設定を取得（個人設定を無視）
     */
    private function getSystemLocale(): string
    {
        $envLocale = env('APP_LOCALE');
        if ($envLocale !== null) {
            return $envLocale;
        }

        $dbLocale = $this->baseSettingRepository->get('locale');
        if ($dbLocale !== null) {
            return $dbLocale;
        }

        return 'en';
    }
}
