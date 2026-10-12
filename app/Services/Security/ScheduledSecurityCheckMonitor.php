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

namespace App\Services\Security;

use App\DTO\Core\CoreIntegrityResult;
use App\Enums\PluginHealthStatus;
use App\Helpers\AdminHelper;
use App\Services\SystemNotificationService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Turns the results of the scheduled security checks into admin-wide
 * banners (through SystemWarningService) and notification mail.
 *
 * Three checks feed it:
 *  - the core signed-manifest check (`dls:core:verify`)
 *  - the audit log hash chain / daily seal verification (`audit:integrity verify`)
 *  - the daily rescan of enabled plugins and themes (`dls:extensions:rescan`):
 *    a worse health status or a signature that stopped verifying
 *
 * A failing result raises an alert; the next passing result clears it, so the
 * banner disappears once the condition is resolved. Notification mail is sent
 * only when an alert is first raised, not on every run while it persists.
 *
 * State lives in a small JSON file under storage (not the cache), so routine
 * `cache:clear` / `optimize:clear` cannot silently hide an alert.
 *
 * @internal Core only. Do not reference from plugins/themes
 */
class ScheduledSecurityCheckMonitor
{
    public const KEY_CORE_MANIFEST = 'core_manifest';

    public const KEY_AUDIT_CHAIN = 'audit_chain';

    public const EXTENSION_KEY_PREFIX = 'extension:';

    public const REASON_HEALTH = 'health';

    public const REASON_SIGNATURE = 'signature';

    /**
     * Admin menu key that gates who sees the banners.
     */
    public const MENU_KEY = 'settings.security.integrity';

    /**
     * Core manifest statuses that count as a failed check. MODIFIED is a
     * supported local customization and UNSIGNED / PENDING are not failures.
     */
    protected const CORE_FAILURE_STATUSES = [
        CoreIntegrityResult::STATUS_INVALID,
        CoreIntegrityResult::STATUS_ERROR,
    ];

    /**
     * Signature statuses that count as "verifying". A pending status means the
     * trusted key could not be fetched yet (offline), not that the check failed.
     */
    protected const SIGNATURE_OK_STATUSES = ['valid', 'pending_verification'];

    /** @var array<string, mixed>|null per-instance read cache */
    protected ?array $cachedState = null;

    public function __construct(
        protected SystemNotificationService $notifications,
    ) {}

    // ------------------------------------------------------------------
    // Recording results
    // ------------------------------------------------------------------

    /**
     * Record the outcome of a core signed-manifest check.
     */
    public function recordCoreManifest(CoreIntegrityResult $result): void
    {
        $failed = in_array($result->status, self::CORE_FAILURE_STATUSES, true);

        $raised = false;
        $this->mutate(function (array $state) use ($result, $failed, &$raised): array {
            $state['core_manifest'] = [
                'checked_at' => now()->toIso8601String(),
                'status' => $result->status,
                'version' => $result->version,
                'key_id' => $result->keyId,
                'signed_at' => $result->signedAt,
                'changed_count' => $result->changedCount(),
                'message' => $result->message,
                'waived' => $result->waived,
            ];

            if ($failed) {
                $raised = ! isset($state['alerts'][self::KEY_CORE_MANIFEST]);
                $state['alerts'][self::KEY_CORE_MANIFEST] = [
                    'raised_at' => $state['alerts'][self::KEY_CORE_MANIFEST]['raised_at'] ?? now()->toIso8601String(),
                    'status' => $result->status,
                ];
            } else {
                unset($state['alerts'][self::KEY_CORE_MANIFEST]);
            }

            return $state;
        });

        if ($raised) {
            $this->notify(
                __('admin/settings/security/integrity.scheduled.core_manifest_title'),
                __('admin/settings/security/integrity.scheduled.core_manifest_message', [
                    'status' => $this->coreStatusLabel($result->status),
                ]),
            );
        }
    }

