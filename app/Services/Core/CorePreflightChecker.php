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

use Closure;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * Checks that a core update can finish before it starts changing anything
 * (backlog core-update-rollback-hardening #1).
 *
 * The update is executed by the currently installed core, so these checks
 * have to ship in the release that performs the update, not the one being
 * installed. They run in three stages, each at the last point where the
 * information exists and nothing irreversible has happened yet:
 *
 *   - run()                      before the source snapshot: PHP version,
 *                                extensions, write permissions, disk space,
 *                                leftovers of an interrupted run.
 *   - checkDownloadedArchive()   after the download, before extraction:
 *                                room to unpack the actual archive.
 *   - checkReleaseRequirements() after extraction, before maintenance mode:
 *                                the new release's own PHP requirement.
 *
 * Every filesystem and runtime probe is injectable so tests do not depend
 * on the host (the dev container runs as root, where is_writable() is
 * always true).
 */
final class CorePreflightChecker
{
    /**
     * Extensions the update itself needs (download, unpack, migrate) plus the
     * framework baseline. The PDO driver for the configured connection is
     * added at runtime.
     */
    public const REQUIRED_EXTENSIONS = [
        'zip', 'curl', 'openssl', 'mbstring', 'json', 'ctype',
        'tokenizer', 'xml', 'dom', 'fileinfo', 'pdo',
    ];

    /**
     * Headroom on top of the estimated space, for logs, caches, the DB dump
     * and anything the estimate cannot see.
     */
    public const SAFETY_MARGIN_BYTES = 300 * 1024 * 1024;

    /**
     * @param  string|null  $basePath  Core root; defaults to base_path().
     * @param  Closure|null  $extensionLoaded  fn (string $ext): bool
     * @param  Closure|null  $isWritable  fn (string $path): bool
     * @param  Closure|null  $freeSpace  fn (string $path): ?float — bytes, null when unknown
     * @param  string|null  $phpVersion  Defaults to PHP_VERSION.
     * @param  Closure|null  $driftDetector  fn (): array — VersionDriftService::detect() shape
     * @param  Closure|null  $dependencyChecker  fn (): array — DependencyIntegrityService::check() shape
     */
    public function __construct(
        private readonly ?string $basePath = null,
        private readonly ?Closure $extensionLoaded = null,
        private readonly ?Closure $isWritable = null,
        private readonly ?Closure $freeSpace = null,
        private readonly ?string $phpVersion = null,
        private readonly ?Closure $driftDetector = null,
        private readonly ?Closure $dependencyChecker = null,
    ) {}

    /**
     * Environment checks. Runs before the update touches anything.
     */
    public function run(): PreflightResult
    {
        $result = new PreflightResult();

        $this->checkPhpVersion($result, $this->root('dixlase.json'), 'php_version', 'installed release');
        $this->checkExtensions($result);
        $this->checkWritable($result);
        $this->checkDiskSpace($result);
        $this->checkLeftovers($result);
        $this->checkDependencyIntegrity($result);
        $this->checkVersionDrift($result);

        return $result;
    }

    /**
     * Room to unpack the downloaded archive next to the staging directory.
     */
    public function checkDownloadedArchive(string $zipPath, string $stagingPath): PreflightResult
    {
        $result = new PreflightResult();

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            // Extraction reports the real error right after this; do not
            // pre-empt it with a guess.
            return $result->add('archive_space', PreflightResult::WARN, 'could not open the archive to measure it');
        }

        $unpacked = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $unpacked += is_array($stat) ? (int) $stat['size'] : 0;
        }
        $zip->close();

        $needed = $unpacked + self::SAFETY_MARGIN_BYTES;
        $probe = $this->existingAncestor($stagingPath);
        $free = $this->free($probe);

        if ($free === null) {
            return $result->add('archive_space', PreflightResult::WARN, sprintf('free space at %s is unknown; the release unpacks to %s', $probe, self::human($unpacked)));
        }
        if ($free < $needed) {
            return $result->add('archive_space', PreflightResult::FAIL, sprintf('the release unpacks to %s and needs %s free at %s, but only %s is available', self::human($unpacked), self::human($needed), $probe, self::human($free)));
        }

        return $result->add('archive_space', PreflightResult::OK, sprintf('%s to unpack, %s free', self::human($unpacked), self::human($free)));
    }

    /**
     * The new release's own PHP requirement, read from its dixlase.json.
     */
    public function checkReleaseRequirements(string $payloadRoot): PreflightResult
    {
        $result = new PreflightResult();
        $this->checkPhpVersion($result, rtrim($payloadRoot, '/').'/dixlase.json', 'release_php_version', 'new release');

        return $result;
    }

    private function checkPhpVersion(PreflightResult $result, string $manifestPath, string $name, string $label): void
    {
        $running = $this->phpVersion ?? PHP_VERSION;
        $constraint = $this->phpConstraint($manifestPath);

        if ($constraint === null) {
            $result->add($name, PreflightResult::OK, sprintf('PHP %s (the %s declares no PHP requirement)', $running, $label));

            return;
        }

        if (preg_match('/(\d+\.\d+(?:\.\d+)?)/', $constraint, $m) !== 1) {
            $result->add($name, PreflightResult::WARN, sprintf('cannot read the %s PHP requirement "%s"; running PHP %s', $label, $constraint, $running));

            return;
        }

        $minimum = $m[1];
        if (version_compare($running, $minimum, '<')) {
            $result->add($name, PreflightResult::FAIL, sprintf('the %s requires PHP %s but this server runs PHP %s', $label, $constraint, $running));

            return;
        }

        $result->add($name, PreflightResult::OK, sprintf('PHP %s satisfies %s', $running, $constraint));
    }

    private function phpConstraint(string $manifestPath): ?string
    {
        if (! is_file($manifestPath)) {
            return null;
        }

        $decoded = json_decode((string) @file_get_contents($manifestPath), true);
        $php = is_array($decoded) ? ($decoded['requires']['php'] ?? null) : null;

        return is_string($php) && $php !== '' ? $php : null;
    }

    private function checkExtensions(PreflightResult $result): void
    {
        $required = self::REQUIRED_EXTENSIONS;
        $driver = $this->pdoDriverExtension();
        if ($driver !== null) {
            $required[] = $driver;
        }

        $loaded = $this->extensionLoaded ?? static fn (string $ext): bool => extension_loaded($ext);
        $missing = array_values(array_filter($required, static fn (string $ext): bool => ! $loaded($ext)));

        if ($missing !== []) {
            $result->add('php_extensions', PreflightResult::FAIL, 'missing PHP extensions: '.implode(', ', $missing));

            return;
        }

        $result->add('php_extensions', PreflightResult::OK, count($required).' required extensions loaded');
    }

    private function pdoDriverExtension(): ?string
    {
        $connection = (string) config('database.default');
        $driver = (string) config("database.connections.{$connection}.driver", '');

        return match ($driver) {
            'mysql', 'mariadb' => 'pdo_mysql',
            'pgsql' => 'pdo_pgsql',
            'sqlite' => 'pdo_sqlite',
            'sqlsrv' => 'pdo_sqlsrv',
            default => null,
        };
    }

    /**
     * Every directory the update creates files in or renames entries of.
     * A live source directory is replaced by building `<dir>.new` next to
     * it and renaming, so its parent must be writable too.
     */
    private function checkWritable(PreflightResult $result): void
    {
        $paths = [$this->root()];

        foreach (CoreSourceSnapshot::SOURCE_DIRECTORIES as $dir) {
            $paths[] = $this->root($dir);
            $paths[] = dirname($this->root($dir));
        }
        foreach (CoreSourceSnapshot::SOURCE_FILES as $file) {
            $path = $this->root($file);
            $paths[] = is_file($path) ? $path : dirname($path);
        }
        foreach (['storage/app/private/core-update', 'storage/framework', 'bootstrap/cache', 'vendor'] as $dir) {
            $paths[] = $this->existingAncestor($this->root($dir));
        }
        $paths[] = $this->existingAncestor((string) config('extension-sources.download_path', $this->root('storage/app/extension-downloads')));

        $check = $this->isWritable ?? static fn (string $path): bool => is_writable($path);
        $blocked = [];
        foreach (array_unique($paths) as $path) {
            if (file_exists($path) && ! $check($path)) {
                $blocked[] = $this->relative($path);
            }
        }

        if ($blocked !== []) {
            $result->add('write_permissions', PreflightResult::FAIL, 'not writable by the PHP user: '.implode(', ', $blocked));

            return;
        }

        $result->add('write_permissions', PreflightResult::OK, 'core directories and files are writable');
    }

    /**
     * The source directories are copied once into the snapshot and once
     * more as `<dir>.new` while being replaced, so budget twice their size
     * plus the margin. The downloaded archive gets its own check later,
     * once its real size is known.
     */
    private function checkDiskSpace(PreflightResult $result): void
    {
        $source = 0;
        foreach (CoreSourceSnapshot::SOURCE_DIRECTORIES as $dir) {
            $source += $this->directorySize($this->root($dir));
        }

        $needed = ($source * 2) + self::SAFETY_MARGIN_BYTES;
        $free = $this->free($this->root());

        if ($free === null) {
            $result->add('disk_space', PreflightResult::WARN, sprintf('free space is unknown; the update needs about %s', self::human($needed)));

            return;
        }
        if ($free < $needed) {
            $result->add('disk_space', PreflightResult::FAIL, sprintf('the update needs about %s free (snapshot + replacement of %s of source) but only %s is available', self::human($needed), self::human($source), self::human($free)));

            return;
        }

        $result->add('disk_space', PreflightResult::OK, sprintf('%s free, about %s needed before the download', self::human($free), self::human($needed)));
    }

    /**
     * Remnants of an interrupted run. The updater clears them itself, so
     * they only warrant a warning — but the operator should know an earlier
     * update did not finish.
     */
    private function checkLeftovers(PreflightResult $result): void
    {
        $found = [];
        if (file_exists($this->root('vendor.old'))) {
            $found[] = 'vendor.old';
        }
        foreach (CoreSourceSnapshot::SOURCE_DIRECTORIES as $dir) {
            foreach (['.old', '.new'] as $suffix) {
                if (file_exists($this->root($dir.$suffix))) {
                    $found[] = $dir.$suffix;
                }
            }
        }
        if (file_exists($this->root('storage/framework/maintenance.php'))) {
            $found[] = 'maintenance mode is already on';
        }

        if ($found !== []) {
            $result->add('previous_run', PreflightResult::WARN, 'an earlier update or rollback may not have finished: '.implode(', ', $found));

            return;
        }

        $result->add('previous_run', PreflightResult::OK, 'no leftovers from an interrupted run');
    }

    /**
     * Does the `vendor/` on disk still match `composer.lock`?
     *
     * A mismatch means an earlier update died between swapping the source and
     * swapping `vendor/`, and nothing else reports it: the site answers 200
     * and the panel shows the new version (sandbox, 2026-09-24).
     *
     * Warn, never block. Re-running the update is exactly how an operator
     * repairs this, so refusing to start would trap them in the broken state.
     */
    private function checkDependencyIntegrity(PreflightResult $result): void
    {
        try {
            $check = $this->dependencyChecker !== null
                ? ($this->dependencyChecker)()
                : app(DependencyIntegrityService::class)->check($this->basePath);
        } catch (\Throwable) {
            return; // Informational only; never block on it.
        }

        if ($check['state'] !== DependencyIntegrityService::STATE_MISMATCHED) {
            return;
        }

        $names = implode(', ', array_map(
            static fn (array $s): string => $s['name'].' '.($s['installed'] ?? 'absent').' != '.$s['locked'],
            $check['samples']
        ));

        $result->add('dependency_integrity', PreflightResult::WARN, sprintf(
            '%d installed package(s) do not match composer.lock (%s); an earlier update probably stopped mid-swap',
            $check['mismatched'],
            $names
        ));
    }

    private function checkVersionDrift(PreflightResult $result): void
    {
        try {
            $drift = $this->driftDetector !== null
                ? ($this->driftDetector)()
                : app(VersionDriftService::class)->detect();
        } catch (\Throwable) {
            return; // Informational only; never block on it.
        }

        if (! empty($drift['manifest_drifted'])) {
            $result->add('version_files', PreflightResult::WARN, sprintf('VERSION (%s) and dixlase.json (%s) disagree', $drift['on_disk'] ?? '?', $drift['manifest'] ?? '?'));
        }
    }

    private function directorySize(string $path): int
    {
        if (! is_dir($path)) {
            return 0;
        }

        $size = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
            RecursiveIteratorIterator::CATCH_GET_CHILD
        );
        foreach ($iterator as $file) {
            // Never follow symlinks (public/storage, asset links): they are
            // not copied as content, and following them can loop or leave
            // the core tree entirely.
            if ($file->isLink() || ! $file->isFile()) {
                continue;
            }
            $size += (int) $file->getSize();
        }

        return $size;
    }

    private function free(string $path): ?float
    {
        if ($this->freeSpace !== null) {
            return ($this->freeSpace)($path);
        }

        $free = @disk_free_space($path);

        return $free === false ? null : (float) $free;
    }

    private function existingAncestor(string $path): string
    {
        while (! file_exists($path) && dirname($path) !== $path) {
            $path = dirname($path);
        }

        return $path;
    }

    private function root(string $relative = ''): string
    {
        $base = rtrim($this->basePath ?? base_path(), '/');

        return $relative === '' ? $base : $base.'/'.ltrim($relative, '/');
    }

    private function relative(string $path): string
    {
        $base = $this->root().'/';

        return str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
    }

    private static function human(float|int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $value = (float) $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return sprintf($i === 0 ? '%d %s' : '%.1f %s', $value, $units[$i]);
    }
}
