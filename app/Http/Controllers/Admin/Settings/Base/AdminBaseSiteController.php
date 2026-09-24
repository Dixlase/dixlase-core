<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

use App\Contracts\Repositories\SiteSettingRepositoryInterface;
use App\Contracts\Site\SiteContextInterface;
use App\Helpers\AdminModeHelper;
use App\Helpers\ConfigHelper;
use App\Helpers\EnvHelper;
use App\Helpers\PluginHelper;
use App\Helpers\TimezoneHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Base\AdminBaseSiteUpdateRequest;
use App\Models\Site;

class AdminBaseSiteController extends AdminLoggedInController
{
    protected const SETTING_KEYS = ['app_name', 'site_tagline', 'locale', 'display_timezone'];

    protected SiteSettingRepositoryInterface $siteSettingRepository;

    protected SiteContextInterface $siteContext;

    public function __construct(
        SiteSettingRepositoryInterface $siteSettingRepository,
        SiteContextInterface $siteContext,
    ) {
        parent::__construct();
        $this->siteSettingRepository = $siteSettingRepository;
        $this->siteContext = $siteContext;
    }

    /**
     * Site settings page.
     */
    public function index()
    {
        $settings = [
            'app_name' => ConfigHelper::getAppName(),
            'site_tagline' => ConfigHelper::getSiteTagline(),
            'locale' => $this->getSystemLocale(),
            'display_timezone' => ConfigHelper::getDisplayTimezone(),
        ];

        $timezones = TimezoneHelper::getTimezonesWithUtcOffset();

        $this->viewParams['settings'] = $settings;
        $this->viewParams['timezones'] = $timezones;
        $this->viewParams['locales'] = collect(config('admin.locale.available', []))->mapWithKeys(function ($locale, $key) {
            return [$key => $locale['name']];
        })->toArray();
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.base.site');
        // Capability-based detection so third-party SEO plugins are also recognised
        // (plugins declare `"capabilities": ["seo"]` in plugin.json).
        $this->viewParams['seoPluginEnabled'] = PluginHelper::hasCapability('seo');
        $this->viewParams['seoPluginFilesPresent'] = PluginHelper::hasCapabilityInAnyInstalled('seo');

        return view('admin.settings.base.site', $this->viewParams);
    }

    /**
     * Update site settings.
     */
    public function update(AdminBaseSiteUpdateRequest $request)
    {
        $actor = new \App\Actors\MemberActor(\App\Helpers\AdminHelper::getMember());
        $siteContext = $this->siteContext;

        \App\Actions\Settings\UpdateSettingsAction::make(
            repository: $this->siteSettingRepository,
            settingsPage: 'base.site',
            settingKeys: static::SETTING_KEYS,
            writeCallback: function ($repo, $data) use ($siteContext) {
                // Persist only deploy-time values to .env. APP_TIMEZONE stays UTC.
                $availableLocales = config('admin.locale.available', []);
                EnvHelper::update([
                    'app_name' => $data['app_name'],
                    'locale' => $data['locale'],
                    'faker_locale' => $availableLocales[$data['locale']]['faker_locale'] ?? 'ja_JA',
                    'fallback_locale' => $data['locale'],
                ]);

                // Persist to the per-site settings store. display_timezone is
                // the display-only TZ; Carbon/DB always operate in UTC.
                $repo->setMultiple([
                    'app_name' => $data['app_name'],
                    'site_tagline' => (string) ($data['site_tagline'] ?? ''),
                    'locale' => $data['locale'],
                    'display_timezone' => $data['display_timezone'],
                ]);

                // Sync the canonical Site.primary_locale column. The
                // SiteSetting('locale') row above is the legacy shadow kept
                // for backward compatibility; new code reads primary_locale
                // directly via SiteContext.
                Site::query()
                    ->whereKey($siteContext->currentSiteId())
                    ->update(['primary_locale' => $data['locale']]);
            },
        )->execute($actor, $request->validated());

        return redirect()->route('admin.settings.base.site')
            ->with('success', __('admin/settings/base/site.settings_updated'));
    }

    /**
     * Get the system base language setting (ignores personal preferences).
     */
    private function getSystemLocale(): string
    {
        $envLocale = env('APP_LOCALE');
        if ($envLocale !== null) {
            return $envLocale;
        }

        $sitePrimary = $this->siteContext->currentSite()->primary_locale ?? null;
        if (is_string($sitePrimary) && $sitePrimary !== '') {
            return $sitePrimary;
        }

        $dbLocale = $this->siteSettingRepository->get('locale');
        if ($dbLocale !== null) {
            return $dbLocale;
        }

        return 'en';
    }
}
