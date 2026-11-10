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

namespace App\Helpers;

use App\Contracts\Demo\DemoExpiryProvider;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Helpers for demo mode (DIXLASE_DEMO_MODE).
 *
 * @internal
 */
class DemoModeHelper
{
    /**
     * When the current demo sandbox expires, or null for no expiry line.
     *
     * A demo extension supplies this by registering a DemoExpiryProvider. With
     * none registered, core falls back to the legacy `dixlase.demo_expires_at`
     * config value (written at runtime by a demo extension), then to null. The
     * config fallback is removed once extensions provide the expiry.
     */
    public static function expiresAt(): ?CarbonInterface
    {
        if (App::bound(DemoExpiryProvider::class)) {
            try {
                return App::make(DemoExpiryProvider::class)->expiresAt();
            } catch (Throwable $e) {
                Log::warning('DemoExpiryProvider failed; hiding the demo expiry', ['exception' => $e]);

                return null;
            }
        }

        $legacy = config('dixlase.demo_expires_at');

        return $legacy !== null ? Carbon::parse($legacy) : null;
    }
}
