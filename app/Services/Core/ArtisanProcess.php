<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @internal Core only. Do not reference from plugins/themes
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

namespace App\Services\Core;

use App\Support\Process\SubprocessEnvironment;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Runs an Artisan command in a new PHP process.
 *
 * A core update or rollback that replaces vendor/ keeps running in the
 * process that booted from the previous vendor/: its class loader and
 * the service providers it registered still describe packages that may
 * be gone. When a release dropped livewire/livewire, the in-process
 * view:cache went through Livewire's Blade extension and failed on a
 * file the new vendor/ no longer had, so the first update always failed.
 * A new process boots from the new vendor/ and the rebuilt package
 * manifest instead.
 */
class ArtisanProcess
{
    private const TIMEOUT_SECONDS = 1800;

    public function __construct(
        private ?string $artisanPath = null,
        private ?string $phpBinary = null,
    ) {}

    /**
     * Run `php artisan <command>` and return its output.
     *
     * Options are given the way Artisan::call() takes them:
     * ['--force' => true, '--path' => 'database/migrations'].
     *
     * @param  array<string, bool|int|string>  $options
     *
     * @throws RuntimeException when the command exits non-zero
     */
    public function run(string $command, array $options = []): string
    {
        $argv = $this->commandLine($command, $options);

        $process = new Process($argv, dirname($this->artisan()), SubprocessEnvironment::inherit());
        $process->setTimeout(self::TIMEOUT_SECONDS);
        $process->run();

        $output = trim($process->getOutput()."\n".$process->getErrorOutput());

        if (! $process->isSuccessful()) {
            throw new RuntimeException(sprintf(
                'php artisan %s failed (exit %s): %s',
                $command,
                $process->getExitCode() ?? 'unknown',
                mb_substr($output, -2000),
            ));
        }

        return $output;
    }

    /**
     * Run the launcher once and report whether a new process boots the app.
     *
     * Called before the tree is touched it doubles as a warm-up: every class
     * run() needs is loaded into memory while the files are still the ones
     * this process was built against. That matters most on a rollback, where
     * the target version predates this class — resolving it after the source
     * swap fails with "include(.../ArtisanProcess.php): Failed to open
     * stream", which is how a v0.3.54 to v0.3.53 rollback left the site
     * answering 500.
     *
     * Called after a restore it answers the question the operator actually
     * has: does the site come up. A recovery that puts files back but leaves
     * a stale bootstrap/cache manifest behind reports success while every
     * request fatals.
     */
    public function boots(): bool
    {
        try {
            $this->run('--version');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, bool|int|string>  $options
     * @return list<string>
     */
    public function commandLine(string $command, array $options = []): array
    {
        $argv = [$this->php(), $this->artisan(), $command];

        foreach ($options as $name => $value) {
            if ($value === false) {
                continue;
            }
            $argv[] = $value === true ? $name : $name.'='.$value;
        }

        if (! array_key_exists('--no-interaction', $options)) {
            $argv[] = '--no-interaction';
        }

        return $argv;
    }

    private function artisan(): string
    {
        return $this->artisanPath ?? base_path('artisan');
    }

    /**
     * The CLI interpreter. The updater itself runs as a CLI process
     * (dls:core:update / dls:core:rollback), so PHP_BINARY is the right
     * binary; under a web SAPI it would point at php-fpm.
     */
    private function php(): string
    {
        return $this->phpBinary ?? PHP_BINARY;
    }
}
