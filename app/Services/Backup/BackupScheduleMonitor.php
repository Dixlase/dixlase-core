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

use App\DTO\Backup\BackupResultDTO;
use App\Helpers\AdminHelper;
use App\Services\SystemNotificationService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Remembers whether the last scheduled backup failed, and says so.
 *
 * `dls:backup:create --scheduled` records every outcome here. A failure
 * raises an alert: the administrator notification mail is sent once, when
 * the alert is first raised, and a warning banner stays on the admin pages
 * of members who can edit the backup settings until a scheduled backup
 * succeeds again. The same shape as ScheduledSecurityCheckMonitor, kept
 * apart because a failed backup is not a security finding and is shown to
 * whoever manages backups.
 *
 * The state is a small JSON file rather than a database row: a failure to
 * write the database is one of the things it has to be able to report.
 *
 * @internal
 */
class BackupScheduleMonitor
{
    /** Menu whose editors see the banner. */
    public const MENU_KEY = 'settings.systems.backup.settings';

    public function __construct(
        private readonly SystemNotificationService $notifications,
    ) {}

    /**
     * Record the outcome of a scheduled backup.
     */
    public function recordScheduledRun(BackupResultDTO $result): void
    {
        $raised = false;
        $this->mutate(function (array $state) use ($result, &$raised): array {
            $state['last_run_at'] = now()->toIso8601String();

            if ($result->success) {
                unset($state['failure']);

                return $state;
            }

            $raised = ! isset($state['failure']);
            $state['failure'] = [
                'raised_at' => $state['failure']['raised_at'] ?? now()->toIso8601String(),
                'last_failed_at' => now()->toIso8601String(),
                'count' => (int) ($state['failure']['count'] ?? 0) + 1,
                'error' => mb_substr((string) $result->error, 0, 500),
            ];

            return $state;
        });

        if ($raised) {
            $this->notify((string) $result->error);
        }
    }

    /**
     * The open failure, or null when the last scheduled backup succeeded.
     *
     * @return array{raised_at: string, last_failed_at: string, count: int, error: string}|null
     */
    public function failure(): ?array
    {
        $failure = $this->state()['failure'] ?? null;

        return is_array($failure) ? $failure : null;
    }

    /**
     * Banners for SystemWarningService.
     *
     * @return array<int, array<string, mixed>>
     */
    public function banners(): array
    {
        try {
            $failure = $this->failure();
            if ($failure === null || ! AdminHelper::canEditMenu(self::MENU_KEY)) {
                return [];
            }

            $prefix = 'admin/settings/systems/backup/settings.schedule.';

            return [[
                'level' => 'warning',
                'icon' => 'fas fa-box-archive',
                'title' => __($prefix.'failed_title'),
                'message' => __($prefix.'failed_message', [
                    'count' => $failure['count'],
                    'error' => $failure['error'],
                ]),
                'actions' => [[
                    'label' => __($prefix.'open_backups'),
                    'url' => route('admin.settings.systems.backup.index'),
                    'style' => 'primary',
                    'method' => 'GET',
                ]],
            ]];
        } catch (Throwable $e) {
            // A banner must never break the admin layout.
            Log::warning('BackupScheduleMonitor: banner build failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    protected function notify(string $error): void
    {
        $prefix = 'admin/settings/systems/backup/settings.schedule.';

        try {
            $this->notifications->sendAdminNotification(
                __($prefix.'failed_title'),
                __($prefix.'failed_mail', ['error' => $error]),
            );
        } catch (Throwable $e) {
            Log::warning('BackupScheduleMonitor: notification failed', ['error' => $e->getMessage()]);
        }
    }

    protected function statePath(): string
    {
        $path = (string) config('backup.schedule.state_file', 'app/private/backup-schedule.json');

        return str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : storage_path($path);
    }

    /**
     * @return array<string, mixed>
     */
    protected function state(): array
    {
        $path = $this->statePath();
        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) @file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Read-modify-write the state file under an exclusive lock.
     *
     * @param  callable(array<string, mixed>): array<string, mixed>  $mutator
     */
    protected function mutate(callable $mutator): void
    {
        $path = $this->statePath();
        File::ensureDirectoryExists(dirname($path));

        $handle = fopen($path, 'c+');
        if ($handle === false) {
            Log::error('BackupScheduleMonitor: cannot open state file', ['path' => $path]);

            return;
        }

        try {
            flock($handle, LOCK_EX);
            $raw = stream_get_contents($handle);
            $state = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
            if (! is_array($state)) {
                $state = [];
            }

            $state = $mutator($state);

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, (string) json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }
}
