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
                            {target : Plugin/theme slug or directory name, or "core" for --scope=core}
                            {--scope=plugin : plugin|theme|core}
                            {--confirm : Required (destructive: deletes the signature)}
                            {--json : Output the result as JSON}';

    protected $description = 'Delete a signature (destructive; result is the unsigned state). For core, removes the manifest + signature';

    public function handle(): int
    {
        $scope = $this->resolveScope();
        if (is_int($scope)) {
            return $scope;
        }

        if (! $this->ensureCoreMutationAllowed($scope)) {
            return self::FAILURE;
        }

        $target = (string) $this->argument('target');
        $label = $this->canonicalTarget($scope, $target);

        if (! $this->targetExists($scope, $target)) {
            $this->error(sprintf('%s "%s" not found.', ucfirst($scope), $label));

            return self::FAILURE;
        }

        $files = $this->signatureFilesFor($scope, $target);
        $existing = array_values(array_filter($files, static fn (string $f): bool => File::exists($f)));

        if (empty($existing)) {
            $this->infoOrJson($scope, $label, 'already_unsigned', sprintf('No signature present for %s "%s" — already unsigned.', $scope, $label));

            return self::SUCCESS;
        }

        if (! $this->option('confirm')) {
            $this->warn(sprintf('This permanently deletes the signature for %s "%s" (destructive):', $scope, $label));
            foreach ($existing as $file) {
                $this->line('  - '.str_replace(base_path().'/', '', $file));
            }
            $this->line($scope === 'core'
                ? 'Core will become "unsigned" until re-signed in a build environment.'
                : 'The extension will become "unsigned"; operators can restore it only by reinstalling.');
            $this->line('Re-run with --confirm to proceed.');

            return self::SUCCESS;
        }

        foreach ($existing as $file) {
            File::delete($file);
        }

        $this->infoOrJson($scope, $label, 'removed', sprintf('Removed the signature for %s "%s". Status becomes "unsigned".', $scope, $label));

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
