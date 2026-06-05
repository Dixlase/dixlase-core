<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @internal Core use only. The mutating CLI lives in first-party DixlaseDevKit
 *           and reaches this via App\Contracts\Signature\SignatureWaiverServiceInterface.
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

use App\Contracts\Signature\SignatureWaiverServiceInterface;
use App\Models\AuditLog;
use App\Models\SignatureWaiver;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Cross-scope manager for signature waivers + removal (plugin | theme | core).
 *
 * A waiver is an OVERLAY on the objective signature verification result: it
 * records that an operator deliberately accepted an extension whose signature
 * does not verify, suppressing the warning while leaving the verifier status
 * untouched. Removal deletes the signature file(s) entirely.
 *
 * All three mutating operations (waive / unwaive / removeSignature) emit a
 * tamper-evident security entry to the central audit log, so "who changed the
 * signature trust state, when, and why" is always recorded — regardless of
 * whether the call originated from the admin panel (real member) or a CLI
 * (system + OS user + --by). The read methods are safe to call anywhere.
 */
class SignatureWaiverService implements SignatureWaiverServiceInterface
{
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
     * Record a new active waiver (audited).
     *
     * @throws InvalidArgumentException on an unknown scope or empty reason
     * @throws RuntimeException if an active waiver already exists
     */
    public function waive(
        string $scope,
        string $targetSlug,
        string $reason,
        ?Authenticatable $actor = null,
        ?string $actorLabel = null,
        string $source = 'cli',
    ): SignatureWaiver {
        $this->assertScope($scope);
        $targetSlug = $this->canonicalTarget($scope, $targetSlug);

        if (! $this->tableReady()) {
            throw new RuntimeException('The signature_waivers table is missing; run migrations before recording a waiver.');
        }

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

        $label = $actorLabel ?? $this->nameOf($actor);

        $waiver = SignatureWaiver::create([
            'scope' => $scope,
            'target_slug' => $targetSlug,
            'waived_at' => now(),
            'waived_by' => $actor?->getAuthIdentifier(),
            'waived_by_label' => $label,
            'reason' => $reason,
            'active' => true,
        ]);

        $this->audit(AuditLog::ACTION_SIGNATURE_WAIVED, $scope, $targetSlug, $actor, $label, $source, [
            'reason' => $reason,
            'waiver_id' => $waiver->id,
        ]);

        return $waiver;
    }

    /**
     * Revoke the active waiver for this scope + target (audited).
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
        ?Authenticatable $actor = null,
        ?string $revokedReason = null,
        ?string $actorLabel = null,
        string $source = 'cli',
    ): bool {
        $this->assertScope($scope);
        $canonical = $this->canonicalTarget($scope, $targetSlug);

        $waiver = $this->getActiveWaiver($scope, $targetSlug);

        if ($waiver === null) {
            return false;
        }

        $waiver->update([
            'active' => null,
            'revoked_at' => now(),
            'revoked_by' => $actor?->getAuthIdentifier(),
            'revoked_reason' => $revokedReason,
        ]);

        $this->audit(AuditLog::ACTION_SIGNATURE_WAIVER_REVOKED, $scope, $canonical, $actor, $actorLabel ?? $this->nameOf($actor), $source, [
            'waiver_id' => $waiver->id,
        ]);

        return true;
    }

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
    ): array {
        $this->assertScope($scope);
        $canonical = $this->canonicalTarget($scope, $targetSlug);

        $deleted = [];
        foreach ($this->signatureFilesFor($scope, $targetSlug) as $file) {
            if (File::exists($file)) {
                File::delete($file);
                $deleted[] = $file;
            }
        }

        if (! empty($deleted)) {
            $this->audit(AuditLog::ACTION_SIGNATURE_REMOVED, $scope, $canonical, $actor, $actorLabel ?? $this->nameOf($actor), $source, [
                'files' => array_map(fn (string $f): string => str_replace(base_path().'/', '', $f), $deleted),
            ]);
        }

        return $deleted;
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
     * Canonical target id: 'core' for the core scope, else the studly directory
     * name (matches CoreSignatureVerifier), accepting a slug or a directory name.
     */
    public function canonicalTarget(string $scope, string $targetSlug): string
    {
        if ($scope === SignatureWaiver::SCOPE_CORE) {
            return 'core';
        }

        return Str::studly(str_replace('-', '_', $targetSlug));
    }

    /**
     * Does the target exist on disk? (core always "exists".)
     */
    public function targetExists(string $scope, string $targetSlug): bool
    {
        $canonical = $this->canonicalTarget($scope, $targetSlug);

        return match ($scope) {
            SignatureWaiver::SCOPE_PLUGIN => File::exists(base_path('plugins/'.$canonical.'/plugin.json')),
            SignatureWaiver::SCOPE_THEME => File::exists(base_path('themes/'.$canonical.'/theme.json')),
            SignatureWaiver::SCOPE_CORE => true,
            default => false,
        };
    }

    /**
     * Absolute path(s) whose deletion removes the signature for this target.
     * For core, both the manifest and the detached signature are removed.
     *
     * @return array<int, string>
     */
    public function signatureFilesFor(string $scope, string $targetSlug): array
    {
        $canonical = $this->canonicalTarget($scope, $targetSlug);

        return match ($scope) {
            SignatureWaiver::SCOPE_PLUGIN => [base_path('plugins/'.$canonical.'/signature.sig')],
            SignatureWaiver::SCOPE_THEME => [base_path('themes/'.$canonical.'/signature.sig')],
            SignatureWaiver::SCOPE_CORE => [
                base_path((string) config('core-integrity.signature_file', 'core-signature.sig')),
                base_path((string) config('core-integrity.manifest_file', 'core-manifest.json')),
            ],
            default => [],
        };
    }

    /**
     * Whether mutating the CORE signature (waive/remove) is permitted. Removing
     * or waiving the root-of-trust signature is the genuinely dangerous op, so
     * it is gated to dev / customized installs.
     */
    public function coreMutationGateOpen(): bool
    {
        return (bool) config('core-integrity.allow_unsign', false) || (bool) config('app.debug', false);
    }

    /**
     * Emit a tamper-evident security audit entry. Wrapped so a logging failure
     * never aborts the primary operation.
     *
     * @param  array<string, mixed>  $context
     */
    protected function audit(string $action, string $scope, string $canonicalTarget, ?Authenticatable $actor, ?string $actorName, string $source, array $context = []): void
    {
        try {
            AuditLog::logSecurity($action, [
                'actor' => $actor,
                'actor_name' => $actorName,
                'actor_source' => $source,
                'target_label' => $scope.':'.$canonicalTarget,
                'context' => array_merge(['scope' => $scope, 'target' => $canonicalTarget], $context),
            ]);
        } catch (\Throwable $e) {
            Log::warning('SignatureWaiverService: audit log failed', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Best-effort display name for an actor model (Member etc.), or null.
     */
    protected function nameOf(?Authenticatable $actor): ?string
    {
        if ($actor === null) {
            return null;
        }

        return data_get($actor, 'display_name')
            ?? data_get($actor, 'name')
            ?? data_get($actor, 'account_name');
    }

    /**
     * Whether the waivers table exists yet. Lets the read methods stay safe on
     * a deploy where the code has shipped but the migration has not run yet.
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
