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
use App\Captcha\GoogleRecaptchaDriver;
use App\Captcha\GoogleRecaptchaEnterpriseDriver;
use App\Captcha\TurnstileCaptchaDriver;
use App\Helpers\CaptchaHelper;

class CaptchaServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CaptchaDriver::class, function ($app) {
            $driver = CaptchaHelper::getDriver();
            
            switch ($driver) {
                case 'google':
                    return new GoogleRecaptchaDriver();
                case 'google_enterprise':
                    return new GoogleRecaptchaEnterpriseDriver();
                case 'turnstile':
                    return new TurnstileCaptchaDriver();
                default:
                    throw new \InvalidArgumentException("Captcha driver [{$driver}] is not supported.");
            }
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
