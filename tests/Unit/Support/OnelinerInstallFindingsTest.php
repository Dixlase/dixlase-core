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

namespace Tests\Unit\Support;

use App\Http\Middleware\CheckInstallationReady;
use App\Services\Core\CoreManifestBuilder;
use App\Support\CoreVersion;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Findings from the one-liner install of v0.3.49
 * (Src/Tasks/task-core-oneliner-v0.3.49-findings.md).
 */
class OnelinerInstallFindingsTest extends TestCase
{
    use RefreshDatabase;

    private string $tmp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmp = storage_path('framework/testing/core-version-'.uniqid());
        File::ensureDirectoryExists($this->tmp);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tmp);
        parent::tearDown();
    }

    public function test_core_version_reads_the_version_file_of_the_given_tree(): void
    {
        File::put($this->tmp.'/VERSION', "9.8.7\n");

        $this->assertSame('9.8.7', CoreVersion::current($this->tmp));
    }

    public function test_a_tree_without_a_version_file_is_unknown_rather_than_a_made_up_version(): void
    {
        $this->assertSame(CoreVersion::UNKNOWN, CoreVersion::current($this->tmp));
    }

    public function test_the_live_core_version_matches_the_version_file(): void
    {
        $this->assertSame(trim((string) file_get_contents(base_path('VERSION'))), CoreVersion::current());
    }

    public function test_the_core_manifest_records_the_tree_version(): void
    {
        File::put($this->tmp.'/VERSION', "9.8.7\n");

        $this->assertSame('9.8.7', app(CoreManifestBuilder::class)->build($this->tmp)['version']);
    }

    public function test_nothing_in_app_reads_the_undefined_app_version_config_without_a_real_source_first(): void
    {
        $offenders = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path(), \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            foreach (file($file->getPathname()) as $n => $line) {
                // Allowed only as the last fallback after CoreVersionHistory / the VERSION file.
                if (str_contains($line, "config('app.version'")
                    && ! str_contains($line, 'currentVersion()')
                    && ! str_contains($line, 'readVersionFromDisk()')
                    && ! str_starts_with(ltrim($line), '??') // continuation of such a chain
                    && ! str_starts_with(ltrim($line), '//')
                    && ! str_starts_with(ltrim($line), '*')) {
                    $offenders[] = $file->getPathname().':'.($n + 1);
                }
            }
        }

        $this->assertSame([], $offenders, "config('app.version') is not defined; use App\\Support\\CoreVersion.");
    }

    public function test_the_queue_tables_exist_after_migrating(): void
    {
        $this->assertTrue(Schema::hasTable('jobs'));
        $this->assertTrue(Schema::hasTable('job_batches'));
        $this->assertTrue(Schema::hasTable('failed_jobs'));
    }

    public function test_the_default_queue_connection_needs_no_worker(): void
    {
        $config = require config_path('queue.php');

        // Without QUEUE_CONNECTION in the environment the default is sync.
        $this->assertStringContainsString("env('QUEUE_CONNECTION', 'sync')", (string) file_get_contents(config_path('queue.php')));
        $this->assertArrayHasKey('sync', $config['connections']);
        $this->assertMatchesRegularExpression('/^QUEUE_CONNECTION=sync$/m', (string) file_get_contents(base_path('.env.example')));
    }

    public function test_the_scheduler_drains_the_database_queue_only_when_it_is_used(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($e) => $e->description === 'dixlase-queue-drain');

        $this->assertNotNull($event, 'The queue drain must be scheduled.');

        config(['queue.default' => 'sync']);
        $this->assertFalse($event->filtersPass($this->app));

        config(['queue.default' => 'database']);
        $this->assertTrue($event->filtersPass($this->app));
    }

    public function test_install_log_recognises_an_unreachable_database(): void
    {
        $method = new ReflectionMethod(CheckInstallationReady::class, 'isConnectionFailure');
        $middleware = $this->app->make(CheckInstallationReady::class);

        $this->assertTrue($method->invoke($middleware, new \PDOException('SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo for mysql failed')));
        $this->assertTrue($method->invoke($middleware, new \PDOException('SQLSTATE[HY000] [2002] Connection refused')));
        $this->assertFalse($method->invoke($middleware, new \PDOException("SQLSTATE[42S02]: Base table or view not found: 1146 Table 'x.dls_members' doesn't exist")));
    }

    public function test_plugin_and_theme_rows_get_created_at_on_insert(): void
    {
        // The query builder's updateOrInsert() writes no timestamps; the
        // plugins row was left with created_at NULL.
        foreach (['Console/Commands/PluginInstall.php', 'Console/Commands/ThemeSwitch.php'] as $file) {
            $source = (string) file_get_contents(app_path($file));
            $this->assertMatchesRegularExpression(
                '/updateOrInsert\(\s*\[[^\]]*\],\s*(?:\/\/[^\n]*\n\s*)?fn \(bool \$exists\) => \(\$exists \? \[\] : \[\'created_at\' => now\(\)\]\)/s',
                $source,
                $file.' must set created_at when it inserts.'
            );
        }
    }
}
