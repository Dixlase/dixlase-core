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

namespace App\Services;

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 */
class ThemeMigrator
{
    protected Migrator $migrator;

    protected Filesystem $files;

    protected MigrationRepositoryInterface $repository;

    protected ?string $themeSlug;

    /**
     * Constructor
     */
    public function __construct(
        Filesystem $files,
        ConnectionResolverInterface $resolver,
        string $migrationTable = 'theme_migrations',
        ?string $themeSlug = null // nullable
    ) {
        $this->files = $files;
        $this->themeSlug = $themeSlug;

        // Create repository for `theme_migrations` table
        $this->repository = new ThemeMigrationRepository($resolver, $migrationTable, $themeSlug);

        // Create Migrator instance
        $this->migrator = new Migrator(
            $this->repository,
            $resolver,
            $this->files
        );

        // Set default database connection (change as needed)
        $this->migrator->setConnection($resolver->getDefaultConnection());
    }

    /**
     * Run migrations for the specified theme
     *
     * @param  string  $theme  Theme name
     * @param  string|null  $path  Path to migration files (defaults to within theme directory)
     * @param  array  $options  Options (e.g. --force)
     * @return array Details of executed migrations
     *
     * @throws \Exception
     */
    public function migrate(string $theme, ?string $path = null, array $options = []): array
    {
        $migrationPath = $path ?? base_path("themes/{$theme}/database/migrations");

        Log::info('ThemeMigrator: Starting migration', [
            'theme' => $theme,
            'path' => $migrationPath,
            'slug' => $this->themeSlug,
        ]);

        if (! $this->files->isDirectory($migrationPath)) {
            Log::error('ThemeMigrator: Migration path does not exist', ['path' => $migrationPath]);
            throw new \Exception("Migration path does not exist: {$migrationPath}");
        }

        // Get list of migration files
        $files = $this->files->glob($migrationPath.'/*.php');
        Log::info('ThemeMigrator: Found migration files', [
            'count' => count($files),
            'files' => array_map('basename', $files),
        ]);

        // Get migration files before execution
        $before = $this->repository->getRan();
        Log::info('ThemeMigrator: Migrations before run', ['count' => count($before)]);

        // Set migration path on migrator
        $this->migrator->run($migrationPath, [
            'pretend' => $options['pretend'] ?? false,
            'step' => $options['step'] ?? false,
        ]);

        // Get migration files after execution
        $after = $this->repository->getRan($this->themeSlug);
        Log::info('ThemeMigrator: Migrations after run', ['count' => count($after)]);

        // Extract newly executed migration files
        $migrated = array_diff($after, $before);
        Log::info('ThemeMigrator: Migration completed', [
            'migrated_count' => count($migrated),
            'migrated' => array_values($migrated),
        ]);

        return array_values($migrated);
    }

    /**
     * Roll back migrations for the specified theme
     *
     * @param  string  $theme  Theme name
     * @param  array  $options  Options (e.g. --step=1)
     * @return array Notes for rolled back migrations
     *
     * @throws \Exception
     */
    public function rollback(string $theme, array $options = []): array
    {
        $migrationPath = base_path("themes/{$theme}/database/migrations");

        Log::info('ThemeMigrator: Starting rollback', [
            'theme' => $theme,
            'path' => $migrationPath,
            'slug' => $this->themeSlug,
            'options' => $options,
        ]);

        if (! $this->files->isDirectory($migrationPath)) {
            Log::error('ThemeMigrator: Migration path does not exist', ['path' => $migrationPath]);
            throw new \Exception("Migration path does not exist: {$migrationPath}");
        }

        // Get list of migration files
        $files = $this->files->glob($migrationPath.'/*.php');
        Log::info('ThemeMigrator: Found migration files for rollback', [
            'count' => count($files),
            'files' => array_map('basename', $files),
        ]);

        // Get migration files before rollback
        $before = $this->repository->getRan();
        Log::info('ThemeMigrator: Migrations before rollback', [
            'count' => count($before),
            'migrations' => $before,
        ]);

        // Set migration path on migrator
        $this->migrator->rollback($migrationPath, [
            'step' => $options['step'] ?? 1,
            'pretend' => $options['pretend'] ?? false,
        ]);

        // Get migration files after rollback
        $after = $this->repository->getRan();
        Log::info('ThemeMigrator: Migrations after rollback', [
            'count' => count($after),
            'migrations' => $after,
        ]);

        // Extract rolled back migration files
        $rolledBack = array_diff($before, $after);
        Log::info('ThemeMigrator: Rollback completed', [
            'rolled_back_count' => count($rolledBack),
            'rolled_back' => array_values($rolledBack),
        ]);

        return array_values($rolledBack);
    }

    /**
     * Refresh migrations for the specified theme
     *
     * @param  string  $theme  Theme name
     * @param  array  $options  Options (e.g. --step=1)
     * @return array Notes of migrations executed after refresh
     *
     * @throws \Exception
     */
    public function refresh(string $theme, array $options = []): array
    {
        // Rollback
        $rolledBack = $this->rollback($theme, $options);

        // Re-execute
        $migrated = $this->migrate($theme, null, $options);

        return array_merge($rolledBack, $migrated);
    }

    /**
     * Get migration status for the specified theme
     *
     * @param  string  $theme  Theme name
     *
     * @throws \Exception
     */
    public function getMigrationStatus(string $theme): array
    {
        $migrationPath = base_path("themes/{$theme}/database/migrations");

        if (! $this->files->isDirectory($migrationPath)) {
            throw new \Exception("Migration path does not exist: {$migrationPath}");
        }

        $migrations = $this->migrator->getMigrationFiles($migrationPath);

        $ran = $this->repository->getRan();
        $ran = array_filter($ran, function ($migration) use ($theme) {
            // Filter only migrations that include the theme name as prefix
            return Str::startsWith($migration, Str::snake($theme).'_');
        });

        $status = [];
        foreach ($migrations as $file => $path) {
            $status[] = [
                'migration' => basename($file, '.php'),
                'ran' => in_array(basename($file, '.php'), $ran),
            ];
        }

        return $status;
    }
}
