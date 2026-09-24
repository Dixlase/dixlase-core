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

namespace App\Services\Core;

use Illuminate\Support\Facades\Cache;

/**
 * @api Stable API usable from plugins and themes.
 *
 * Answers one question: does the `vendor/` on disk match the `composer.lock`
 * that ships beside it?
 *
 * A core update swaps the source tree and `vendor/` in separate steps. If the
 * process dies between them — killed, OOM, or maintenance lifted out from
 * under it — the site is left with new source and old dependencies, and
 * **nothing else notices**. Observed on the sandbox 2026-09-24: source,
 * `VERSION` and `composer.lock` all said 0.3.39 while `vendor/` was still
 * 0.3.38, and the front page, the admin panel and the Laravel log were all
 * clean. The gap happened to be a patch-level framework bump; the same
 * interruption across a framework major leaves a tree that fatals on boot
 * while the panel still reports the new version.
 *
 * The check is a comparison of two files Composer itself writes, so it costs
 * no network and no subprocess.
 */
class DependencyIntegrityService
{
    /** vendor/ matches the lockfile. */
    public const STATE_OK = 'ok';

    /** vendor/ does not match: an interrupted swap, or a hand-edited tree. */
    public const STATE_MISMATCHED = 'mismatched';

    /** One of the two files is missing or unreadable — cannot judge. */
    public const STATE_UNKNOWN = 'unknown';

    /**
     * How many mismatching packages to name in the summary. The count is what
     * matters; the names are there so the operator can recognise a framework
     * bump without opening a shell.
     */
    private const SAMPLE_LIMIT = 3;

    private const CACHE_KEY = 'core.dependency_integrity';

    private const CACHE_TTL = 300;

    /**
     * @return array{state: string, checked: int, mismatched: int, samples: list<array{name: string, locked: string, installed: string|null}>, reason: string|null}
     */
    public function check(?string $basePath = null): array
    {
        $base = $basePath ?? base_path();
        $lockPath = $base.'/composer.lock';
        $installedPath = $base.'/vendor/composer/installed.php';

        if (! is_file($lockPath) || ! is_readable($lockPath)) {
            return $this->unknown('composer.lock is missing or unreadable');
        }
        if (! is_file($installedPath) || ! is_readable($installedPath)) {
            return $this->unknown('vendor/composer/installed.php is missing or unreadable');
        }

        $lock = json_decode((string) file_get_contents($lockPath), true);
        if (! is_array($lock) || ! isset($lock['packages']) || ! is_array($lock['packages'])) {
            return $this->unknown('composer.lock could not be parsed');
        }

        /** @var mixed $installed */
        $installed = @include $installedPath;
        if (! is_array($installed) || ! isset($installed['versions']) || ! is_array($installed['versions'])) {
            return $this->unknown('vendor/composer/installed.php could not be read');
        }
        $versions = $installed['versions'];

        $mismatched = [];
        $checked = 0;

        // Only the production set. A `--no-dev` install legitimately has no
        // dev packages, so comparing `packages-dev` would report a mismatch on
        // every correctly-installed production site.
        foreach ($lock['packages'] as $package) {
            if (! is_array($package) || ! isset($package['name'], $package['version'])) {
                continue;
            }
            $name = (string) $package['name'];
            $lockedVersion = (string) $package['version'];
            $checked++;

            $installedVersion = null;
            if (isset($versions[$name]) && is_array($versions[$name])) {
                $pretty = $versions[$name]['pretty_version'] ?? null;
                $installedVersion = is_string($pretty) ? $pretty : null;
            }

            // A replaced or provided package has no pretty_version of its own;
            // treat "present but versionless" as satisfied rather than crying
            // wolf, and only flag an outright absence or a different version.
            if ($installedVersion === null && array_key_exists($name, $versions)) {
                continue;
            }

            if ($installedVersion !== $lockedVersion) {
                $mismatched[] = [
                    'name' => $name,
                    'locked' => $lockedVersion,
                    'installed' => $installedVersion,
                ];
            }
        }

        return [
            'state' => $mismatched === [] ? self::STATE_OK : self::STATE_MISMATCHED,
            'checked' => $checked,
            'mismatched' => count($mismatched),
            'samples' => array_slice($mismatched, 0, self::SAMPLE_LIMIT),
            'reason' => null,
        ];
    }

    /**
     * Cached for the dashboard, which renders on every admin page load.
     *
     * No explicit invalidation is needed: both `CoreUpdater` and
     * `CoreRollback` run `cache:clear` at the end, so a repaired tree stops
     * being reported as soon as the operation that repaired it finishes.
     *
     * @return array{state: string, checked: int, mismatched: int, samples: list<array{name: string, locked: string, installed: string|null}>, reason: string|null}
     */
    public function checkCached(): array
    {
        /** @var array{state: string, checked: int, mismatched: int, samples: list<array{name: string, locked: string, installed: string|null}>, reason: string|null} */
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->check());
    }

    /**
     * @return array{state: string, checked: int, mismatched: int, samples: list<array{name: string, locked: string, installed: string|null}>, reason: string|null}
     */
    private function unknown(string $reason): array
    {
        return [
            'state' => self::STATE_UNKNOWN,
            'checked' => 0,
            'mismatched' => 0,
            'samples' => [],
            'reason' => $reason,
        ];
    }
}