    /**
     * Record the outcome of an audit log integrity verification.
     */
    public function recordAuditVerification(bool $valid, int $tampered = 0, int $invalidSeals = 0): void
    {
        $raised = false;
        $this->mutate(function (array $state) use ($valid, $tampered, $invalidSeals, &$raised): array {
            if ($valid) {
                unset($state['alerts'][self::KEY_AUDIT_CHAIN]);

                return $state;
            }

            $raised = ! isset($state['alerts'][self::KEY_AUDIT_CHAIN]);
            $state['alerts'][self::KEY_AUDIT_CHAIN] = [
                'raised_at' => $state['alerts'][self::KEY_AUDIT_CHAIN]['raised_at'] ?? now()->toIso8601String(),
                'tampered' => $tampered,
                'invalid_seals' => $invalidSeals,
            ];

            return $state;
        });

        if ($raised) {
            $this->notify(
                __('admin/settings/security/integrity.scheduled.audit_chain_title'),
                __('admin/settings/security/integrity.scheduled.audit_chain_message', [
                    'tampered' => $tampered,
                    'seals' => $invalidSeals,
                ]),
            );
        }
    }

    /**
     * Record the scheduled rescan of one extension.
     *
     * The comparison point is the status this monitor saw at the previous
     * scheduled run (or, while an alert is open, the status from before the
     * alert). A manual rescan in between therefore cannot hide a worsening.
     *
     * @param  string  $type  'plugin' or 'theme'
     * @param  array{health_status?: string|null, signature_status?: string|null}|null  $before  audit row before this rescan (first-run fallback)
     * @param  array{health_status?: string|null, signature_status?: string|null}|null  $after  audit row after this rescan
     */
    public function recordExtensionRescan(string $type, string $slug, string $name, ?array $before, ?array $after): void
    {
        if ($after === null) {
            // The rescan produced nothing to compare; keep the current state.
            return;
        }

        $key = self::extensionKey($type, $slug);
        $current = [
            'health_status' => $after['health_status'] ?? null,
            'signature_status' => $after['signature_status'] ?? null,
        ];

        $raisedReasons = null;
        $this->mutate(function (array $state) use ($key, $type, $slug, $name, $before, $current, &$raisedReasons): array {
            $existing = $state['alerts'][$key] ?? null;
            $baseline = $existing['baseline'] ?? $state['snapshots'][$key] ?? ($before !== null ? [
                'health_status' => $before['health_status'] ?? null,
                'signature_status' => $before['signature_status'] ?? null,
            ] : null);

            $state['snapshots'][$key] = $current;

            $reasons = $baseline !== null ? $this->worseningReasons($baseline, $current) : [];

            if ($reasons === []) {
                unset($state['alerts'][$key]);

                return $state;
            }

            if ($existing === null) {
                $raisedReasons = $reasons;
            }

            $state['alerts'][$key] = [
                'raised_at' => $existing['raised_at'] ?? now()->toIso8601String(),
                'type' => $type,
                'slug' => $slug,
                'name' => $name,
                'baseline' => $baseline,
                'current' => $current,
                'reasons' => $reasons,
            ];

            return $state;
        });

        if ($raisedReasons !== null) {
            $this->notify(
                __('admin/settings/security/integrity.scheduled.extensions_title'),
                __('admin/settings/security/integrity.scheduled.extension_mail_message', [
                    'type' => __('admin/settings/security/integrity.scheduled.type_'.$type),
                    'name' => $name,
                    'reasons' => $this->reasonLabels($raisedReasons),
                ]),
            );
        }
    }

    /**
     * Drop extension alerts and snapshots for extensions that were not part
     * of this scheduled rescan (uninstalled or disabled since).
     *
     * @param  array<int, string>  $scannedKeys  keys built by extensionKey()
     */
    public function pruneExtensions(array $scannedKeys): void
    {
        $this->mutate(function (array $state) use ($scannedKeys): array {
            foreach (['alerts', 'snapshots'] as $bucket) {
                foreach (array_keys($state[$bucket] ?? []) as $key) {
                    if (str_starts_with((string) $key, self::EXTENSION_KEY_PREFIX) && ! in_array($key, $scannedKeys, true)) {
                        unset($state[$bucket][$key]);
                    }
                }
            }

            return $state;
        });
    }

