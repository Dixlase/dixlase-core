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
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Services;

use App\Enums\SafeMode;
use Illuminate\Support\Facades\Log;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Safe mode management service
 *
 * Manages enabling/disabling of session-based safe mode
 * Operates on session only, no database writes
 */
class SafeModeService
{
    /**
     * Enable the specified safe mode
     */
    public function activate(SafeMode $mode): void
    {
        session([$mode->sessionKey() => true]);

        Log::channel('admin_activity')->warning(__('services/safe_mode_service.safe_mode_enabled').$mode->value, [
            'mode' => $mode->value,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name ?? 'unknown',
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Disable the specified safe mode
     */
    public function deactivate(SafeMode $mode): void
    {
        session()->forget($mode->sessionKey());

        Log::channel('admin_activity')->info(__('services/safe_mode_service.safe_mode_disabled').$mode->value, [
            'mode' => $mode->value,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name ?? 'unknown',
            'ip' => request()->ip(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Disable all safe modes
     */
    public function deactivateAll(): void
    {
        foreach (SafeMode::cases() as $mode) {
            if ($this->isActive($mode)) {
                $this->deactivate($mode);
            }
        }
    }

    /**
     * Whether the specified safe mode is enabled
     */
    public function isActive(SafeMode $mode): bool
    {
        return (bool) session($mode->sessionKey(), false);
    }

    /**
     * Get list of enabled safe modes
     *
     * @return SafeMode[]
     */
    public function getActiveModes(): array
    {
        $activeModes = [];

        foreach (SafeMode::cases() as $mode) {
            if ($this->isActive($mode)) {
                $activeModes[] = $mode;
            }
        }

        return $activeModes;
    }

    /**
     * Whether any safe mode is enabled
     */
    public function hasAnyActive(): bool
    {
        foreach (SafeMode::cases() as $mode) {
            if ($this->isActive($mode)) {
                return true;
            }
        }

        return false;
    }
}
