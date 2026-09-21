<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Services\Core\CorePreflightChecker;
use App\Services\Core\CoreSourceSnapshot;
use App\Services\Core\CoreUpdater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * `dls:core:update --dry-run` runs the real environment preflight and
 * reports it, without ever calling the updater. The checker is the real
 * class, pointed at a throwaway tree with its host probes injected, so the
 * table and the verdict are exactly what an operator would see.
 */
class CoreUpdatePreflightDryRunTest extends TestCase
{
    use RefreshDatabase;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = storage_path('framework/testing/preflight-cli-'.uniqid());
        foreach (CoreSourceSnapshot::SOURCE_DIRECTORIES as $dir) {
            File::ensureDirectoryExists($this->root.'/'.$dir);
        }
        File::put($this->root.'/dixlase.json', json_encode(['requires' => ['php' => '>=8.2']]));

        // The updater must never run during a dry run.
        $this->mock(CoreUpdater::class, fn ($mock) => $mock->shouldNotReceive('update'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);
        parent::tearDown();
    }

    public function test_dry_run_reports_a_passing_preflight_and_changes_nothing(): void
    {
        $this->bindChecker(missingExtensions: []);

        $this->artisan('dls:core:update', ['--to' => '99.0.0', '--dry-run' => true])
            ->expectsOutputToContain('Preflight checks:')
            ->expectsOutputToContain('Preflight passed.')
            ->assertSuccessful();
    }

    public function test_dry_run_exits_non_zero_when_the_update_would_be_refused(): void
    {
        $this->bindChecker(missingExtensions: ['zip']);

        $this->artisan('dls:core:update', ['--to' => '99.0.0', '--dry-run' => true])
            ->expectsOutputToContain('Preflight failed — the update would be refused: php_extensions: missing PHP extensions: zip')
            ->assertFailed();
    }

    /**
     * @param  list<string>  $missingExtensions
     */
    private function bindChecker(array $missingExtensions): void
    {
        $this->app->instance(CorePreflightChecker::class, new CorePreflightChecker(
            basePath: $this->root,
            extensionLoaded: static fn (string $ext): bool => ! in_array($ext, $missingExtensions, true),
            isWritable: static fn (string $path): bool => true,
            freeSpace: static fn (string $path): ?float => 50.0 * 1024 * 1024 * 1024,
            phpVersion: '8.3.12',
            driftDetector: static fn (): array => ['manifest_drifted' => false],
        ));
    }
}
