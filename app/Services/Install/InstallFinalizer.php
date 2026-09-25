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

declare(strict_types=1);

namespace App\Services\Install;

use App\Models\CoreVersionHistory;
use App\Services\AuditLogIntegrityService;
use App\Services\Core\CoreUpdater;
use App\Support\Install\EnvFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Marks an installation finished.
 *
 * Until this runs, `.env` still says `INSTALLED=false` even though the
 * database is fully migrated — the wizard leaves the flag alone until
 * the operator answers the completion screen. `dls:install` has no
 * screen to answer, so it calls the same steps directly.
 */
class InstallFinalizer
{
    /**
     * Write the completion state to .env and do the bookkeeping.
     *
     * Returns the session cookie name when one was generated, so a caller
     * that still has a live session can update its own config to match.
     */
    public function finalize(): ?string
    {
        $envUpdates = [
            'INSTALLED' => 'true',
            'SESSION_DRIVER' => 'guard-aware-database',
        ];

        $sessionCookie = $this->resolveSessionCookieName();
        if ($sessionCookie !== null) {
            $envUpdates['SESSION_COOKIE'] = $sessionCookie;
            Log::channel('install')->info('Session cookie named for this installation', ['cookie' => $sessionCookie]);
        }

        EnvFile::update($envUpdates);

        // Apply immediately, so anything later in this process sees it.
        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        $this->recordBaselineVersionHistory();
        $this->buildAuditLogHashChain();

        return $sessionCookie;
    }

    /**
     * Insert a baseline `core_version_history` row when both:
     *   1. `VERSION` file exists at the repo root
     *      (`CoreUpdater::readVersionFromDisk()` returns non-null)
     *   2. The ledger is currently empty
     *
     * This is the fix for issue #171 Finding D: without a baseline row,
     * the first `dls:core:update` after a clean install records
     * `from: 0.0.0`, which subsequently breaks `dls:core:rollback`
     * (Finding B — rollback re-fetches vendor from GitHub release
     * `v0.0.0`, which does not exist).
     *
     * Wrapped in try/catch so a bookkeeping failure never fails the
     * install — the operator can `dls:core:reconcile --confirm` after
     * the fact if this ever misfires.
     *
     * `protected` (not `private`) so tests can exercise this method in
     * isolation, without invoking `finalize()` which mutates `.env` and
     * therefore leaks state across the install test suite.
     *
     * Returns the created row for callers who want to log it, or null
     * when the guard skipped the write (either condition failed) or
     * an exception was swallowed.
     */
    public function recordBaselineVersionHistory(): ?CoreVersionHistory
    {
        try {
            $onDiskVersion = CoreUpdater::readVersionFromDisk();
            $ledgerEmpty = CoreVersionHistory::query()->doesntExist();

            if ($onDiskVersion === null || ! $ledgerEmpty) {
                Log::channel('install')->info('Baseline core_version_history row NOT recorded', [
                    'on_disk_version' => $onDiskVersion,
                    'ledger_empty' => $ledgerEmpty,
                ]);

                return null;
            }

            $row = CoreVersionHistory::create([
                'old_version' => null,
                'new_version' => $onDiskVersion,
                'files_changed_count' => 0,
                'lines_added' => 0,
                'lines_removed' => 0,
                'signing_key_changed' => false,
                'author_id_changed' => false,
                'installation_method' => 'install',
                'installed_from_url' => null,
                'downloaded_sha256' => null,
                'applied_by_id' => null,
                'applied_at' => now(),
            ]);

            Cache::forget(CoreVersionHistory::CURRENT_VERSION_CACHE_KEY);
            Log::channel('install')->info("Recorded baseline core_version_history row: v{$onDiskVersion} (installation_method=install)");

            return $row;
        } catch (\Throwable $e) {
            // Never fail the install over a bookkeeping row — log and continue.
            Log::channel('install')->warning('Failed to record baseline core_version_history row (non-fatal): '.$e->getMessage());

            return null;
        }
    }

    /**
     * Link every unchained audit log entry into the hash chain.
     *
     * Runs once at install completion so install-time events are protected
     * immediately and the dashboard shows a working chain instead of an
     * empty one for up to an hour. No daily seal can exist yet — seals only
     * cover completed days — so nothing is sealed here.
     *
     * Returns the number of entries chained, or null when the step was
     * skipped because of an error (never fails the install).
     */
    public function buildAuditLogHashChain(): ?int
    {
        try {
            $result = app(AuditLogIntegrityService::class)->buildPendingChains();

            Log::channel('install')->info('Audit log hash chain built at install completion', [
                'processed' => $result['processed'],
                'remaining' => $result['remaining'],
                'errors' => count($result['errors']),
            ]);

            return (int) $result['processed'];
        } catch (\Throwable $e) {
            // The hourly scheduler picks these rows up — log and continue.
            Log::channel('install')->warning('Failed to build the audit log hash chain at install completion (non-fatal): '.$e->getMessage());

            return null;
        }
    }

    /**
     * The session cookie name to write at finalize, or null to keep the
     * existing one.
     *
     * Returns null when .env already carries a name — a re-install must not
     * invalidate the sessions of an already-running site.
     */
    public function resolveSessionCookieName(): ?string
    {
        if (EnvFile::read('SESSION_COOKIE') !== '') {
            return null;
        }

        $siteName = null;

        try {
            $siteName = app(\App\Services\Site\SettingResolver::class)->get('site_name');
        } catch (\Throwable $e) {
            Log::channel('install')->warning('Failed to read site_name for the session cookie name: '.$e->getMessage());
        }

        return self::sessionCookieName(is_string($siteName) && $siteName !== '' ? $siteName : config('app.name'));
    }

    /**
     * Build a session cookie name unique to this installation.
     *
     * Two Dixlase sites on one hostname (localhost:8080 and localhost:8081,
     * say) would otherwise share a cookie and overwrite each other's
     * session, which surfaces as CSRF 419 errors.
     */
    public static function sessionCookieName(?string $siteName): string
    {
        $slug = Str::slug((string) $siteName) ?: 'dixlase';

        return strtolower($slug.'_'.Str::random(4).'_session');
    }
}
