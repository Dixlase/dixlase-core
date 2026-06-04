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

namespace App\Console\Commands\Signature;

use App\Models\SignatureWaiver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Shared scope handling and target resolution for the dls:signature:* commands.
 *
 * Abstract, so Laravel's command discovery (which skips non-instantiable
 * classes) does not register it as a runnable command. Supports plugin, theme,
 * and core scopes; mutating the core signature is gated (see coreGateOpen()).
 */
abstract class AbstractSignatureCommand extends Command
{
    /**
     * Validate the --scope option.
     *
     * @return string|int the scope string, or an exit code to return (FAILURE on unknown scope)
     */
    protected function resolveScope(): string|int
    {
        $scope = (string) $this->option('scope');

        if (! in_array($scope, SignatureWaiver::SCOPES, true)) {
            $this->error(sprintf(
                'Unknown --scope "%s". Expected one of: %s.',
                $scope,
                implode(', ', SignatureWaiver::SCOPES)
            ));

            return self::FAILURE;
        }

        return $scope;
    }

    /**
     * Canonical target identifier: 'core' for the core scope, otherwise the
     * studly directory name (matches CoreSignatureVerifier / the waiver service).
     */
    protected function canonicalTarget(string $scope, string $target): string
    {
        if ($scope === SignatureWaiver::SCOPE_CORE) {
            return 'core';
        }

        return Str::studly(str_replace('-', '_', $target));
    }

    /**
     * Does the target exist on disk? (core always "exists".)
     */
    protected function targetExists(string $scope, string $target): bool
    {
        return match ($scope) {
            SignatureWaiver::SCOPE_PLUGIN => File::exists(base_path('plugins/'.$this->canonicalTarget($scope, $target).'/plugin.json')),
            SignatureWaiver::SCOPE_THEME => File::exists(base_path('themes/'.$this->canonicalTarget($scope, $target).'/theme.json')),
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
    protected function signatureFilesFor(string $scope, string $target): array
    {
        return match ($scope) {
            SignatureWaiver::SCOPE_PLUGIN => [base_path('plugins/'.$this->canonicalTarget($scope, $target).'/signature.sig')],
            SignatureWaiver::SCOPE_THEME => [base_path('themes/'.$this->canonicalTarget($scope, $target).'/signature.sig')],
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
     * it is gated to dev/customized installs.
     */
    protected function coreGateOpen(): bool
    {
        return (bool) config('core-integrity.allow_unsign', false) || (bool) config('app.debug', false);
    }

    /**
     * Enforce the core mutation gate. Returns true if allowed; otherwise prints
     * the reason and returns false (the caller should return FAILURE).
     */
    protected function ensureCoreMutationAllowed(string $scope): bool
    {
        if ($scope !== SignatureWaiver::SCOPE_CORE) {
            return true;
        }

        if ($this->coreGateOpen()) {
            return true;
        }

        $this->error('Mutating the core signature is disabled on this install.');
        $this->line('Set DLS_CORE_ALLOW_UNSIGN=true (or APP_DEBUG=true) to allow it on a development / customized install.');

        return false;
    }
}
