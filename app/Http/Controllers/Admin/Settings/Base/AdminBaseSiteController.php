<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

use App\Contracts\Repositories\BaseSettingRepositoryInterface;
use App\Helpers\AdminModeHelper;
use App\Helpers\ConfigHelper;
use App\Helpers\EnvHelper;
use App\Helpers\TimezoneHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Base\AdminBaseSiteUpdateRequest;

class AdminBaseSiteController extends AdminLoggedInController
{
    protected const SETTING_KEYS = ['app_name', 'locale', 'display_timezone'];

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
        // capability ベースの検出に変更（サードパーティSEOプラグインにも対応）
        // プラグインは plugin.json で `"capabilities": ["seo"]` を宣言することで認識される
        $this->viewParams['seoPluginEnabled'] = \App\Helpers\PluginHelper::hasCapability('seo');
        $this->viewParams['seoPluginFilesPresent'] = \App\Helpers\PluginHelper::hasCapabilityInAnyInstalled('seo');

        return view('admin.settings.base.site', $this->viewParams);
    }

    /**
     * サイト設定の更新
     */
    public function update(AdminBaseSiteUpdateRequest $request)
    {
        $actor = new \App\Actors\MemberActor(\App\Helpers\AdminHelper::getMember());

        \App\Actions\Settings\UpdateSettingsAction::make(
            repository: $this->baseSettingRepository,
            settingsPage: 'base.site',
            settingKeys: static::SETTING_KEYS,
            writeCallback: function ($repo, $data) {
                // .env にはデプロイ時に決まる項目のみ保存（APP_TIMEZONE は UTC 固定で触らない）
                $availableLocales = config('admin.locale.available', []);
                EnvHelper::update([
                    'app_name' => $data['app_name'],
                    'locale' => $data['locale'],
                    'faker_locale' => $availableLocales[$data['locale']]['faker_locale'] ?? 'ja_JA',
                    'fallback_locale' => $data['locale'],
                ]);

                // DBに保存（display_timezone は表示用 TZ。Carbon/DB の TZ は常に UTC）
                $repo->setMultiple([
                    'app_name' => $data['app_name'],
                    'locale' => $data['locale'],
                    'display_timezone' => $data['display_timezone'],
                ]);
            },
        )->execute($actor, $request->validated());

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
