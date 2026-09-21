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

// Audit log hash chain (runs hourly)
//
// Links pending audit log entries into the hash chain. Entries stay unprotected
// until they are chained, so this interval is the tamper-detection blind spot.
Schedule::command('audit:integrity build')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/audit-integrity.log'));

// Audit log daily seal (runs daily at 3:30 AM)
//
// Signs each completed day with an HMAC seal. Runs after the file integrity scan
// so the two integrity jobs do not overlap. Days already sealed are skipped, and
// any unchained entries for the target day are chained first, so a missed run is
// recovered on the next one.
Schedule::command('audit:integrity seal')
    ->dailyAt('03:30')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/audit-integrity.log'));

// Maintenance mode auto-release check (runs every minute)
Schedule::command('maintenance:check-auto-release')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Core update / rollback self-heal (runs every minute).
//
// A core update or rollback brackets its source swap with `artisan down`
// / `artisan up`. If that process is killed mid-way (OOM, container
// restart, closed terminal) the site would stay on Laravel's static 503
// with nobody around to run `php artisan up`. This lifts the window once
// the owning process is provably gone; a manual `artisan down` is never
// touched. Cheap when the site is up (one file_exists), so every minute
// is fine. See App\Services\Core\CoreMaintenanceGuard.
Schedule::command('dls:core:heal-maintenance')
    ->everyMinute()
    ->withoutOverlapping();

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
