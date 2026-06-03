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
 * Shared scope handling and plugin resolution for the dls:signature:* commands.
 *
 * Abstract, so Laravel's command discovery (which skips non-instantiable
 * classes) does not register it as a runnable command.
 */
abstract class AbstractSignatureCommand extends Command
{
    /**
     * Scopes a mutating command may act on in Phase 1 (plugin only).
     * Theme is Phase 2, core is Phase 3.
     */
    protected const PHASE1_SCOPES = [SignatureWaiver::SCOPE_PLUGIN];

    /**
     * Resolve and validate the --scope option for a mutating command.
     *
     * @return string|int the scope string, or an exit code the caller should
     *                    return immediately (FAILURE = unknown scope,
     *                    SUCCESS = valid scope not yet supported in Phase 1)
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

        if (! in_array($scope, self::PHASE1_SCOPES, true)) {
            $this->warn(sprintf(
                'Phase 1 only supports --scope=plugin. "%s" support is reserved for Phase 2/3 '
                .'(see .claude/plans/handoff-signature-waiver-implementation.md).',
                $scope
            ));

            return self::SUCCESS;
        }

        return $scope;
    }

    /**
     * The studly directory name used on disk (matches CoreSignatureVerifier),
     * accepting either a slug (dixlase-inquiry) or a directory (DixlaseInquiry).
     */
    protected function directoryName(string $target): string
    {
        return Str::studly(str_replace('-', '_', $target));
    }

    /**
     * Does the plugin exist on disk (plugin.json present)?
     */
    protected function pluginExists(string $target): bool
    {
        return File::exists(base_path('plugins/'.$this->directoryName($target).'/plugin.json'));
    }
}
