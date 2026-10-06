<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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

namespace Tests\Feature\Extensions;

use App\Console\Commands\PluginRollback;
use App\Console\Commands\ThemeRollback;
use App\Services\PluginMigrationRepository;
use App\Services\ThemeMigrationRepository;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Mockery;
use Tests\TestCase;

/**
 * A rollback reverses every migration the update added (dixlase-core#455).
 *
 * Regression: the rollback passed the number of batches added since the
 * backup as --step, but --step counts migration files. An update with two
 * migrations puts both in one batch, so only the newer one was reversed.
 *
 * The backup used here lives under storage/framework/testing; the real
 * plugins/ and themes/ are never touched.
 */
class ExtensionRollbackMigrationStepTest extends TestCase
{
    use RefreshDatabase;

    private string $backup;

    protected function setUp(): void
    {
        parent::setUp();

        $root = storage_path('framework/testing/rollback-step-'.uniqid());
        File::ensureDirectoryExists($root);
        $this->backup = $root.'/20261003-000000';
        File::ensureDirectoryExists($this->backup);
        // The backup was taken at batch 1; the update then added batch 2.
        File::put($this->backup.'.meta.json', json_encode(['max_batch' => 1]));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(dirname($this->backup));

        parent::tearDown();
    }

    public function test_the_ledger_counts_the_rows_above_a_batch(): void
    {
        $this->ledger('plugin_migrations', 'plugin', 'step-test');

        $repository = new PluginMigrationRepository(app(ConnectionResolverInterface::class), 'plugin_migrations', 'step-test');

        $this->assertSame(2, $repository->countSinceBatch(1));
        $this->assertSame(3, $repository->countSinceBatch(0));
        $this->assertSame(0, $repository->countSinceBatch(2));
    }

    public function test_a_plugin_rollback_reverses_both_migrations_of_the_update(): void
    {
        $this->ledger('plugin_migrations', 'plugin', 'step-test');

        Artisan::shouldReceive('call')
            ->with('dls:plugin:migrate:rollback', ['plugin' => 'StepTest', '--step' => 2, '--force' => true])
            ->once()
            ->andReturn(0);

        $command = $this->app->make(PluginRollback::class);
        (new \ReflectionMethod($command, 'rollbackSchemaFromBackupMetadata'))->invoke($command, 'step-test', 'StepTest', $this->backup);
    }

    public function test_a_theme_rollback_reverses_both_migrations_of_the_update(): void
    {
        $this->ledger('theme_migrations', 'theme', 'step-theme');

        $this->assertSame(2, (new ThemeMigrationRepository(app(ConnectionResolverInterface::class), 'theme_migrations', 'step-theme'))->countSinceBatch(1));

        Artisan::shouldReceive('call')
            ->with('dls:theme:migrate:rollback', Mockery::on(fn (array $args) => ($args['--step'] ?? null) === 2))
            ->once()
            ->andReturn(0);

        $command = $this->app->make(ThemeRollback::class);
        (new \ReflectionMethod($command, 'rollbackSchemaFromBackupMetadata'))->invoke($command, 'step-theme', 'StepTheme', $this->backup);
    }

    /**
     * One migration from the install (batch 1) and two from the update
     * (batch 2), plus another extension's row that must not be counted.
     */
    private function ledger(string $table, string $column, string $slug): void
    {
        DB::table($table)->insert([
            ['migration' => '2026_01_01_000001_install', 'batch' => 1, $column => $slug],
            ['migration' => '2026_10_01_000001_first', 'batch' => 2, $column => $slug],
            ['migration' => '2026_10_01_000002_second', 'batch' => 2, $column => $slug],
            ['migration' => '2026_10_01_000003_other', 'batch' => 2, $column => 'someone-else'],
        ]);
    }
}
