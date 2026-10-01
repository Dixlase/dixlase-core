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

namespace App\Services\Core;

use App\Models\CoreRelease;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * Ownership record for the maintenance window that a core update or
 * rollback opens, plus the self-heal that lifts it when the owning
 * process dies.
 *
 * Background: CoreUpdater::update() and dls:core:rollback bracket the
 * source swap with `artisan down` / `artisan up`. Whether the window
 * is open was tracked only by an in-memory bool, so a SIGKILL (OOM
 * kill, container restart, operator closing the terminal that ran the
 * detached job) mid-swap leaves the site serving Laravel's static 503
 * forever, and a non-technical operator has no way to run
 * `php artisan up`. This class writes an on-disk "owner" record next
 * to the maintenance sentinel, and healIfOrphaned() lifts the window
 * once the owner is provably gone. It never touches a maintenance
 * window it did not open (no owner file = an operator ran `down` by
 * hand, and that is theirs to lift).
 *
 * The owner file lives under storage/app/private/core-update/ for the
 * same reason as CoreUpdater::inProgressFlagPath(): a mid-update
 * `cache:clear` must not wipe it.
 *
 * Detection runs from two places that still boot when the site is
 * down: the every-minute scheduler command (dls:core:heal-maintenance)
 * and any artisan invocation (AppServiceProvider::boot). A web request
 * cannot self-heal — public/index.php serves the 503 before the
 * framework boots — which is why `down` is also given a `--secret`:
 * the bypass URL is logged so an operator can still reach the panel.
 */
final class CoreMaintenanceGuard
{
    public const OPERATION_UPDATE = 'update';

    public const OPERATION_ROLLBACK = 'rollback';

    /**
     * When the owning pid cannot be probed (no posix, no /proc), treat
     * the window as orphaned after this long. Mirrors the in-progress
     * flag tolerance in AdminSystemUpdatesController (15 minutes: long
     * enough for the slowest observed update, short enough that a real
     * crash does not leave the site dark).
     */
    public const STALE_SECONDS = 900;

    /**
     * @param  string|null  $ownerPath  Override for tests (throwaway dir under storage/framework/testing).
     * @param  string|null  $sentinelPath  Override for tests; defaults to Laravel's pre-boot maintenance sentinel.
     * @param  Closure|null  $lifter  Override for tests; defaults to `Artisan::call('up')`.
     * @param  Closure|null  $pidProbe  Override for tests; fn (int $pid): ?bool — true alive, false dead, null unknown.
     */
    public function __construct(
        private readonly ?string $ownerPath = null,
        private readonly ?string $sentinelPath = null,
        private readonly ?Closure $lifter = null,
        private readonly ?Closure $pidProbe = null,
    ) {}

    /**
     * Why the last healIfOrphaned() could not mark the failure on
     * core_releases, or null when it did (or nothing was lifted).
     */
    private ?string $lastRecordError = null;

    public function lastRecordError(): ?string
    {
        return $this->lastRecordError;
    }

    public static function defaultOwnerPath(): string
    {
        return storage_path('app/private/core-update/.maintenance-owner');
    }

    /**
     * The file public/index.php checks before booting the framework.
     * `artisan down` writes it, `artisan up` removes it.
     */
    public static function defaultSentinelPath(): string
    {
        return storage_path('framework/maintenance.php');
    }

    /**
     * Record this process as the owner of the maintenance window about
     * to be opened, and return the secret to pass to `artisan down`.
     *
     * Call this immediately BEFORE `Artisan::call('down', ...)` so that
     * a crash between the two leaves an owner without a sentinel — a
     * harmless leftover that healIfOrphaned() simply removes — rather
     * than a sentinel without an owner, which it would refuse to lift.
     */
    public function claim(string $operation, ?string $target = null): string
    {
        $secret = Str::random(32);

        $payload = [
            'operation' => $operation,
            'pid' => getmypid(),
            'hostname' => gethostname() ?: null,
            'started_at' => CarbonImmutable::now()->toIso8601String(),
            'target' => $target,
            'secret' => $secret,
        ];

        $path = $this->ownerFile();
        $dir = dirname($path);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

        return $secret;
    }