    /**
     * Operator acknowledgement: accept the current status of every flagged
     * extension as the new baseline and clear their alerts.
     */
    public function acknowledgeExtensions(): void
    {
        $this->mutate(function (array $state): array {
            foreach (array_keys($state['alerts'] ?? []) as $key) {
                if (str_starts_with((string) $key, self::EXTENSION_KEY_PREFIX)) {
                    unset($state['alerts'][$key]);
                }
            }

            return $state;
        });
    }

    public static function extensionKey(string $type, string $slug): string
    {
        return self::EXTENSION_KEY_PREFIX.$type.':'.$slug;
    }

    // ------------------------------------------------------------------
    // Reading
    // ------------------------------------------------------------------

    /**
     * Active alerts, keyed by alert key.
     *
     * @return array<string, array<string, mixed>>
     */
    public function alerts(): array
    {
        $alerts = $this->state()['alerts'] ?? [];

        return is_array($alerts) ? $alerts : [];
    }

    /**
     * Active extension alerts only.
     *
     * @return array<string, array<string, mixed>>
     */
    public function extensionAlerts(): array
    {
        return array_filter(
            $this->alerts(),
            static fn ($alert, $key): bool => str_starts_with((string) $key, self::EXTENSION_KEY_PREFIX),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * The most recent core signed-manifest check, or null if it never ran.
     *
     * @return array<string, mixed>|null
     */
    public function latestCoreManifestCheck(): ?array
    {
        $check = $this->state()['core_manifest'] ?? null;

        return is_array($check) ? $check : null;
    }

    /**
     * Banner provider for SystemWarningService.
     *
     * Only members who can open Security → Integrity see these banners.
     *
     * @return array<int, array<string, mixed>>
     */
    public function banners(): array
    {
        try {
            if (! AdminHelper::canAccessMenu(self::MENU_KEY)) {
                return [];
            }

            $alerts = $this->alerts();
            if ($alerts === []) {
                return [];
            }

            return $this->buildBanners($alerts, AdminHelper::canEditMenu(self::MENU_KEY));
        } catch (Throwable $e) {
            // A banner must never break the admin layout.
            Log::warning('ScheduledSecurityCheckMonitor: banner build failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $alerts
     * @return array<int, array<string, mixed>>
     */
    public function buildBanners(array $alerts, bool $canAcknowledge): array
    {
        $prefix = 'admin/settings/security/integrity.scheduled.';
        $detailsAction = [
            'label' => __($prefix.'view_details'),
            'url' => route('admin.settings.security.integrity'),
            'style' => 'primary',
            'method' => 'GET',
        ];

        $banners = [];

        if (isset($alerts[self::KEY_CORE_MANIFEST])) {
            $banners[] = [
                'level' => 'error',
                'icon' => 'fas fa-shield-alt',
                'title' => __($prefix.'core_manifest_title'),
                'message' => __($prefix.'core_manifest_message', [
                    'status' => $this->coreStatusLabel((string) ($alerts[self::KEY_CORE_MANIFEST]['status'] ?? '')),
                ]),
                'actions' => [$detailsAction],
            ];
        }

        if (isset($alerts[self::KEY_AUDIT_CHAIN])) {
            $banners[] = [
                'level' => 'error',
                'icon' => 'fas fa-link',
                'title' => __($prefix.'audit_chain_title'),
                'message' => __($prefix.'audit_chain_message', [
                    'tampered' => (int) ($alerts[self::KEY_AUDIT_CHAIN]['tampered'] ?? 0),
                    'seals' => (int) ($alerts[self::KEY_AUDIT_CHAIN]['invalid_seals'] ?? 0),
                ]),
                'actions' => [$detailsAction],
            ];
        }

        $extensionAlerts = array_filter(
            $alerts,
            static fn ($alert, $key): bool => str_starts_with((string) $key, self::EXTENSION_KEY_PREFIX),
            ARRAY_FILTER_USE_BOTH,
        );

        if ($extensionAlerts !== []) {
            $signatureFailed = false;
            $names = [];
            foreach ($extensionAlerts as $alert) {
                $names[] = (string) ($alert['name'] ?? $alert['slug'] ?? '');
                if (in_array(self::REASON_SIGNATURE, (array) ($alert['reasons'] ?? []), true)) {
                    $signatureFailed = true;
                }
            }

            $actions = [$detailsAction];
            if ($canAcknowledge) {
                $actions[] = [
                    'label' => __($prefix.'acknowledge'),
                    'url' => route('admin.settings.security.integrity.acknowledge-extensions'),
                    'style' => 'secondary',
                    'method' => 'POST',
                ];
            }

            $banners[] = [
                'level' => $signatureFailed ? 'error' : 'warning',
                'icon' => 'fas fa-puzzle-piece',
                'title' => __($prefix.'extensions_title'),
                'message' => __($prefix.'extensions_message', ['names' => implode(', ', $names)]),
                'actions' => $actions,
            ];
        }

        return $banners;
    }

    // ------------------------------------------------------------------
    // Labels
    // ------------------------------------------------------------------

    public function coreStatusLabel(string $status): string
    {
        $key = 'admin/settings/security/integrity.scheduled.core_status.'.$status;
        $label = __($key);

        return $label === $key ? $status : $label;
    }

    /**
     * @param  array<int, string>  $reasons
     */
    public function reasonLabels(array $reasons): string
    {
        return implode(', ', array_map(
            static fn (string $reason): string => __('admin/settings/security/integrity.scheduled.reason_'.$reason),
            $reasons,
        ));
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    /**
     * Why the current status is worse than the baseline (empty when it is not).
     *
     * @param  array<string, mixed>  $baseline
     * @param  array<string, mixed>  $current
     * @return array<int, string>
     */
    protected function worseningReasons(array $baseline, array $current): array
    {
        $reasons = [];

        $before = $this->healthRank($baseline['health_status'] ?? null);
        $now = $this->healthRank($current['health_status'] ?? null);
        if ($before !== null && $now !== null && $now > $before) {
            $reasons[] = self::REASON_HEALTH;
        }

        $signatureBefore = $baseline['signature_status'] ?? null;
        $signatureNow = $current['signature_status'] ?? null;
        if ($signatureBefore === 'valid' && ! in_array($signatureNow, self::SIGNATURE_OK_STATUSES, true)) {
            $reasons[] = self::REASON_SIGNATURE;
        }

        return $reasons;
    }

    /**
     * Order health statuses from best (0) to worst. Unknown values are not
     * ranked, so they never count as a change.
     */
    protected function healthRank(mixed $status): ?int
    {
        return match ($status) {
            PluginHealthStatus::Healthy->value => 0,
            PluginHealthStatus::Advisory->value => 1,
            PluginHealthStatus::NeedsAttention->value, PluginHealthStatus::NotVerified->value => 2,
            default => null,
        };
    }

    protected function notify(string $subject, string $message): void
    {
        try {
            $this->notifications->sendAdminNotification($subject, $message);
        } catch (Throwable $e) {
            Log::warning('ScheduledSecurityCheckMonitor: notification failed', ['error' => $e->getMessage()]);
        }
    }

    protected function statePath(): string
    {
        $path = (string) config('security.scheduled_checks.state_file', 'app/private/security/scheduled-checks.json');

        return str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : storage_path($path);
    }

    /**
     * @return array<string, mixed>
     */
    protected function state(): array
    {
        if ($this->cachedState !== null) {
            return $this->cachedState;
        }

        $path = $this->statePath();
        if (! is_file($path)) {
            return $this->cachedState = [];
        }

        $decoded = json_decode((string) @file_get_contents($path), true);

        return $this->cachedState = is_array($decoded) ? $decoded : [];
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
            Log::error('ScheduledSecurityCheckMonitor: cannot open state file', ['path' => $path]);

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

            $this->cachedState = $state;
        } finally {
            fclose($handle);
        }
    }
}
