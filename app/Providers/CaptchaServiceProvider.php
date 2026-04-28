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

namespace App\Providers;

use App\Captcha\CaptchaDriver;
use App\Captcha\GoogleRecaptchaEnterpriseDriver;
use App\Captcha\GoogleRecaptchaV2Driver;
use App\Captcha\GoogleRecaptchaV3Driver;
use App\Captcha\TurnstileCaptchaDriver;
use App\Helpers\CaptchaHelper;
use Illuminate\Support\ServiceProvider;

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
                    // バージョンに応じてv2/v3を選択
                    $version = CaptchaHelper::getGoogleVersion();
                    if ($version === 'v3') {
                        return new GoogleRecaptchaV3Driver();
                    } else {
                        // v2_checkbox or v2_invisible
                        return new GoogleRecaptchaV2Driver();
                    }
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
