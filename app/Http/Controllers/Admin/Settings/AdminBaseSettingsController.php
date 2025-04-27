<?php

/**
 * This file is part of MySoftware.
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

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\BaseSetting;
use App\Http\Requests\Admin\Settings\AdminBaseSettingsRequest;
use App\Helpers\EnvHelper;
use App\Helpers\TimezoneHelper;
use App\Facades\BaseSettings;
use DateTime;
use DateTimeZone;

class AdminBaseSettingsController extends AdminLoggedInController
{

    //初期設定を行う
    public function __construct()
    {
        // 親クラスのコンストラクタを呼び出す
        parent::__construct();
    }

    /**
     * 設定の表示
     */

    public function index()
    {
        $settings = [
            'app_name' => BaseSettings::get('app_name', 'MySoftware'),
            'locale' => BaseSettings::get('locale', 'ja_JA'),
            'timezone' => BaseSettings::get('timezone', 'Asia/Tokyo'),

            'mail_mailer' => BaseSettings::get('mail_mailer', 'smtp'),
            'mail_host' => BaseSettings::get('mail_host', 'smtp.example.com'),
            'mail_port' => BaseSettings::get('mail_port', '587'),
            'mail_username' => BaseSettings::get('mail_username', ''),
            'mail_password' => BaseSettings::get('mail_password', ''),
            'mail_encryption' => BaseSettings::get('mail_encryption', 'tls'),
            'mail_from_address' => BaseSettings::get('MAIL_FROM_ADDRESS', 'no-reply@example.com'),

            'maintenance_mode' => BaseSetting::getValue('maintenance_mode', 'false'),
            'maintenance_message' => BaseSetting::getValue('maintenance_message', '現在メンテナンス中です。しばらくお待ちください。'),
        ];

        $timezones = TimezoneHelper::getTimezonesWithUtcOffset();


        $this->viewParams['settings'] = $settings;
        $this->viewParams['timezones'] = $timezones;
        $this->viewParams['locales'] = trans('admin.locales');
        $this->viewParams['mailers'] = trans('mail.mailers');
        $this->viewParams['encryptions'] = trans('mail.encryptions');

        return view(
            'admin::settings.base.index',
            $this->viewParams
        );
    }

    /**
     * 設定の更新
     */
    public function update(AdminBaseSettingsRequest $request)
    {


        $settings = $request->only([
            'maintenance_mode',
            'maintenance_message',
        ]);

        $envData = $request->only([
            'app_name',
            'locale',
            'timezone',
            'mail_mailer',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_from_address',
        ]);

        // DBに保存するもの(メンテナンスモードの設定)
        BaseSetting::setMany($settings);
        // .env に保存するもの
        EnvHelper::update($envData);

        return redirect()->route('admin.settings.base')->with('success', '設定が更新されました。');
    }


    private function getTimezonesWithUtcOffset(): array
    {
        $timezones = [];
        $now = new DateTime('now');
        $translations = trans('timezones');

        foreach (DateTimeZone::listIdentifiers() as $timezone) {
            $tz = new DateTimeZone($timezone);
            $offset = $tz->getOffset($now);
            $sign = $offset < 0 ? '-' : '+';
            $hours = str_pad(abs($offset) / 3600, 2, '0', STR_PAD_LEFT);
            $minutes = str_pad(abs($offset) % 3600 / 60, 2, '0', STR_PAD_LEFT);
            $formattedOffset = "UTC{$sign}{$hours}:{$minutes}";
            $label = $translations[$timezone] ?? $timezone;
            $timezones[$timezone] = "（{$formattedOffset}）{$label}";
        }

        return $timezones;
    }
}
