<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @internal Core use only. Do not reference from plugins/themes.
 *           (No @api in Phase 1 — add it, and register in PLUGIN-API.md,
 *           only when a plugin/theme actually needs to read waiver state.)
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

namespace App\Services\Signature;

use App\Models\SignatureWaiver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Cross-scope manager for signature waivers (plugin | theme | core).
 *
 * A waiver is an OVERLAY on the objective signature verification result: it
 * records that an operator deliberately accepted an extension whose signature
 * does not verify, suppressing the warning while leaving the verifier status
 * untouched. Read methods are safe to call anywhere; the mutating methods
 * (waive/unwaive) represent an explicit operator action.
 */
class SignatureWaiverService
{
    /**
     * Is there an active waiver for this scope + target?
     */
    public function isWaived(string $scope, string $targetSlug): bool
    {
        $this->assertScope($scope);
        $targetSlug = $this->canonicalTarget($scope, $targetSlug);

        if (! $this->tableReady()) {
            return false;
        }

        return SignatureWaiver::query()
            ->active()
            ->forTarget($scope, $targetSlug)
            ->exists();
    }

    /**
     * The active waiver for this scope + target, or null.
     */
    public function getActiveWaiver(string $scope, string $targetSlug): ?SignatureWaiver
    {
        $this->assertScope($scope);
        $targetSlug = $this->canonicalTarget($scope, $targetSlug);

        if (! $this->tableReady()) {
            return null;
        }

        return SignatureWaiver::query()
            ->active()
            ->forTarget($scope, $targetSlug)
            ->first();
    }

    /**
     * Record a new active waiver.
     *
     * @throws InvalidArgumentException on an unknown scope or empty reason
     * @throws RuntimeException if an active waiver already exists
     */
    public function waive(
        string $scope,
        string $targetSlug,
        string $reason,
        ?int $waivedByMemberId = null,
        ?string $waivedByLabel = null,
    ): SignatureWaiver {
        $this->assertScope($scope);
        $targetSlug = $this->canonicalTarget($scope, $targetSlug);

        if (trim($reason) === '') {
            throw new InvalidArgumentException('A waiver reason is required.');
        }

        if ($this->isWaived($scope, $targetSlug)) {
            throw new RuntimeException(sprintf(
                'An active waiver already exists for %s "%s". Revoke it with unwaive() first.',
                $scope,
                $targetSlug
            ));
        }

        return SignatureWaiver::create([
            'scope' => $scope,
            'target_slug' => $targetSlug,
            'waived_at' => now(),
            'waived_by' => $waivedByMemberId,
            'waived_by_label' => $waivedByLabel,
            'reason' => $reason,
            'active' => true,
        ]);
    }

    /**
     * Revoke the active waiver for this scope + target.
     *
     * Sets the NULL-safe sentinel `active = null` together with the audit
     * timestamp `revoked_at`, so the historical row is preserved but no longer
     * collides with a future waiver.
     *
     * @return bool true if an active waiver was revoked, false if there was none
     */
    public function unwaive(
        string $scope,
        string $targetSlug,
        ?int $revokedByMemberId = null,
        ?string $revokedReason = null,
    ): bool {
        $this->assertScope($scope);

        $waiver = $this->getActiveWaiver($scope, $targetSlug);

        if ($waiver === null) {
            return false;
        }

        $waiver->update([
            'active' => null,
            'revoked_at' => now(),
            'revoked_by' => $revokedByMemberId,
            'revoked_reason' => $revokedReason,
        ]);

        return true;
    }

    /**
     * All active waivers, optionally filtered by scope.
     *
     * @return Collection<int, SignatureWaiver>
     */
    public function listActiveWaivers(?string $scope = null): Collection
    {
        if (! $this->tableReady()) {
            return new Collection();
        }

        $query = SignatureWaiver::query()->active();

        if ($scope !== null) {
            $this->assertScope($scope);
            $query->where('scope', $scope);
        }

        return $query->orderBy('scope')->orderBy('target_slug')->get();
    }

    /**
     * Normalize the target identifier to the canonical form the verifier uses,
     * so a waiver matches regardless of whether the caller passed a slug
     * (`dixlase-inquiry`) or a directory name (`DixlaseInquiry`).
     *
     * - plugin/theme: the studly directory name (matches CoreSignatureVerifier)
     * - core: always the literal 'core'
     */
    protected function canonicalTarget(string $scope, string $targetSlug): string
    {
        if ($scope === SignatureWaiver::SCOPE_CORE) {
            return 'core';
        }

        return Str::studly(str_replace('-', '_', $targetSlug));
    }

    /**
     * Whether the waivers table exists yet. Lets the read methods stay safe on
     * a deploy where the code has shipped but the migration has not run yet
     * (e.g. production before `php artisan migrate`), instead of 500-ing every
     * admin page that calls getSignatureInfo().
     */
    protected function tableReady(): bool
    {
        return Schema::hasTable((new SignatureWaiver())->getTable());
    }

    /**
     * @throws InvalidArgumentException on an unknown scope
     */
    protected function assertScope(string $scope): void
    {
        if (! in_array($scope, SignatureWaiver::SCOPES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown waiver scope "%s". Expected one of: %s.',
                $scope,
                implode(', ', SignatureWaiver::SCOPES)
            ));
        }
    }
}
