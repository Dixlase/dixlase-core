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

use Illuminate\Support\Facades\File;

/**
 * Destructively remove an extension's signature by deleting its signature.sig.
 *
 * After removal the extension verifies as `unsigned`. For operators this is
 * reversible only by reinstalling the extension (no signer ships to
 * production); in a build/dev environment it can be re-signed.
 *
 * Prefer `dls:signature:waive` when you only want to suppress the warning while
 * preserving the diagnostic — removal drops the signature chain entirely.
 */
class RemoveCommand extends AbstractSignatureCommand
{
    protected $signature = 'dls:signature:remove
                            {target : Plugin slug or directory name}
                            {--scope=plugin : plugin|theme|core (Phase 1: plugin only)}
                            {--confirm : Required (destructive: deletes signature.sig)}
                            {--json : Output the result as JSON}';

    protected $description = 'Delete an extension signature.sig (destructive; result is the unsigned state)';

    public function handle(): int
    {
        $scope = $this->resolveScope();
        if (is_int($scope)) {
            return $scope;
        }

        $target = (string) $this->argument('target');
        $directory = $this->directoryName($target);

        if (! $this->pluginExists($target)) {
            $this->error(sprintf('Plugin "%s" not found (no plugins/%s/plugin.json).', $target, $directory));

            return self::FAILURE;
        }

        $signaturePath = base_path('plugins/'.$directory.'/signature.sig');

        if (! File::exists($signaturePath)) {
            $this->infoOrJson($scope, $directory, 'already_unsigned', sprintf('No signature.sig for "%s" — already unsigned.', $directory));

            return self::SUCCESS;
        }

        if (! $this->option('confirm')) {
            $this->warn(sprintf('This permanently deletes plugins/%s/signature.sig (destructive).', $directory));
            $this->line('The extension will become "unsigned"; operators can restore it only by reinstalling.');
            $this->line('Re-run with --confirm to proceed.');

            return self::SUCCESS;
        }

        File::delete($signaturePath);

        $this->infoOrJson($scope, $directory, 'removed', sprintf('Removed plugins/%s/signature.sig. Status becomes "unsigned" on next audit.', $directory));

        return self::SUCCESS;
    }

    /**
     * Emit a result line as JSON or human-readable text.
     */
    protected function infoOrJson(string $scope, string $directory, string $status, string $message): void
    {
        if ($this->option('json')) {
            $this->line(json_encode([
                'action' => 'remove',
                'status' => $status,
                'scope' => $scope,
                'target' => $directory,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return;
        }

        $this->info($message);
    }
}
