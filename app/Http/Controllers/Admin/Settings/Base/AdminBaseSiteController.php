<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

use App\Contracts\Repositories\BaseSettingRepositoryInterface;
use App\Helpers\AdminModeHelper;
use App\Helpers\ConfigHelper;
use App\Helpers\EnvHelper;
use App\Helpers\TimezoneHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Base\AdminBaseSiteUpdateRequest;

class AdminBaseSiteController extends AdminLoggedInController
{
    protected const SETTING_KEYS = ['app_name', 'locale', 'timezone'];

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
            'timezone' => ConfigHelper::getAppTimezone(),
        ];

        $timezones = TimezoneHelper::getTimezonesWithUtcOffset();

        $this->viewParams['settings'] = $settings;
        $this->viewParams['timezones'] = $timezones;
        $this->viewParams['locales'] = collect(config('admin.locale.available', []))->mapWithKeys(function ($locale, $key) {
            return [$key => $locale['name']];
        })->toArray();
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.base.site');
        $this->viewParams['seoPluginEnabled'] = \App\Helpers\PluginHelper::isEnabled('dixlase-seo');
        // プラグインファイルが追加されているか（プラグインマスターで操作可能な状態か）を判定
        // ディレクトリ存在 または DBレコード存在のいずれかがあればプラグインマスターに遷移させる
        $this->viewParams['seoPluginFilesPresent'] = is_dir(base_path('plugins/DixlaseSEO'))
            || \App\Models\Plugin::where('slug', 'dixlase-seo')->exists();

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
                // .envに保存
                $availableLocales = config('admin.locale.available', []);
                EnvHelper::update([
                    'app_name' => $data['app_name'],
                    'locale' => $data['locale'],
                    'timezone' => $data['timezone'],
                    'faker_locale' => $availableLocales[$data['locale']]['faker_locale'] ?? 'ja_JA',
                    'fallback_locale' => $data['locale'],
                ]);

                // DBに保存
                $repo->setMultiple([
                    'app_name' => $data['app_name'],
                    'locale' => $data['locale'],
                    'timezone' => $data['timezone'],
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
