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

namespace App\Services\Captcha;

use App\Captcha\CaptchaDriver;
use App\Captcha\GoogleRecaptchaEnterpriseDriver;
use App\Captcha\GoogleRecaptchaV2Driver;
use App\Captcha\GoogleRecaptchaV3Driver;
use App\Captcha\TurnstileCaptchaDriver;
use App\Models\SecuritySetting;

/**
 * @internal Core use only. Do not reference from plugins/themes.
 *
 * Bridges the captcha subsystem into the CSP builder. When captcha is
 * enabled, the active driver's required external origins are injected into
 * the CSP. When captcha is disabled, no captcha origins appear in the
 * policy at all — sites that don't use captcha don't pay the broader
 * default-allow tax.
 *
 * Exception: on the captcha admin settings page, every driver's origins
 * are included so the operator can test any provider before flipping the
 * captcha_enabled switch. Without this, the live-validation widget cannot
 * load and the test/enable flow deadlocks.
 */
class CaptchaCspProvider implements \App\Contracts\CspPolicyProvider
{
    public function getCspDirectives(): array
    {
        if ($this->isCaptchaSettingsPage()) {
            return $this->collectAllDriverDirectives();
        }

        if (! $this->isCaptchaEnabled()) {
            return [];
        }

        try {
            $driver = app(CaptchaDriver::class);
        } catch (\Throwable) {
            return [];
        }

        return $driver->cspDirectives();
    }

    /**
     * Whether the current request targets the captcha admin settings page.
     *
     * The settings page lets the operator test any of the supported drivers
     * before saving, so the CSP must permit every provider's widget origin
     * regardless of the persisted captcha_enabled flag.
     */
    protected function isCaptchaSettingsPage(): bool
    {
        try {
            $route = request()?->route();
        } catch (\Throwable) {
            return false;
        }

        if ($route === null) {
            return false;
        }

        $name = $route->getName();
        if (! is_string($name)) {
            return false;
        }

        return str_starts_with($name, 'admin.settings.security.captcha');
    }

    /**
     * Merge cspDirectives() from every supported driver.
     *
     * @return array<string, array<int, string>>
     */
    protected function collectAllDriverDirectives(): array
    {
        $merged = [];

        $drivers = [
            new TurnstileCaptchaDriver(),
            new GoogleRecaptchaV2Driver(),
            new GoogleRecaptchaV3Driver(),
            new GoogleRecaptchaEnterpriseDriver(),
        ];

        foreach ($drivers as $driver) {
            foreach ($driver->cspDirectives() as $directive => $values) {
                if (! isset($merged[$directive])) {
                    $merged[$directive] = [];
                }

                foreach ($values as $value) {
                    if (! in_array($value, $merged[$directive], true)) {
                        $merged[$directive][] = $value;
                    }
                }
            }
        }

        return $merged;
    }

    /**
     * Whether captcha is administratively enabled.
     *
     * Uses the raw captcha_enabled setting rather than CaptchaHelper::isEnabled()
     * so that the captcha widget can still load on the admin verification screen
     * before the authentication test result has been recorded.
     */
    protected function isCaptchaEnabled(): bool
    {
        try {
            return filter_var(
                SecuritySetting::get('captcha_enabled', false),
                FILTER_VALIDATE_BOOLEAN
            );
        } catch (\Throwable) {
            return false;
        }
    }
}
