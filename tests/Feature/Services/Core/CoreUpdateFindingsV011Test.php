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

namespace Tests\Feature\Services\Core;

use App\Services\Core\CoreMaintenanceGuard;
use App\Services\FileIntegrityService;
use Tests\TestCase;

/**
 * Three findings from the v0.1.0 → v0.1.1 one-liner update.
 *
 * 1. The operator's bypass cookie let an admin request through between the
 *    vendor/ swap and the extension autoload re-sync, which 500ed on a
 *    plugin class. The bypass is now suspended for that window.
 * 2. The plugin Tailwind sources file, which core rewrites on every plugin
 *    change, was inside the integrity scope, so every site with a plugin
 *    looked modified and the update-time baseline refresh never ran.
 * 3. Update and rollback overwrote that file with the release placeholder
 *    and never rebuilt it, so the next theme build lost plugin classes.
 */
class CoreUpdateFindingsV011Test extends TestCase
{
    private const GENERATED = 'resources/src/common/css/dixlase-tailwind-plugin-sources.css';

    protected function tearDown(): void
    {
        if (app()->maintenanceMode()->active()) {
            app()->maintenanceMode()->deactivate();
        }

        parent::tearDown();
    }

    public function test_the_bypass_is_suspended_and_restored_without_lifting_maintenance(): void
    {
        app()->maintenanceMode()->activate(['secret' => 'operator-secret', 'retry' => 60]);
        $guard = app(CoreMaintenanceGuard::class);

        $saved = $guard->suspendOperatorBypass();

        $this->assertTrue(app()->maintenanceMode()->active());
        $this->assertSame('operator-secret', $saved['secret']);
        $this->assertNotSame('operator-secret', app()->maintenanceMode()->data()['secret']);
        $this->assertSame(60, app()->maintenanceMode()->data()['retry']);

        $guard->restoreOperatorBypass($saved);

        $this->assertSame('operator-secret', app()->maintenanceMode()->data()['secret']);
    }

    public function test_suspending_is_a_no_op_outside_maintenance(): void
    {
        $guard = app(CoreMaintenanceGuard::class);

        $this->assertNull($guard->suspendOperatorBypass());
        $guard->restoreOperatorBypass(null);
        $this->assertFalse(app()->maintenanceMode()->active());
    }

    public function test_update_and_rollback_suspend_the_bypass_around_the_vendor_swap(): void
    {
        $sources = [
            'CoreUpdater' => [(string) file_get_contents(app_path('Services/Core/CoreUpdater.php')), '->swap($payloadRoot)'],
            'CoreRollback' => [(string) file_get_contents(app_path('Console/Commands/CoreRollback.php')), '->refetchAndSwap('],
        ];

        foreach ($sources as $name => [$source, $swap]) {
            $suspend = strpos($source, 'suspendOperatorBypass()');
            $swapAt = strpos($source, $swap);
            $manifest = strpos($source, 'rebuildPackageManifest()');
            $restore = strpos($source, 'restoreOperatorBypass(');

            $this->assertNotFalse($suspend, "{$name} must suspend the bypass.");
            $this->assertLessThan($swapAt, $suspend, "{$name}: suspend before the vendor swap.");
            $this->assertGreaterThan($manifest, $restore, "{$name}: restore after the package manifest rebuild.");
        }
    }

    public function test_the_generated_file_is_outside_the_integrity_scope(): void
    {
        $integrity = app(FileIntegrityService::class);

        $this->assertContains(self::GENERATED, FileIntegrityService::GENERATED_PATHS);
        $this->assertSame(['app/A.php' => 'a'], $integrity->withoutGeneratedFiles(['app/A.php' => 'a', self::GENERATED => 'x']));

        $compare = new \ReflectionMethod($integrity, 'compareStates');
        $result = $compare->invoke($integrity, ['app/A.php' => 'a', self::GENERATED => 'placeholder'], ['app/A.php' => 'a', self::GENERATED => 'with-pages']);

        $this->assertSame([], $result['changed']);
        $this->assertSame([], $result['added']);
        $this->assertSame([], $result['removed']);
    }

    public function test_update_rollback_and_theme_build_rebuild_the_plugin_sources(): void
    {
        foreach ([
            'CoreUpdater' => app_path('Services/Core/CoreUpdater.php'),
            'CoreRollback' => app_path('Console/Commands/CoreRollback.php'),
        ] as $name => $path) {
            $this->assertStringContainsString('dls:tailwind:regenerate-plugin-sources', (string) file_get_contents($path), "{$name} must rebuild the plugin Tailwind sources.");
        }

        $build = (string) file_get_contents(app_path('Console/Commands/ThemeBuild.php'));
        $this->assertStringContainsString('PluginSourceAggregator::class)->regenerate()', $build, 'dls:theme:build must rebuild the sources before building.');
    }
}
