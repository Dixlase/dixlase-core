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

namespace App\Services\Privacy;

use App\Contracts\PluginIntegration\PrivacyDataProviderInterface;
use App\DTO\PluginPrivacy\UserDataDeletionDTO;
use App\Enums\PluginPrivacy\DeletionMode;
use App\Facades\Audit;
use App\Models\AuditLog;
use App\Models\Member;
use App\Services\Plugin\PluginPermissionService;
use App\Services\Plugin\PluginServiceResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Aggregates every PrivacyDataProviderInterface implementation registered
 * in the container and dispatches deleteUserData() to each one.
 *
 * Per-provider failures (capability unavailable, plugin permission
 * denied, exception during deletion) do NOT abort the run. The eraser
 * collects a UserDataDeletionDTO per attempted provider, including
 * synthesised DTOs for the failure cases so the operator can see which
 * providers did not complete and why. This matches the design decision
 * of "continue on failure, aggregate errors[]".
 *
 * The eraser does not coordinate foreign-key ordering across providers:
 * each provider is expected to honour its own internal FK constraints
 * (typically by wrapping its deleteUserData implementation in a
 * transaction).
 *
 * The run is recorded as one `privacy_data_erased` audit row. The
 * providers' writes on the default connection commit together with that
 * row: if it cannot be written, the whole run is rolled back and nothing
 * is erased. Its `request_id` is the id the core provider stamps on
 * every audit row it redacts, so verification can tie each redacted row
 * to this run (#478).
 * The row names the subject by id only, never by name or address.
 *
 * Future enhancement (not implemented): a $preview flag on erase() that
 * reports the affected row counts without writing changes.
 */
class UserPrivacyEraser
{
    public function __construct(
        private readonly PluginServiceResolver $resolver,
        private readonly PluginPermissionService $permissions,
    ) {}

    /**
     * Delete or anonymize all user data across every registered provider.
     *
     * @param  int  $userId  Subject identifier passed to each provider.
     * @param  DeletionMode  $mode  Strategy chosen by the operator.
     * @param  int|null  $siteId  null = network-wide; int = data tied to that site only.
     * @param  Model|null  $actor  The operator running the erasure, recorded on its audit row.
     * @return array<UserDataDeletionDTO> One DTO per attempted provider,
     *                                    including synthesised entries for
     *                                    unresolved or permission-denied providers.
     *
     * @throws ErasureNotRecordedException When the run's audit row could not be written; nothing is erased.
     */
    public function erase(int $userId, DeletionMode $mode, ?int $siteId = null, ?Model $actor = null): array
    {
        $redactionId = (string) Str::uuid();

        app()->instance(AuditRedactionScope::class, new AuditRedactionScope($redactionId));

        try {
            return DB::transaction(function () use ($userId, $mode, $siteId, $actor, $redactionId) {
                $results = $this->runProviders($userId, $mode, $siteId);

                $this->recordErasure($userId, $mode, $siteId, $actor, $redactionId, $results);

                return $results;
            });
        } finally {
            app()->forgetInstance(AuditRedactionScope::class);
        }
    }

    /**
     * Dispatch deleteUserData() to every registered provider.
     *
     * @return array<UserDataDeletionDTO>
     */
    private function runProviders(int $userId, DeletionMode $mode, ?int $siteId): array
    {
        $results = [];

        foreach ($this->resolver->resolveAll(PrivacyDataProviderInterface::class) as $resolution) {
            if (! $resolution->isResolved()) {
                $results[] = new UserDataDeletionDTO(
                    providerKey: 'unresolved:'.($resolution->pluginSlug ?? 'unknown'),
                    mode: $mode,
                    errors: [
                        'failure_reason: '.($resolution->failureReason ?? 'unknown'),
                    ],
                );

                continue;
            }

            $provider = $resolution->instance;
            if (! $provider instanceof PrivacyDataProviderInterface) {
                continue;
            }

            $slug = $provider->getPluginSlug();
            $providerKey = $provider->privacyProviderKey();

            if (! $this->isDeletionAllowed($slug)) {
                $results[] = new UserDataDeletionDTO(
                    providerKey: $providerKey,
                    mode: $mode,
                    errors: ['permission_denied: privacy.delete'],
                );

                continue;
            }

            try {
                $results[] = $provider->deleteUserData($userId, $mode, $siteId);
            } catch (Throwable $e) {
                Log::error('Privacy provider failed to delete user data', [
                    'plugin' => $slug,
                    'provider_key' => $providerKey,
                    'user_id' => $userId,
                    'site_id' => $siteId,
                    'mode' => $mode->value,
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);
                $results[] = new UserDataDeletionDTO(
                    providerKey: $providerKey,
                    mode: $mode,
                    errors: ['exception: '.$e->getMessage()],
                );
            }
        }

        return $results;
    }

    /**
     * Write the run's own audit row, or throw so the run is rolled back.
     *
     * @param  array<UserDataDeletionDTO>  $results
     *
     * @throws ErasureNotRecordedException
     */
    private function recordErasure(int $userId, DeletionMode $mode, ?int $siteId, ?Model $actor, string $redactionId, array $results): void
    {
        $providers = [];
        foreach ($results as $result) {
            $providers[$result->providerKey] = [
                'deleted' => $result->deletedRecords,
                'anonymized' => $result->anonymizedRecords,
                'errors' => count($result->errors),
            ];
        }

        $row = Audit::logAccount(AuditLog::ACTION_PRIVACY_DATA_ERASED, [
            'request_id' => $redactionId,
            'severity' => AuditLog::SEVERITY_WARNING,
            'actor' => $actor,
            'target_type' => Member::class,
            'target_id' => $userId,
            'context' => [
                'mode' => $mode->value,
                'site_id' => $siteId,
                'providers' => $providers,
            ],
        ]);

        if ($row === null) {
            throw new ErasureNotRecordedException(
                "The erasure of member [{$userId}] could not be recorded in the audit log, so nothing was erased."
            );
        }
    }

    /**
     * Decide whether the given plugin slug is allowed to participate in
     * a deletion run. Core providers (slug 'core' or prefixed 'core-')
     * are always allowed because they do not have a plugin.json to
     * declare permissions. Plugin providers must declare 'privacy.delete'.
     */
    private function isDeletionAllowed(string $slug): bool
    {
        if ($slug === 'core' || str_starts_with($slug, 'core-')) {
            return true;
        }

        return $this->permissions->check($slug, 'privacy.delete');
    }
}
