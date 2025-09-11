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

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Captcha\CaptchaDriver;
use App\Models\SecuritySetting;

class CaptchaServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CaptchaDriver::class, function ($app) {
            $driver = SecuritySetting::get('captcha_driver', config('captcha.default', 'google'));
            $config = config("captcha.drivers.{$driver}", []);
            
            if (empty($config) || !isset($config['class'])) {
                throw new \InvalidArgumentException("Captcha driver [{$driver}] is not configured.");
            }

            $driverClass = $config['class'];
            
            if (!class_exists($driverClass)) {
                throw new \InvalidArgumentException("Captcha driver class [{$driverClass}] does not exist.");
            }

            return new $driverClass($config);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
