<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Services\PluginMigrator;
use App\Services\ThemeMigrator;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `--pretend` says what would run, and writes nothing.
 *
 * Both migrators reported what ran by diffing the ledger around
 * `Migrator::run()`. A pretend run writes nothing to the ledger, so the
 * difference was always empty and the commands took their "nothing to run"
 * branch -- telling an operator checking a site before migrating that
 * there was nothing to do, while migrations were pending (#694).
 *
 * The migrators are exercised directly: the commands resolve their path
 * from `plugins/<Name>` / `themes/<Name>`, and a test must not put a
 * fixture inside those real directories. The commands' own pretend
 * branches are pinned by reading them.
 */
class MigratePretendTest extends TestCase
{
    use RefreshDatabase;

    private string $fixtureDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Never inside plugins/ or themes/: those are real repositories.
        $this->fixtureDir = storage_path('framework/testing/pretend-'.uniqid());
        File::ensureDirectoryExists($this->fixtureDir);

        File::put($this->fixtureDir.'/0001_01_01_000001_create_pretend_fixture_table.php', <<<'PHP'
            <?php

            use Illuminate\Database\Migrations\Migration;
            use Illuminate\Database\Schema\Blueprint;
            use Illuminate\Support\Facades\Schema;

            return new class extends Migration
            {
                public function up(): void
                {
                    Schema::create('pretend_fixture', function (Blueprint $table) {
                        $table->id();
                    });
                }

                public function down(): void
                {
                    Schema::dropIfExists('pretend_fixture');
                }
            };
            PHP);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->fixtureDir)) {
            File::deleteDirectory($this->fixtureDir);
        }

        parent::tearDown();
    }

    private function pluginMigrator(): PluginMigrator
    {
        return new PluginMigrator(
            app(Filesystem::class),
            app(ConnectionResolverInterface::class),
            'plugin_migrations',
            'pretend-fixture',
        );
    }

    private function themeMigrator(): ThemeMigrator
    {
        return new ThemeMigrator(
            app(Filesystem::class),
            app(ConnectionResolverInterface::class),
            'theme_migrations',
            'pretend-fixture',
        );
    }

    private function ledgerCount(string $table): int
    {
        return (int) app(ConnectionResolverInterface::class)
            ->connection()
            ->table($table)
            ->where(str_contains($table, 'plugin') ? 'plugin' : 'theme', 'pretend-fixture')
            ->count();
    }

    #[Test]
    public function a_plugin_pretend_run_names_the_pending_migration_and_writes_nothing(): void
    {
        $migrated = $this->pluginMigrator()->migrate('PretendFixture', $this->fixtureDir, ['pretend' => true]);

        $this->assertSame(
            ['0001_01_01_000001_create_pretend_fixture_table'],
            $migrated,
            'A pretend run has to report the migration that would run.'
        );
        $this->assertSame(0, $this->ledgerCount('plugin_migrations'), 'A pretend run must write nothing.');
    }

    #[Test]
    public function a_plugin_pretend_run_reports_nothing_once_the_migration_has_run(): void
    {
        $migrator = $this->pluginMigrator();

        $applied = $migrator->migrate('PretendFixture', $this->fixtureDir, []);
        $this->assertSame(['0001_01_01_000001_create_pretend_fixture_table'], $applied);
        $this->assertSame(1, $this->ledgerCount('plugin_migrations'));

        $this->assertSame(
            [],
            $migrator->migrate('PretendFixture', $this->fixtureDir, ['pretend' => true]),
            '"Nothing to run" has to stay true when there is nothing to run.'
        );
    }

    #[Test]
    public function the_plugin_pending_list_is_the_files_minus_the_ledger(): void
    {
        $migrator = $this->pluginMigrator();

        $this->assertSame(
            ['0001_01_01_000001_create_pretend_fixture_table'],
            $migrator->pendingMigrations('PretendFixture', $this->fixtureDir)
        );

        $migrator->migrate('PretendFixture', $this->fixtureDir, []);

        $this->assertSame([], $migrator->pendingMigrations('PretendFixture', $this->fixtureDir));
    }

    #[Test]
    public function a_theme_pretend_run_names_the_pending_migration_and_writes_nothing(): void
    {
        $migrated = $this->themeMigrator()->migrate('PretendFixture', $this->fixtureDir, ['pretend' => true]);

        $this->assertSame(
            ['0001_01_01_000001_create_pretend_fixture_table'],
            $migrated,
            'The theme migrator had the same defect, so it gets the same guarantee.'
        );
        $this->assertSame(0, $this->ledgerCount('theme_migrations'), 'A pretend run must write nothing.');
    }

    #[Test]
    public function a_theme_pretend_run_reports_nothing_once_the_migration_has_run(): void
    {
        $migrator = $this->themeMigrator();

        $migrator->migrate('PretendFixture', $this->fixtureDir, []);
        $this->assertSame(1, $this->ledgerCount('theme_migrations'));

        $this->assertSame([], $migrator->migrate('PretendFixture', $this->fixtureDir, ['pretend' => true]));
    }

    #[Test]
    public function neither_command_records_an_audit_entry_for_a_pretend_run(): void
    {
        // The list a pretend run returns is "would run", not "did run", so
        // the audit call and the success line must sit outside that branch
        // -- otherwise making the list non-empty would start recording dry
        // runs as migrations.
        foreach ([
            'PluginMigrate' => 'ACTION_PLUGIN_MIGRATED',
            'ThemeMigrate' => 'ACTION_THEME_MIGRATED',
        ] as $command => $action) {
            $source = (string) file_get_contents(app_path("Console/Commands/{$command}.php"));

            $pretendAt = strpos($source, 'Would run: ');
            $auditAt = strpos($source, $action);

            $this->assertNotFalse($pretendAt, "{$command} must report what would run.");
            $this->assertNotFalse($auditAt, "{$command} is expected to record a real migration.");
            $this->assertGreaterThan(
                $pretendAt,
                $auditAt,
                "{$command}: the audit call must not be reachable from the pretend branch."
            );
        }
    }

    #[Test]
    public function both_commands_hand_the_migrator_an_output_for_the_sql(): void
    {
        foreach (['PluginMigrate', 'ThemeMigrate'] as $command) {
            $source = (string) file_get_contents(app_path("Console/Commands/{$command}.php"));

            $this->assertMatchesRegularExpression(
                '/if \(\$pretend\) \{\s*\n\s*\$\w+(->\w+)?->setOutput\(\$this->output\);/',
                $source,
                "{$command}: a pretend run's SQL is written by the migrator, so it needs an output."
            );
        }
    }
}
