<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Contracts\Demo;

use Carbon\CarbonInterface;

/**
 * Demo Expiry Provider Contract
 *
 * In demo mode the admin banner can show when the current sandbox expires.
 * Core holds no sandbox lifetime of its own — a demo extension that
 * provisions throwaway tenants knows it. Rather than core carrying a blank
 * config value for an extension to fill at runtime, the extension registers
 * an implementation of this contract and core asks it when rendering the
 * banner.
 *
 * With nothing registered, core falls back to the legacy
 * `dixlase.demo_expires_at` config value, then to no expiry line, so a site
 * without such an extension behaves exactly as before.
 *
 * Register in a ServiceProvider:
 * ```php
 * $this->app->singleton(DemoExpiryProvider::class, MyDemoExpiryProvider::class);
 * ```
 */
interface DemoExpiryProvider
{
    /**
     * When the current demo sandbox expires, or null for no expiry line.
     *
     * Return null when the request is not a sandbox, or the sandbox has no
     * expiry: the banner then omits the expiry line.
     */
    public function expiresAt(): ?CarbonInterface;
}