    /**
     * Drop the ownership record. Call this right after a successful
     * `artisan up`. Do NOT call it when `up` failed — the record is
     * what lets the self-heal lift the window later.
     */
    public function release(): void
    {
        @unlink($this->ownerFile());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function readOwner(): ?array
    {
        $path = $this->ownerFile();
        if (! is_file($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function isMaintenanceActive(): bool
    {
        clearstatcache(true, $this->sentinelFile());

        return file_exists($this->sentinelFile());
    }

    /**
     * Stop the operator's bypass from working, without lifting maintenance.
     *
     * The operator who started an update or rollback carries the bypass
     * cookie, so their admin requests pass the maintenance check in
     * public/index.php. Between swapping vendor/ and re-syncing the
     * extension autoload such a request reaches route registration and
     * fails on a plugin class that cannot be loaded yet (HTTP 500).
     * Laravel's maintenance stub reads the secret from storage/framework/down
     * on every request, so replacing it with a throwaway value invalidates
     * the cookie: the operator sees the same 503 page as everyone else.
     *
     * Returns the payload to hand back to restoreOperatorBypass(), or null
     * when there was nothing to suspend. Never throws.
     *
     * Kept on this class on purpose: a rollback replaces app/ with an older
     * release's, and this class is already loaded by claim() before that.
     *
     * @return array<string, mixed>|null
     */
    public function suspendOperatorBypass(): ?array
    {
        try {
            $mode = app()->maintenanceMode();
            if (! $mode->active()) {
                return null;
            }

            $payload = $mode->data();
            if (empty($payload['secret'])) {
                return null;
            }

            $mode->activate(array_merge($payload, ['secret' => bin2hex(random_bytes(32))]));

            return $payload;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Put back the payload suspendOperatorBypass() replaced, so the bypass URL
     * logged at the start of the operation works again. Never throws.
     *
     * @param  array<string, mixed>|null  $payload
     */
    public function restoreOperatorBypass(?array $payload): void
    {
        if ($payload === null) {
            return;
        }

        try {
            $mode = app()->maintenanceMode();
            if ($mode->active()) {
                $mode->activate($payload);
            }
        } catch (\Throwable) {
            // Maintenance is lifted at the end of the operation anyway.
        }
    }

    /**
     * Operator-facing bypass URL for the secret handed to `artisan down`.
     */
    public static function bypassUrl(string $secret): string
    {
        return rtrim((string) config('app.url'), '/').'/'.$secret;
    }

    /**
     * Lift the maintenance window if — and only if — this class opened it
     * and the owning process is provably gone.
     *
     * Returns a description of what was healed, or null when nothing was
     * done (no maintenance, manual maintenance, or the owner is still
     * running).
     *
     * @return array{operation: string, pid: int, started_at: string|null, reason: string}|null
     */
    public function healIfOrphaned(): ?array
    {
        if (! $this->isMaintenanceActive()) {
            // A record without a sentinel is a leftover from a crash
            // between claim() and `down`, or from an `up` whose release()
            // never ran. Nothing is down, so just tidy it away.
            if ($this->readOwner() !== null) {
                $this->release();
            }

            return null;
        }

        $owner = $this->readOwner();
        if ($owner === null) {
            // Someone ran `artisan down` by hand. Not ours to lift.
            return null;
        }

        $reason = $this->orphanReason($owner);
        if ($reason === null) {
            return null;
        }

        $operation = (string) ($owner['operation'] ?? 'update');
        $pid = (int) ($owner['pid'] ?? 0);
        $startedAt = isset($owner['started_at']) ? (string) $owner['started_at'] : null;

        ($this->lifter ?? static fn () => Artisan::call('up'))();

        $this->release();
        @unlink(CoreUpdater::inProgressFlagPath());

        $message = sprintf(
            'Maintenance mode lifted automatically: the core %s process (pid %d) %s.',
            $operation,
            $pid,
            $reason
        );
        Log::warning('[core-update] '.$message, ['owner' => $owner]);

        // Surface it in the admin panel the same way a failed update is
        // surfaced, so the operator learns the update did not complete.
        // Best-effort: the DB may be mid-migration or unreachable, and a
        // failure to record must never keep the site down — but it is
        // logged and kept in lastRecordError() so it is not invisible.
        $this->lastRecordError = null;
        try {
            CoreRelease::singleton();
            CoreRelease::query()->whereKey(CoreRelease::PRIMARY_ID)->update([
                'update_failed_at' => now(),
                'update_failure_reason' => Str::limit($message, 500),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $this->lastRecordError = $e->getMessage();
            Log::error('[core-update] Maintenance was lifted but the failure could not be recorded on core_releases: '.$e->getMessage());
        }

        return [
            'operation' => $operation,
            'pid' => $pid,
            'started_at' => $startedAt,
            'reason' => $reason,
        ];
    }

    /**
     * Why the recorded owner should be considered gone, or null while it
     * may still be working.
     *
     * @param  array<string, mixed>  $owner
     */
    public function orphanReason(array $owner): ?string
    {
        $pid = (int) ($owner['pid'] ?? 0);

        // A pid only means something inside the namespace that produced it.
        // In the standard Docker layout the scheduler runs in its own `cron`
        // container, so probing the app container's pid there answers "no such
        // process" for a perfectly healthy update — and the heal then lifts
        // maintenance in the middle of a running vendor swap, deletes the
        // in-progress lock (allowing a second update to start) and records a
        // false failure. Observed twice on the sandbox 2026-09-23/24, with
        // ~31 s and ~72 s of work still to go.
        //
        // So the probe is only trusted when the record was written by this
        // host. Otherwise fall through to the age rule, which is namespace
        // independent: a genuinely dead foreign owner is still cleaned up,
        // just after STALE_SECONDS instead of immediately.
        $ownerHost = $owner['hostname'] ?? null;
        $thisHost = gethostname();
        $sameHost = is_string($ownerHost)
            && $ownerHost !== ''
            && is_string($thisHost)
            && $ownerHost === $thisHost;

        if ($sameHost) {
            $alive = $this->probePid($pid);
            if ($alive === true) {
                return null;
            }
            if ($alive === false) {
                return 'is no longer running';
            }
        }

        // Liveness unknown here — either the platform cannot probe, or the
        // owner belongs to another host. Fall back to age.
        $why = $sameHost
            ? 'cannot be probed'
            : sprintf('was started on another host (%s)', is_string($ownerHost) && $ownerHost !== '' ? $ownerHost : 'unknown');

        $startedAt = $owner['started_at'] ?? null;
        if (! is_string($startedAt) || $startedAt === '') {
            return 'left no start time and '.$why;
        }

        try {
            $started = CarbonImmutable::parse($startedAt);
        } catch (\Throwable) {
            return 'left an unreadable start time and '.$why;
        }

        $age = $started->diffInSeconds(CarbonImmutable::now());
        if ($age > self::STALE_SECONDS) {
            return sprintf('%s and started %d seconds ago (limit %d)', $why, $age, self::STALE_SECONDS);
        }

        return null;
    }

    /**
     * true = alive, false = dead, null = cannot tell on this platform.
     */
    private function probePid(int $pid): ?bool
    {
        if ($this->pidProbe !== null) {
            return ($this->pidProbe)($pid);
        }

        if ($pid <= 0) {
            return false;
        }

        if (function_exists('posix_kill')) {
            if (posix_kill($pid, 0)) {
                return true;
            }

            // EPERM (1): the process exists but belongs to another user —
            // still alive. Anything else (ESRCH) means it is gone.
            return posix_get_last_error() === 1;
        }

        if (is_dir('/proc')) {
            return file_exists('/proc/'.$pid);
        }

        return null;
    }

    private function ownerFile(): string
    {
        return $this->ownerPath ?? self::defaultOwnerPath();
    }

    private function sentinelFile(): string
    {
        return $this->sentinelPath ?? self::defaultSentinelPath();
    }
}
