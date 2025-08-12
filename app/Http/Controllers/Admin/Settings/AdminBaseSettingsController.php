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
            // .env から読み取る設定
            'app_name' => env('APP_NAME', 'MySoftware'),
            'locale' => env('APP_LOCALE', 'ja'),
            'timezone' => env('APP_TIMEZONE', 'Asia/Tokyo'),

            'mail_mailer' => env('MAIL_MAILER', 'smtp'),
            'mail_host' => env('MAIL_HOST', 'smtp.example.com'),
            'mail_port' => env('MAIL_PORT', '587'),
            'mail_username' => env('MAIL_USERNAME', ''),
            'mail_password' => env('MAIL_PASSWORD', ''),
            'mail_encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'mail_from_address' => env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),

            // メンテナンスモードのON/OFFも.envから読み取り
            'maintenance_mode' => env('MAINTENANCE_MODE', 'false'),
            
            // データベースから読み取る設定（メンテナンスメッセージのみ）
            'maintenance_message' => BaseSetting::getValue('maintenance_message', '現在メンテナンス中です。しばらくお待ちください。'),
        ];

        $timezones = TimezoneHelper::getTimezonesWithUtcOffset();


        $this->viewParams['settings'] = $settings;
        $this->viewParams['timezones'] = $timezones;
        $this->viewParams['locales'] = collect(config('admin.locale.available', []))->mapWithKeys(function ($locale, $key) {
            return [$key => $locale['name']];
        })->toArray();
        $this->viewParams['mailers'] = __('mail.mailers');
        $this->viewParams['encryptions'] = __('mail.encryptions');

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


        // DBに保存するもの（メンテナンスメッセージのみ）
        $settings = $request->only([
            'maintenance_message',
        ]);

        // .envに保存するもの（メンテナンスモードのON/OFFも含む）
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
            'maintenance_mode',
        ]);

        // APP_FAKER_LOCALEを選択された言語に基づいて自動設定
        if (isset($envData['locale'])) {
            $availableLocales = config('admin.locale.available', []);
            $envData['faker_locale'] = $availableLocales[$envData['locale']]['faker_locale'] ?? 'ja_JA';
        }

        // DBに保存するもの（メンテナンスメッセージのみ）
        BaseSetting::setMany($settings);
        // .env に保存するもの（メンテナンスモードのON/OFFも含む）
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
