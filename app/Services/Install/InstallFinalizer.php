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

use App\Models\AuditLog;
use App\Models\CoreVersionHistory;
use App\Models\Member;
use App\Services\AuditLogIntegrityService;
use App\Services\AuditService;
use App\Services\Core\CoreUpdater;
use App\Services\FileIntegrityService;
use App\Services\Security\IntegrityKeyring;
use App\Support\Install\EnvFile;
use App\Support\Install\InstallLogFile;
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

        $integrityKey = $this->newIntegrityKey();
        if ($integrityKey !== null) {
            $envUpdates['INTEGRITY_KEY'] = $integrityKey;
        }

        EnvFile::update($envUpdates);

        // Apply immediately, so anything later in this process sees it.
        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';
        if ($integrityKey !== null) {
            // So the chain built below is keyed from the first row.
            config(['security.integrity_keys.current' => $integrityKey]);
        }

        // The installer wrote the file-integrity baseline before the key
        // existed; seal it as written (#803).
        $this->sealInstallBaseline();

        $baseline = $this->recordBaselineVersionHistory();
        // Before the chain is built, so the install's own entry is inside
        // that pass rather than left behind it.
        $this->recordInstallAudit($baseline);
        $this->buildAuditLogHashChain();
        $this->scheduleInstallLogDiscard();

        return $sessionCookie;
    }

    /**
     * Seal the file-integrity baseline the installer has just written.
     *
     * A no-op when there is no key, no baseline, or it is sealed already.
     * Never fails the install: an unsealed baseline is reported by the scan.
     */
    private function sealInstallBaseline(): void
    {
        try {
            if (app(FileIntegrityService::class)->sealExistingBaseline()) {
                Log::channel('install')->info('File integrity baseline sealed with the integrity key');
            }
        } catch (\Throwable $e) {
            Log::channel('install')->warning('Could not seal the file integrity baseline', ['error' => $e->getMessage()]);
        }
    }

    /**
     * A new integrity key for this site (#665), or null when one is already
     * set (the installer, or an operator, put it in .env beforehand).
     *
     * Only a new install gets one this way: an existing site's .env is never
     * written by an update, so its operator creates the key with
     * `dls:integrity:key`.
     */
    private function newIntegrityKey(): ?string
    {
        if (EnvFile::read('INTEGRITY_KEY') !== '' || app(IntegrityKeyring::class)->isConfigured()) {
            return null;
        }

        return IntegrityKeyring::generate(IntegrityKeyring::FIRST_VERSION);
    }

    /**
     * Record the install in the audit log.
     *
     * The log's first line on a new site used to be whatever happened
     * after the install, so the event that created the site -- and the
     * account that became its first administrator -- was the one event the
     * log did not hold. `core_version_history` answers "which version";
     * only the hash-chained log answers "when, by whom, from where", and
     * it is the log `audit:integrity` verifies.
     *
     * Both install paths reach this through finalize(): the wizard's
     * InstallCompleteController and `dls:install`.
     *
     * Writes nothing when an entry already exists, so re-running the
     * finalizer against an installed site does not claim a second
     * install. Never throws: bookkeeping must not fail an install that
     * has otherwise completed.
     *
     * `public` (not `private`) for the same reason as the two methods
     * above: a test can exercise it without invoking finalize(), which
     * mutates `.env`.
     *
     * Returns the row, or null when one already existed or the write
     * failed.
     */
    public function recordInstallAudit(?CoreVersionHistory $baseline = null): ?AuditLog
    {
        try {
            if (AuditLog::query()->where('action', AuditLog::ACTION_CORE_INSTALLED)->exists()) {
                return null;
            }

            // The install's own ledger row names both the version and the
            // member who became the first administrator. InstallRunner
            // writes it (seedCoreReleaseState), which is why
            // recordBaselineVersionHistory() usually has nothing to do and
            // returns null -- so fall back to looking it up.
            $row = $baseline ?? CoreVersionHistory::query()
                ->where('installation_method', CoreVersionHistory::METHOD_INSTALL)
                ->orderBy('id')
                ->first();

            // The nullsafe stays: first() returns null when no ledger
            // row exists, whatever the static analysis infers from the
            // builder chain. It is written as a statement rather than
            // with ?? so the analyser's rule about a nullsafe on the
            // left of ?? does not fire on a guard that is real.
            $version = $row?->new_version;

            if ($version === null) {
                $version = CoreUpdater::readVersionFromDisk();
            }
            $actorId = $row?->applied_by_id;
            $actor = $actorId !== null ? Member::find($actorId) : null;

            return app(AuditService::class)->log([
                'action' => AuditLog::ACTION_CORE_INSTALLED,
                'category' => AuditLog::CATEGORY_SYSTEM,
                'severity' => AuditLog::SEVERITY_NOTICE,
                'outcome' => AuditLog::OUTCOME_SUCCESS,
                'actor' => $actor,
                'target_label' => $version !== null ? "Dixlase core v{$version}" : 'Dixlase core',
                'context' => [
                    'version' => $version,
                    'history_id' => $row?->id,
                    'installation_method' => CoreVersionHistory::METHOD_INSTALL,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::channel('install')->warning('Failed to record the install in the audit log (non-fatal): '.$e->getMessage());

            return null;
        }
    }

    /**
     * Discard the installer's log once this request or command is over.
     *
     * The install is complete, so the log has no audience left. It is
     * dropped after the response rather than here because the callers of
     * this method keep writing to the `install` channel afterwards — the
     * controller logs the finalize result and the redirect, and
     * `dls:install` logs its own completion. Removing the file first
     * would only have Monolog recreate it, with the tail of the install
     * in it.
     *
     * `terminating` runs for an HTTP request and for an Artisan command
     * alike, which is what makes one call here cover both install paths.
     * Failure is silent on purpose: an install must not be reported as
     * broken because a leftover log could not be removed.
     */
    private function scheduleInstallLogDiscard(): void
    {
        app()->terminating(function (): void {
            try {
                InstallLogFile::discard();
            } catch (\Throwable) {
                // A leftover log is not worth a failed install.
            }
        });
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
