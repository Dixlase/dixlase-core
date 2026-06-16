<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

use App\Models\Plugin;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PluginMigrator
{
    protected Migrator $migrator;

    protected Filesystem $files;

    protected MigrationRepositoryInterface $repository;

    protected ?string $pluginSlug;

    protected string $migrationTable;

    protected ConnectionResolverInterface $resolver;

    /**
     * Constructor
     */
    public function __construct(
        Filesystem $files,
        ConnectionResolverInterface $resolver,
        string $migrationTable = 'plugin_migrations',
        ?string $pluginSlug = null // nullable
    ) {
        $this->files = $files;
        $this->pluginSlug = $pluginSlug;
        $this->migrationTable = $migrationTable;
        $this->resolver = $resolver;

        // Create repository for `plugin_migrations` table
        $this->repository = new PluginMigrationRepository($resolver, $migrationTable, $pluginSlug);

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
     * Bind the migration repository to a specific plugin.
     *
     * The repository filters `plugin_migrations` rows by plugin slug.
     * Without this binding `getRan()` returns nothing (it queries
     * `where plugin = null`), so Laravel's Migrator treats every file
     * as pending and re-runs already-applied migrations.
     *
     * Calling this at the entry point of every operation lets the
     * service be DI-resolved once (with a null slug) and still target
     * the right plugin per call.
     */
    protected function bindPlugin(string $pluginName): void
    {
        $slug = null;
        if (Schema::hasTable('plugins')) {
            $slug = Plugin::where('name', $pluginName)->value('slug');
        }
        if ($slug === null) {
            $slug = Str::slug(Str::headline($pluginName), '-');
        }

        $this->pluginSlug = $slug;
        if (method_exists($this->repository, 'setPlugin')) {
            $this->repository->setPlugin($slug);
        }
    }

    /**
     * Run migrations for the specified plugin
     *
     * @param  string  $plugin  Plugin name
     * @param  string|null  $path  Path to migration files (defaults to plugin directory)
     * @param  array  $options  Options (e.g. --force)
     * @return array Details of executed migrations
     *
     * @throws \Exception
     */
    public function migrate(string $plugin, ?string $path = null, array $options = []): array
    {
        $this->bindPlugin($plugin);

        $migrationPath = $path ?? base_path("plugins/{$plugin}/database/migrations");

        Log::info('PluginMigrator: Starting migration', [
            'plugin' => $plugin,
            'path' => $migrationPath,
            'slug' => $this->pluginSlug,
        ]);

        if (! $this->files->isDirectory($migrationPath)) {
            Log::info('PluginMigrator: Migration path does not exist, skipping', ['path' => $migrationPath]);

            return [];
        }

        // Get list of migration files
        $files = $this->files->glob($migrationPath.'/*.php');
        Log::info('PluginMigrator: Found migration files', [
            'count' => count($files),
            'files' => array_map('basename', $files),
        ]);

        // Realign the ledger to the (possibly re-sorted) migration files
        // before computing what is pending, so a release that renumbered
        // a migration does not re-run its CREATE over the existing table.
        // No-op when the names already match.
        \App\Services\Migration\MigrationLedgerReconciler::reconcile(
            $this->resolver->connection($this->resolver->getDefaultConnection()),
            $this->migrationTable,
            'plugin',
            $this->pluginSlug,
            $migrationPath,
        );

        // Get migration files before execution
        $before = $this->repository->getRan();
        Log::info('PluginMigrator: Migrations before run', ['count' => count($before)]);

        // Set migration path in migrator
        $this->migrator->run($migrationPath, [
            'pretend' => $options['pretend'] ?? false,
            'step' => $options['step'] ?? false,
        ]);

        // Get migration files after execution
        $after = $this->repository->getRan($this->pluginSlug);
        Log::info('PluginMigrator: Migrations after run', ['count' => count($after)]);

        // Extract newly executed migration files
        $migrated = array_diff($after, $before);
        Log::info('PluginMigrator: Migration completed', [
            'migrated_count' => count($migrated),
            'migrated' => array_values($migrated),
        ]);

        return array_values($migrated);
    }

    /**
     * Roll back migrations for the specified plugin
     *
     * @param  string  $plugin  Plugin name
     * @param  array  $options  Options (e.g. --step=1)
     * @return array Notes of rolled back migrations
     *
     * @throws \Exception
     */
    public function rollback(string $plugin, array $options = []): array
    {
        $this->bindPlugin($plugin);

        $migrationPath = base_path("plugins/{$plugin}/database/migrations");

        Log::info('PluginMigrator: Starting rollback', [
            'plugin' => $plugin,
            'path' => $migrationPath,
            'slug' => $this->pluginSlug,
            'options' => $options,
        ]);

        if (! $this->files->isDirectory($migrationPath)) {
            Log::info('PluginMigrator: Migration path does not exist, skipping rollback', ['path' => $migrationPath]);

            return [];
        }

        // Get list of migration files
        $files = $this->files->glob($migrationPath.'/*.php');
        Log::info('PluginMigrator: Found migration files for rollback', [
            'count' => count($files),
            'files' => array_map('basename', $files),
        ]);

        // Get migration files before rollback
        $before = $this->repository->getRan();
        Log::info('PluginMigrator: Migrations before rollback', [
            'count' => count($before),
            'migrations' => $before,
        ]);

        // Set migration path in migrator
        $this->migrator->rollback($migrationPath, [
            'step' => $options['step'] ?? 1,
            'pretend' => $options['pretend'] ?? false,
        ]);

        // Get migration files after rollback
        $after = $this->repository->getRan();
        Log::info('PluginMigrator: Migrations after rollback', [
            'count' => count($after),
            'migrations' => $after,
        ]);

        // Extract rolled back migration files
        $rolledBack = array_diff($before, $after);
        Log::info('PluginMigrator: Rollback completed', [
            'rolled_back_count' => count($rolledBack),
            'rolled_back' => array_values($rolledBack),
        ]);

        return array_values($rolledBack);
    }

    /**
     * Refresh migrations for the specified plugin
     *
     * @param  string  $plugin  Plugin name
     * @param  array  $options  Options (e.g. --step=1)
     * @return array Notes of migrations executed after refresh
     *
     * @throws \Exception
     */
    public function refresh(string $plugin, array $options = []): array
    {
        // Rollback
        $rolledBack = $this->rollback($plugin, $options);

        // Re-run
        $migrated = $this->migrate($plugin, null, $options);

        return array_merge($rolledBack, $migrated);
    }

    /**
     * Get migration status for the specified plugin
     *
     * @param  string  $plugin  Plugin name
     *
     * @throws \Exception
     */
    public function getMigrationStatus(string $plugin): array
    {
        $migrationPath = base_path("plugins/{$plugin}/database/migrations");

        if (! $this->files->isDirectory($migrationPath)) {
            return [];
        }

        $migrations = $this->migrator->getMigrationFiles($migrationPath);

        $ran = $this->repository->getRan();
        $ran = array_filter($ran, function ($migration) use ($plugin) {
            // Filter only migrations that include the plugin name as prefix
            return Str::startsWith($migration, Str::snake($plugin).'_');
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
