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

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\AuditLog;
use App\Models\Plugin;
use App\Repositories\SecuritySettingRepository;
use App\Services\Extension\ExtensionAuditScope;
use App\Services\Extension\ExtensionRescanService;
use App\Services\Tailwind\PluginSourceAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Mockery;
use Tests\TestCase;

/**
 * The step commands -- migrate, migration rollback, seed -- record a row of
 * their own when run by hand, and none inside an operation that records
 * itself (dixlase-core#749).
 */
class ExtensionStepAuditTest extends TestCase
{
    use RefreshDatabase;

    private const PLUGIN = 'AuditStepFixture';

    private const THEME = 'AuditStepTheme';

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = base_path('storage/framework/testing/step-audit-'.uniqid('', true));
        $this->writePluginFixture();
        $this->writeThemeFixture();

        $langPath = $this->app->langPath();
        $this->app->setBasePath($this->root);
        $this->app->useLangPath($langPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    public function test_running_plugin_migrations_from_the_cli_is_recorded(): void
    {
        $plugin = $this->registerPlugin();

        $this->assertSame(0, Artisan::call('dls:plugin:migrate', ['plugin' => self::PLUGIN, '--force' => true]));

        $row = AuditLog::where('action', AuditLog::ACTION_PLUGIN_MIGRATED)->sole();
        $this->assertSame(AuditLog::CATEGORY_EXTENSION, $row->category);
        $this->assertSame(AuditLog::ACTOR_SOURCE_CLI, $row->actor_source);
        $this->assertNull($row->actor_id);
        $this->assertSame($plugin->getKey(), (int) $row->target_id);
        $this->assertSame(['2026_01_01_000000_create_audit_step_items'], $row->context['migrations']);
        $this->assertSame('audit-step-fixture', $row->context['plugin_slug']);
    }

    public function test_a_migrate_run_with_nothing_to_run_records_nothing(): void
    {
        Artisan::call('dls:plugin:migrate', ['plugin' => self::PLUGIN, '--force' => true]);
        Artisan::call('dls:plugin:migrate', ['plugin' => self::PLUGIN, '--force' => true]);

        $this->assertSame(1, AuditLog::where('action', AuditLog::ACTION_PLUGIN_MIGRATED)->count());
    }

    public function test_rolling_back_plugin_migrations_is_recorded_as_a_warning(): void
    {
        Artisan::call('dls:plugin:migrate', ['plugin' => self::PLUGIN, '--force' => true]);

        $this->assertSame(0, Artisan::call('dls:plugin:migrate:rollback', ['plugin' => self::PLUGIN, '--force' => true]));

        $row = AuditLog::where('action', AuditLog::ACTION_PLUGIN_MIGRATIONS_ROLLED_BACK)->sole();
        $this->assertSame(AuditLog::SEVERITY_WARNING, $row->severity);
        $this->assertSame(['2026_01_01_000000_create_audit_step_items'], $row->context['migrations']);
    }

    public function test_running_a_plugin_seeder_from_the_cli_is_recorded(): void
    {
        $this->assertSame(0, Artisan::call('dls:plugin:seed', ['plugin' => self::PLUGIN, '--force' => true]));

        $row = AuditLog::where('action', AuditLog::ACTION_PLUGIN_SEEDED)->sole();
        $this->assertSame(AuditLog::ACTOR_SOURCE_CLI, $row->actor_source);
        $this->assertSame('DatabaseSeeder', $row->context['seeder']);
    }

    public function test_a_seeder_run_by_an_operation_that_records_itself_adds_no_row(): void
    {
        ExtensionAuditScope::run(fn () => Artisan::call('dls:plugin:seed', ['plugin' => self::PLUGIN, '--force' => true]));
        ExtensionAuditScope::run(fn () => Artisan::call('dls:plugin:migrate', ['plugin' => self::PLUGIN, '--force' => true]));

        $this->assertSame(0, AuditLog::where('action', AuditLog::ACTION_PLUGIN_SEEDED)->count());
        $this->assertSame(0, AuditLog::where('action', AuditLog::ACTION_PLUGIN_MIGRATED)->count());
        $this->assertFalse(ExtensionAuditScope::active());
    }

    public function test_installing_a_plugin_records_the_install_and_not_its_seeder(): void
    {
        // The Tailwind source aggregator writes a tracked CSS file, and the
        // scan behind the install row's health is not under test.
        $aggregator = Mockery::mock(PluginSourceAggregator::class);
        $aggregator->shouldReceive('regenerate')->andReturn([]);
        $this->app->instance(PluginSourceAggregator::class, $aggregator);
        $rescan = Mockery::mock(ExtensionRescanService::class);
        $rescan->shouldReceive('rescanPlugin')->andReturn([]);
        $this->app->instance(ExtensionRescanService::class, $rescan);
        app(SecuritySettingRepository::class)->set('extension_security_preset', 'development');
        File::ensureDirectoryExists($this->root.'/public');

        $this->artisan('dls:plugin:install', ['pluginName' => self::PLUGIN, '--skip-build' => true])
            ->assertExitCode(0);

        $this->assertSame(1, AuditLog::where('action', AuditLog::ACTION_PLUGIN_INSTALLED)->count());
        $this->assertSame(0, AuditLog::where('action', AuditLog::ACTION_PLUGIN_SEEDED)->count());
    }

    public function test_running_theme_migrations_and_seeder_from_the_cli_is_recorded(): void
    {
        $this->assertSame(0, Artisan::call('dls:theme:migrate', ['theme' => self::THEME, '--force' => true]));
        $this->assertSame(0, Artisan::call('dls:theme:seed', ['theme' => self::THEME, '--force' => true]));

        $migrated = AuditLog::where('action', AuditLog::ACTION_THEME_MIGRATED)->sole();
        $this->assertSame(['2026_01_01_000000_create_audit_step_theme_items'], $migrated->context['migrations']);
        $this->assertSame(self::THEME, $migrated->context['directory']);
        $this->assertSame(1, AuditLog::where('action', AuditLog::ACTION_THEME_SEEDED)->count());
    }

    private function registerPlugin(): Plugin
    {
        return Plugin::create([
            'name' => self::PLUGIN,
            'directory' => self::PLUGIN,
            'slug' => 'audit-step-fixture',
            'namespace' => 'Plugins\\'.self::PLUGIN.'\\',
            'version' => '1.0.0',
            'installed_at' => now(),
        ]);
    }

    private function writePluginFixture(): void
    {
        $dir = $this->root.'/plugins/'.self::PLUGIN;
        File::ensureDirectoryExists($dir.'/database/migrations');
        File::ensureDirectoryExists($dir.'/database/seeders');
        File::put($dir.'/plugin.json', json_encode(['name' => self::PLUGIN, 'slug' => 'audit-step-fixture', 'version' => '1.0.0', 'license' => 'MIT']));
        File::put($dir.'/database/migrations/2026_01_01_000000_create_audit_step_items.php', $this->migrationSource('dls_plg_audit_step_fixture_items'));
        File::put($dir.'/database/seeders/DatabaseSeeder.php', $this->seederSource('Plugins\\'.self::PLUGIN.'\\Database\\Seeders'));

        if (! class_exists('Plugins\\'.self::PLUGIN.'\\Database\\Seeders\\DatabaseSeeder', false)) {
            require $dir.'/database/seeders/DatabaseSeeder.php';
        }
    }

    private function writeThemeFixture(): void
    {
        $dir = $this->root.'/themes/'.self::THEME;
        File::ensureDirectoryExists($dir.'/database/migrations');
        File::ensureDirectoryExists($dir.'/database/seeders');
        File::put($dir.'/theme.json', json_encode(['name' => self::THEME, 'slug' => 'audit-step-theme', 'version' => '1.0.0']));
        File::put($dir.'/database/migrations/2026_01_01_000000_create_audit_step_theme_items.php', $this->migrationSource('dls_thm_audit_step_theme_items'));
        File::put($dir.'/database/seeders/DatabaseSeeder.php', $this->seederSource('Themes\\'.self::THEME.'\\Database\\Seeders'));

        if (! class_exists('Themes\\'.self::THEME.'\\Database\\Seeders\\DatabaseSeeder', false)) {
            require $dir.'/database/seeders/DatabaseSeeder.php';
        }
    }

    private function migrationSource(string $table): string
    {
        return <<<PHP
            <?php

            use Illuminate\\Database\\Migrations\\Migration;
            use Illuminate\\Database\\Schema\\Blueprint;
            use Illuminate\\Support\\Facades\\Schema;

            return new class extends Migration
            {
                public function up(): void
                {
                    Schema::create('{$table}', function (Blueprint \$table) {
                        \$table->id();
                    });
                }

                public function down(): void
                {
                    Schema::dropIfExists('{$table}');
                }
            };
            PHP;
    }

    private function seederSource(string $namespace): string
    {
        return <<<PHP
            <?php

            namespace {$namespace};

            use Illuminate\\Database\\Seeder;

            class DatabaseSeeder extends Seeder
            {
                public function run(): void
                {
                }
            }
            PHP;
    }
}
