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

namespace Tests\Unit\Console\Traits;

use App\Console\Traits\AutoScansExtensionAfterUpdate;
use App\DTO\Plugin\HealthScoreResult;
use App\Enums\PluginEnableAction;
use App\Enums\PluginHealthStatus;
use App\Exceptions\ExtensionUpdateBlockedException;
use App\Services\Extension\ExtensionRescanService;
use App\Services\Plugin\PluginHealthScorer;
use App\Services\Theme\ThemeHealthScorer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

/**
 * An update scans the new version before applying it (security review X3).
 *
 * Regression: dls:plugin:update / dls:theme:update replaced the files, ran
 * migrations, seeders and the npm build, and only then scanned -- and a
 * Blocked result changed nothing. The per-item admin update routes, which
 * bypassed the command entirely, are gone (X1 / X4).
 */
class ExtensionUpdateGateTest extends TestCase
{
    public function test_a_blocked_plugin_version_is_refused(): void
    {
        $this->rescanExpected('rescanPlugin', 'demo');
        $this->scorer(PluginHealthScorer::class, PluginEnableAction::Blocked);

        $this->expectException(ExtensionUpdateBlockedException::class);
        $this->makeHost()->gate('plugin', 'demo');
    }

    public function test_a_blocked_theme_version_is_refused(): void
    {
        $this->rescanExpected('rescanTheme', 'demo-theme');
        $this->scorer(ThemeHealthScorer::class, PluginEnableAction::Blocked);

        $this->expectException(ExtensionUpdateBlockedException::class);
        $this->makeHost()->gate('theme', 'demo-theme');
    }

    public function test_a_version_that_cannot_be_scanned_is_refused(): void
    {
        $rescan = Mockery::mock(ExtensionRescanService::class);
        $rescan->shouldReceive('rescanPlugin')->andThrow(new \RuntimeException('audit crashed'));
        $this->app->instance(ExtensionRescanService::class, $rescan);

        $this->expectException(ExtensionUpdateBlockedException::class);
        $this->expectExceptionMessage('audit crashed');
        $this->makeHost()->gate('plugin', 'demo');
    }

    public function test_an_allowed_version_passes(): void
    {
        $this->rescanExpected('rescanPlugin', 'demo');
        $this->scorer(PluginHealthScorer::class, PluginEnableAction::WarningRequired);

        $this->makeHost()->gate('plugin', 'demo');

        $this->addToAssertionCount(1);
    }

    public function test_the_gate_runs_after_the_swap_and_before_migrations(): void
    {
        foreach (['PluginUpdate' => 'plugin', 'ThemeUpdate' => 'theme'] as $command => $kind) {
            $source = File::get(app_path("Console/Commands/{$command}.php"));

            $extract = strpos($source, '$this->extractUpdate(');
            $gate = strpos($source, "\$this->refuseBlockedUpdate('{$kind}', \$slug)");
            $migrate = strpos($source, '$migrator->migrate(');

            $this->assertNotFalse($gate, "{$command} must call refuseBlockedUpdate().");
            $this->assertGreaterThan($extract, $gate, "{$command}: scan the new files, so after extractUpdate().");
            $this->assertLessThan($migrate, $gate, "{$command}: refuse before any migration runs.");
        }
    }

    public function test_the_per_item_admin_update_routes_are_gone(): void
    {
        foreach ([
            'admin.settings.plugins.update',
            'admin.settings.plugins.update-all',
            'admin.settings.themes.update',
            'admin.settings.themes.update-all',
        ] as $name) {
            $this->assertFalse(Route::has($name), "{$name} bypassed dls:*:update and must not come back.");
        }
    }

    private function rescanExpected(string $method, string $slug): void
    {
        $rescan = Mockery::mock(ExtensionRescanService::class);
        $rescan->shouldReceive($method)->with($slug)->once()->andReturn([]);
        $this->app->instance(ExtensionRescanService::class, $rescan);
    }

    /**
     * @param  class-string  $class
     */
    private function scorer(string $class, PluginEnableAction $action): void
    {
        $scorer = Mockery::mock($class);
        $scorer->shouldReceive('calculate')->andReturn(new HealthScoreResult(90, PluginHealthStatus::Healthy));
        $scorer->shouldReceive('determineEnableAction')->andReturn($action);
        $this->app->instance($class, $scorer);
    }

    private function makeHost(): object
    {
        return new class
        {
            use AutoScansExtensionAfterUpdate;

            public function gate(string $kind, string $slug): void
            {
                $this->refuseBlockedUpdate($kind, $slug);
            }

            public function info(string $message): void {}

            public function warn(string $message): void {}

            public function line(string $message): void {}
        };
    }
}
