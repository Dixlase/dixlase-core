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

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Scheduler liveness heartbeat. The admin UI reads the recorded
// timestamp to decide whether to display "scheduler is running" vs
// "configure cron / start the cron container". Without this tick the
// operator-facing `extension_update_check_interval` setting silently
// has no effect, because nothing is invoking `schedule:run` from the
// outside world.
Schedule::call(function () {
    app(\App\Services\SchedulerHeartbeat::class)->touch();
})
    ->name('dixlase-scheduler-heartbeat')
    ->everyMinute()
    ->withoutOverlapping();

// File integrity scan (runs daily at 3:00 AM)
Schedule::command('dls:integrity:scan --scheduled')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/integrity-scan.log'));

// Maintenance mode auto-release check (runs every minute)
Schedule::command('maintenance:check-auto-release')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Extension update check (cron ticks hourly, actually runs if the configured interval has elapsed)
//
// Interval controlled by `extension_update_check_interval` setting (86400 / 43200 / 21600 / 0=manual)
// Inside `when()`, determines whether to run by checking if the configured interval has elapsed since the last check
// Designed so that cron does not need to be reconfigured when settings change (re-evaluated on hourly tick)
Schedule::command('dls:source:check')
    ->hourly()
    ->when(function () {
        // Do not run before installation is complete
        $installed = $_SERVER['INSTALLED'] ?? $_ENV['INSTALLED'] ?? env('INSTALLED') ?? config('app.installed');
        if ($installed !== 'true' && $installed !== true) {
            return false;
        }

        try {
            $interval = (int) (\App\Services\SecuritySettingsRegistry::get('extension_update_check_interval')
                ?? config('extension-sources.check_interval', 86400));
        } catch (\Throwable) {
            return false;
        }

        // 0 means manual check only (no automatic execution)
        if ($interval <= 0) {
            return false;
        }

        // Maximum last check time (across plugins and themes). If never run, execute immediately
        try {
            $lastPlugin = \App\Models\Plugin::query()->max('last_version_check');
            $lastTheme = \App\Models\Theme::query()->max('last_version_check');
        } catch (\Throwable) {
            return false;
        }

        $candidates = array_filter([$lastPlugin, $lastTheme]);
        if (empty($candidates)) {
            return true;
        }
        $lastCheck = \Carbon\Carbon::parse(max($candidates));

        return $lastCheck->lt(now()->subSeconds($interval));
    })
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/source-check.log'));
