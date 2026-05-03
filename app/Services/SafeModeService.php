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

namespace App\Services;

use App\Enums\SafeMode;
use Illuminate\Support\Facades\Log;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * セーフモード管理サービス
 *
 * セッションベースのセーフモードの有効化/無効化を管理する。
 * DB書き込みなし、セッションのみで動作する。
 */
class SafeModeService
{
    /**
     * 指定したセーフモードを有効化
     */
    public function activate(SafeMode $mode): void
    {
        session([$mode->sessionKey() => true]);

        Log::channel('admin_activity')->warning('セーフモード有効化: '.$mode->value, [
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
     * 指定したセーフモードを無効化
     */
    public function deactivate(SafeMode $mode): void
    {
        session()->forget($mode->sessionKey());

        Log::channel('admin_activity')->info('セーフモード無効化: '.$mode->value, [
            'mode' => $mode->value,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name ?? 'unknown',
            'ip' => request()->ip(),
            'timestamp' => now(),
        ]);
    }

    /**
     * すべてのセーフモードを無効化
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
     * 指定したセーフモードが有効かどうか
     */
    public function isActive(SafeMode $mode): bool
    {
        return (bool) session($mode->sessionKey(), false);
    }

    /**
     * 有効なセーフモード一覧を取得
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
     * いずれかのセーフモードが有効かどうか
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
