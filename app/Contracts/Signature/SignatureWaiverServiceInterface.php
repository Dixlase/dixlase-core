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

namespace App\Contracts\Signature;

use App\Models\SignatureWaiver;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Signature waiver / removal manager (plugin | theme | core).
 *
 * The contract first-party developer tooling (e.g. DixlaseDevKit's
 * dls:signature:* commands) uses to manage signature trust state, so those
 * commands can live outside Core while the data, the read-side overlay, and
 * the audit-log emission stay in Core.
 *
 * NOTE: intentionally NOT tagged @api yet — the only external consumer is the
 * first-party DixlaseDevKit (not subject to the AGPL exception). Add @api and
 * register in PLUGIN-API.md if a third-party plugin/theme ever needs it.
 */
interface SignatureWaiverServiceInterface
{
    // Scope constants re-exported from the model so consumers (e.g. DixlaseDevKit
    // commands) can reference them via this contract without touching core models.
    public const SCOPE_PLUGIN = SignatureWaiver::SCOPE_PLUGIN;

    public const SCOPE_THEME = SignatureWaiver::SCOPE_THEME;

    public const SCOPE_CORE = SignatureWaiver::SCOPE_CORE;

    public const SCOPES = SignatureWaiver::SCOPES;

    /** Is there an active waiver for this scope + target? */
    public function isWaived(string $scope, string $targetSlug): bool;

    /** The active waiver for this scope + target, or null. */
    public function getActiveWaiver(string $scope, string $targetSlug): ?SignatureWaiver;

    /**
     * Record a new active waiver (audited).
     *
     * @param  string  $source  'admin' | 'cli' (origin of the action, for the audit trail)
     */
    public function waive(
        string $scope,
        string $targetSlug,
        string $reason,
        ?Authenticatable $actor = null,
        ?string $actorLabel = null,
        string $source = 'cli',
    ): SignatureWaiver;

    /**
     * Revoke the active waiver for this scope + target (audited).
     *
     * @return bool true if an active waiver was revoked, false if there was none
     */
    public function unwaive(
        string $scope,
        string $targetSlug,
        ?Authenticatable $actor = null,
        ?string $revokedReason = null,
        ?string $actorLabel = null,
        string $source = 'cli',
    ): bool;

    /**
     * Delete the signature file(s) for this target (audited). Destructive.
     *
     * @return array<int, string> the absolute paths that were deleted
     */
    public function removeSignature(
        string $scope,
        string $targetSlug,
        ?Authenticatable $actor = null,
        ?string $actorLabel = null,
        string $source = 'cli',
    ): array;

    /** All active waivers, optionally filtered by scope. */
    public function listActiveWaivers(?string $scope = null): Collection;

    /** Canonical target id: 'core' for core, else the studly directory name. */
    public function canonicalTarget(string $scope, string $targetSlug): string;

    /** Does the target exist on disk? (core always "exists".) */
    public function targetExists(string $scope, string $targetSlug): bool;

    /**
     * Absolute path(s) whose deletion removes the signature for this target.
     *
     * @return array<int, string>
     */
    public function signatureFilesFor(string $scope, string $targetSlug): array;

    /** Whether mutating the CORE signature (waive/remove) is permitted on this install. */
    public function coreMutationGateOpen(): bool;
}
