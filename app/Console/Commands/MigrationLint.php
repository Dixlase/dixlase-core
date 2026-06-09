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

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Command for verifying migration immutability.
 *
 * Generate database/migration-lock.json with the `--lock` option just before the GA release
 * (after the Beta 1 → GA beta series; see CLAUDE.md "Migration Editing Policy"),
 * then compare that lock file with current migration files using SHA-256.
 * Detects modification, deletion, or renaming of existing migrations, and
 * new additions in `0001_01_01_*` format (after GA, Laravel standard `YYYY_MM_DD_HHMMSS_*` is required).
 *
 * During the beta series (Beta 1 → GA) no lock file exists; this command
 * treats that as success so beta-phase schema churn is unblocked.
 */
class MigrationLint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:migration:lint
                            {--lock : 現在のマイグレーションからロックファイルを生成する}
                            {--json : 結果を JSON 形式で出力する}
                            {--base-path= : スキャン基準パスを上書きする（テスト用途、本番では指定しない）}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check migration file integrity (generate lock file with --lock before release)';

    /**
     * Absolute path to the lockfile
     */
    protected string $lockFilePath;

    public function handle(): int
    {
        $this->lockFilePath = $this->basePath().'/database/migration-lock.json';

        if ($this->option('lock')) {
            return $this->generateLockfile();
        }

        return $this->verifyLockfile();
    }

    /**
     * Scan base path. Tests can override this via --base-path.
     */
    protected function basePath(): string
    {
        return $this->option('base-path') ?: base_path();
    }

    /**
     * Hash every current migration file with SHA-256 and write the result to the lockfile.
     */
    protected function generateLockfile(): int
    {
        $files = $this->collectMigrationFiles();
        $hashes = [];

        foreach ($files as $relativePath => $absolutePath) {
            $hashes[$relativePath] = hash_file('sha256', $absolutePath);
        }

        ksort($hashes);

        $lockData = [
            'version' => 1,
            'generated_at' => now()->toIso8601String(),
            'description' => 'Dixlase migration immutability lockfile. Do not edit manually. Regenerate with `php artisan dls:migration:lint --lock`.',
            'files' => $hashes,
        ];

        File::put(
            $this->lockFilePath,
            json_encode($lockData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n"
        );

        if ($this->option('json')) {
            $this->line(json_encode([
                'action' => 'lock',
                'status' => 'success',
                'lockfile' => 'database/migration-lock.json',
                'file_count' => count($hashes),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->info(sprintf(
                'Lockfile written: database/migration-lock.json (%d files)',
                count($hashes)
            ));
        }

        return Command::SUCCESS;
    }

    /**
     * Compare the lockfile against the current migration state.
     */
    protected function verifyLockfile(): int
    {
        if (! File::exists($this->lockFilePath)) {
            $msg = 'Lockfile not found (database/migration-lock.json). '
                .'During the Beta 1 → GA beta series this is expected; '
                .'run `php artisan dls:migration:lint --lock` to create it just before the GA release.';

            if ($this->option('json')) {
                $this->line(json_encode([
                    'action' => 'verify',
                    'status' => 'no_lockfile',
                    'message' => $msg,
                ], JSON_PRETTY_PRINT));
            } else {
                $this->warn($msg);
            }

            return Command::SUCCESS;
        }

        $lockData = json_decode(File::get($this->lockFilePath), true);
        if (! is_array($lockData) || ! isset($lockData['files']) || ! is_array($lockData['files'])) {
            $this->error('Lockfile is corrupt or has unexpected schema: database/migration-lock.json');

            return Command::FAILURE;
        }

        $locked = $lockData['files'];

        $currentFiles = $this->collectMigrationFiles();
        $currentHashes = [];
        foreach ($currentFiles as $relativePath => $absolutePath) {
            $currentHashes[$relativePath] = hash_file('sha256', $absolutePath);
        }

        $modified = [];
        $deleted = [];
        $added = [];
        $namingViolations = [];

        // Detect modifications and deletions
        foreach ($locked as $path => $hash) {
            if (! isset($currentHashes[$path])) {
                $deleted[] = $path;
            } elseif ($currentHashes[$path] !== $hash) {
                $modified[] = $path;
            }
        }

        // Detection of new additions and naming convention violations.
        // New migrations added after GA (when the lock file exists)
        // must use Laravel standard YYYY_MM_DD_HHMMSS_* format; 0001_01_01_* is a violation.
        foreach ($currentHashes as $path => $hash) {
            if (! isset($locked[$path])) {
                $added[] = $path;
                $basename = basename($path);
                if (preg_match('/^0001_01_01_/', $basename)) {
                    $namingViolations[] = $path;
                }
            }
        }

        sort($modified);
        sort($deleted);
        sort($added);
        sort($namingViolations);

        $hasViolations = ! empty($modified) || ! empty($deleted) || ! empty($namingViolations);

        if ($this->option('json')) {
            $this->line(json_encode([
                'action' => 'verify',
                'status' => $hasViolations ? 'violation' : 'clean',
                'lockfile_generated_at' => $lockData['generated_at'] ?? null,
                'locked_file_count' => count($locked),
                'current_file_count' => count($currentHashes),
                'modified' => $modified,
                'deleted' => $deleted,
                'added' => $added,
                'naming_violations' => $namingViolations,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->displayReport($modified, $deleted, $added, $namingViolations);
        }

        return $hasViolations ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Display a human-readable report.
     *
     * @param  array<int, string>  $modified
     * @param  array<int, string>  $deleted
     * @param  array<int, string>  $added
     * @param  array<int, string>  $namingViolations
     */
    protected function displayReport(array $modified, array $deleted, array $added, array $namingViolations): void
    {
        if (! empty($modified)) {
            $this->error(sprintf('[VIOLATION] %d modified migration(s):', count($modified)));
            foreach ($modified as $path) {
                $this->line('  <fg=red>M</> '.$path);
            }
            $this->newLine();
        }

        if (! empty($deleted)) {
            $this->error(sprintf('[VIOLATION] %d deleted migration(s):', count($deleted)));
            foreach ($deleted as $path) {
                $this->line('  <fg=red>D</> '.$path);
            }
            $this->newLine();
        }

        if (! empty($namingViolations)) {
            $this->error(sprintf(
                '[VIOLATION] %d naming convention violation(s) (new migrations after GA must use YYYY_MM_DD_HHMMSS_* format):',
                count($namingViolations)
            ));
            foreach ($namingViolations as $path) {
                $this->line('  <fg=red>N</> '.$path);
            }
            $this->newLine();
        }

        $legitAdded = array_values(array_diff($added, $namingViolations));
        if (! empty($legitAdded)) {
            $this->info(sprintf('%d new migration(s) accepted:', count($legitAdded)));
            foreach ($legitAdded as $path) {
                $this->line('  <fg=green>A</> '.$path);
            }
            $this->newLine();
        }

        if (empty($modified) && empty($deleted) && empty($namingViolations)) {
            $this->info('✓ All migration files are consistent with the lockfile.');
        } else {
            $this->error('Migration lint failed. Edits to locked migrations are prohibited after the GA release.');
            $this->line('If this change is intentional pre-GA, regenerate the lockfile:');
            $this->line('  php artisan dls:migration:lint --lock');
        }
    }

    /**
     * Collect every migration file across core, plugins, and themes.
     *
     * @return array<string, string> relative path => absolute path (sorted)
     */
    protected function collectMigrationFiles(): array
    {
        $basePath = $this->basePath();
        $files = [];

        $directories = ['database/migrations'];

        foreach (glob($basePath.'/plugins/*/database/migrations', GLOB_ONLYDIR) ?: [] as $dir) {
            $directories[] = str_replace($basePath.'/', '', $dir);
        }
        foreach (glob($basePath.'/themes/*/database/migrations', GLOB_ONLYDIR) ?: [] as $dir) {
            $directories[] = str_replace($basePath.'/', '', $dir);
        }

        foreach ($directories as $relDir) {
            $absDir = $basePath.'/'.$relDir;
            if (! is_dir($absDir)) {
                continue;
            }
            foreach (glob($absDir.'/*.php') ?: [] as $file) {
                $basename = basename($file);
                // Skip underscore-prefixed backup files (which Laravel itself ignores)
                if (str_starts_with($basename, '_')) {
                    continue;
                }
                $relPath = $relDir.'/'.$basename;
                $files[$relPath] = $file;
            }
        }

        ksort($files);

        return $files;
    }
}
