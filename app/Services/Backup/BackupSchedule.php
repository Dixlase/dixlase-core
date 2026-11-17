<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

declare(strict_types=1);

namespace App\Services\Backup;

use App\Contracts\Backup\BackupServiceInterface;
use App\Models\BackupRecord;
use App\Models\SiteSetting;
use Illuminate\Support\Carbon;

/**
 * The scheduled backup: whether it runs, what it takes, and whether it is keeping up.
 *
 * routes/console.php ticks `dls:backup:create --scheduled` once a day at
 * RUN_AT and asks isDue() whether this tick should take a backup, so a change
 * on the settings screen takes effect without touching cron. A fresh install
 * has none of the settings stored and gets the defaults: on, daily, the
 * database only, kept for FALLBACK_RETENTION_DAYS days. The database alone
 * keeps the default from filling a small disk with copies of the media
 * library every night.
 *
 * Retention comes from `backup.default_retention_days` when the operator set
 * one, so the scheduled copies expire like the manual ones; the scheduled
 * `dls:backup:cleanup` then deletes them.
 *
 * @internal
 */
final class BackupSchedule
{
    public const SETTING_ENABLED = 'backup.schedule.enabled';

    public const SETTING_FREQUENCY = 'backup.schedule.frequency';

    public const SETTING_TARGETS = 'backup.schedule.targets';

    public const FREQUENCY_DAILY = 'daily';

    public const FREQUENCY_WEEKLY = 'weekly';

    public const FREQUENCIES = [self::FREQUENCY_DAILY, self::FREQUENCY_WEEKLY];

    /** Time of day (server time zone) the scheduled backup runs. */
    public const RUN_AT = '02:30';

    /** The scheduled cleanup runs first, so expired copies free their space. */
    public const CLEANUP_AT = '02:15';

    /** Day a weekly backup runs (Carbon: 0 = Sunday). */
    public const WEEKLY_DAY = Carbon::SUNDAY;

    public const DEFAULT_TARGETS = [BackupServiceInterface::TARGET_DATABASE];

    /** Retention for a scheduled backup when no default retention is set. */
    public const FALLBACK_RETENTION_DAYS = 14;

    public const HEALTH_DISABLED = 'disabled';

    public const HEALTH_NEVER = 'never';

    public const HEALTH_OK = 'ok';

    public const HEALTH_OVERDUE = 'overdue';

    public function __construct(
        private readonly BackupServiceInterface $backups,
    ) {}

    /**
     * Whether scheduled backups are on. Unset means on.
     */
    public function enabled(): bool
    {
        $stored = SiteSetting::getValue(self::SETTING_ENABLED);

        return ! is_string($stored) || $stored === '' || $stored === '1';
    }

    public function frequency(): string
    {
        $stored = SiteSetting::getValue(self::SETTING_FREQUENCY);

        return in_array($stored, self::FREQUENCIES, true) ? $stored : self::FREQUENCY_DAILY;
    }

    /**
     * The targets of a scheduled backup, limited to the targets that exist.
     *
     * @return string[]
     */
    public function targets(): array
    {
        $stored = SiteSetting::getValue(self::SETTING_TARGETS);
        $decoded = is_string($stored) && $stored !== '' ? json_decode($stored, true) : null;
        if (! is_array($decoded)) {
            return self::DEFAULT_TARGETS;
        }

        $targets = array_values(array_intersect($decoded, $this->backups->getAvailableTargets()));

        return $targets !== [] ? $targets : self::DEFAULT_TARGETS;
    }

    public function retentionDays(): int
    {
        $stored = SiteSetting::getValue('backup.default_retention_days');
        $days = is_string($stored) ? (int) $stored : 0;

        return $days > 0 ? $days : self::FALLBACK_RETENTION_DAYS;
    }

    /**
     * Whether the daily tick at RUN_AT should take a backup.
     */
    public function isDue(?Carbon $now = null): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        $now ??= now();

        return $this->frequency() === self::FREQUENCY_DAILY || $now->dayOfWeek === self::WEEKLY_DAY;
    }

    /**
     * Hours without a backup after which the schedule counts as stopped:
     * one interval plus a day of grace for a run that is late or retried.
     */
    public function overdueAfterHours(): int
    {
        return ($this->frequency() === self::FREQUENCY_WEEKLY ? 7 * 24 : 24) + 24;
    }

    /**
     * When the most recent backup of the site was taken, scheduled or not.
     *
     * A backup that retention has since deleted still counts: it shows the
     * schedule ran. A failed one does not.
     */
    public function lastBackupAt(): ?Carbon
    {
        $latest = BackupRecord::query()
            ->where('plugin_slug', 'core')
            ->where('status', '!=', BackupRecord::STATUS_FAILED)
            ->max('created_at');

        return $latest !== null ? Carbon::parse($latest) : null;
    }

    /**
     * @return array{state: string, last_backup_at: Carbon|null}
     */
    public function health(?Carbon $now = null): array
    {
        $last = $this->lastBackupAt();

        if (! $this->enabled()) {
            return ['state' => self::HEALTH_DISABLED, 'last_backup_at' => $last];
        }
        if ($last === null) {
            return ['state' => self::HEALTH_NEVER, 'last_backup_at' => null];
        }

        $overdue = $last->lt(($now ?? now())->copy()->subHours($this->overdueAfterHours()));

        return ['state' => $overdue ? self::HEALTH_OVERDUE : self::HEALTH_OK, 'last_backup_at' => $last];
    }

    /**
     * Whether the installer has finished, read the way `dls:source:check`'s
     * schedule reads it: INSTALLED from the environment first, because it
     * comes from .env and is not in a cached config on every site.
     */
    public static function siteInstalled(): bool
    {
        $fromProcess = getenv('INSTALLED');
        $installed = $_SERVER['INSTALLED'] ?? $_ENV['INSTALLED']
            ?? ($fromProcess !== false ? $fromProcess : config('app.installed'));

        return $installed === 'true' || $installed === true;
    }
}
