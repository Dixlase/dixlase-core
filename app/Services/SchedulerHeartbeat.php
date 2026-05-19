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

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Heartbeat for Laravel's `schedule:run`.
 *
 * `routes/console.php` schedules `touch()` every minute; admin
 * surfaces (Security > Extensions, etc.) read `status()` to decide
 * whether to show "scheduler is running" vs "scheduler is not
 * running — configure cron / start the cron container". This lets
 * operators discover at a glance that their interval setting has no
 * effect because nothing is invoking `schedule:run`.
 *
 * The timestamp lives in the cache. File cache (Dixlase's default)
 * survives container restarts because `storage/framework/cache/data`
 * is on the bind-mounted volume; database cache works the same way.
 * If the cache layer is wiped (e.g. operator clears Redis), the
 * status returns to "not running" until the next scheduler tick — a
 * benign false negative that self-heals within a minute.
 */
class SchedulerHeartbeat
{
    /**
     * Cache key holding the last tick's UNIX timestamp.
     */
    public const CACHE_KEY = 'scheduler:last_tick';

    /**
     * Keep the value around for a day. The status check tolerates
     * much shorter staleness windows, so the only purpose of the TTL
     * is to bound disk / cache growth in pathological cases (e.g.
     * scheduler stopped and never restarted).
     */
    public const TTL_SECONDS = 86400;

    /**
     * Considered "active" when the last tick is within this many
     * seconds. Slack of 3x the schedule cadence (60s) absorbs a tick
     * that ran slightly long or a clock drift.
     */
    public const ACTIVE_WINDOW_SECONDS = 180;

    /**
     * Considered "warning" between ACTIVE_WINDOW and this many
     * seconds. Past this the scheduler is considered stopped.
     */
    public const WARNING_WINDOW_SECONDS = 600;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_WARNING = 'warning';

    public const STATUS_STOPPED = 'stopped';

    /**
     * Record a tick. Called by the scheduled closure in
     * routes/console.php every minute. Idempotent; safe to call
     * from anywhere if a one-off probe is useful.
     */
    public function touch(): void
    {
        Cache::put(self::CACHE_KEY, now()->timestamp, self::TTL_SECONDS);
    }

    /**
     * Most recent tick timestamp, or null if no tick has ever been
     * recorded (e.g. fresh install before the scheduler has run).
     */
    public function lastTick(): ?CarbonInterface
    {
        $timestamp = Cache::get(self::CACHE_KEY);
        if (! is_numeric($timestamp)) {
            return null;
        }

        return Carbon::createFromTimestamp((int) $timestamp);
    }

    /**
     * Snapshot the scheduler's current liveness.
     *
     * @return array{
     *     status: 'active'|'warning'|'stopped',
     *     last_tick: ?CarbonInterface,
     *     last_tick_formatted: ?string,
     *     seconds_since_tick: ?int,
     * }
     */
    public function status(): array
    {
        $last = $this->lastTick();

        if ($last === null) {
            return [
                'status' => self::STATUS_STOPPED,
                'last_tick' => null,
                'last_tick_formatted' => null,
                'seconds_since_tick' => null,
            ];
        }

        $age = max(0, now()->timestamp - $last->timestamp);
        $status = match (true) {
            $age <= self::ACTIVE_WINDOW_SECONDS => self::STATUS_ACTIVE,
            $age <= self::WARNING_WINDOW_SECONDS => self::STATUS_WARNING,
            default => self::STATUS_STOPPED,
        };

        return [
            'status' => $status,
            'last_tick' => $last,
            'last_tick_formatted' => $last->format('Y/m/d H:i:s'),
            'seconds_since_tick' => $age,
        ];
    }
}
