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

namespace Tests\Unit\Services\Core;

use PHPUnit\Framework\TestCase;

/**
 * Source-level guard for the maintenance-window ownership bracket, in the
 * same style as CoreMaintenanceBracketRegressionTest: the updater and the
 * rollback command must (1) record themselves as the window's owner
 * BEFORE `artisan down`, (2) hand `down` the secret that record carries,
 * and (3) drop the record only AFTER a successful `artisan up`. Getting
 * any of those out of order would either leave a window the self-heal
 * refuses to lift (no owner) or lift one that is still in use.
 *
 * Pure file reads: no framework boot, no database.
 */
class CoreMaintenanceOwnerRegressionTest extends TestCase
{
    private const ROOT = __DIR__.'/../../../..';

    public function test_core_updater_claims_before_down_and_releases_after_up(): void
    {
        $this->assertOwnershipBracket(self::ROOT.'/app/Services/Core/CoreUpdater.php', 'CoreUpdater');
    }

    public function test_core_rollback_claims_before_down_and_releases_after_up(): void
    {
        $this->assertOwnershipBracket(self::ROOT.'/app/Console/Commands/CoreRollback.php', 'CoreRollback');
    }

    public function test_self_heal_is_scheduled_every_minute(): void
    {
        $source = (string) file_get_contents(self::ROOT.'/routes/console.php');

        $pos = strpos($source, "Schedule::command('dls:core:heal-maintenance')");
        $this->assertNotFalse($pos, 'dls:core:heal-maintenance must be scheduled in routes/console.php');
        $this->assertStringContainsString('->everyMinute()', substr($source, $pos, 200));
    }

    public function test_self_heal_runs_on_console_boot_but_not_for_the_owning_commands(): void
    {
        $source = (string) file_get_contents(self::ROOT.'/app/Providers/AppServiceProvider.php');

        $this->assertStringContainsString('->healIfOrphaned()', $source);
        $this->assertStringContainsString('runningInConsole()', $source);
        foreach (['down', 'up', 'dls:core:update', 'dls:core:rollback', 'dls:core:heal-maintenance'] as $command) {
            $this->assertStringContainsString("'{$command}'", $source, "boot-time self-heal must skip `{$command}`");
        }
    }

    private function assertOwnershipBracket(string $file, string $label): void
    {
        $source = (string) file_get_contents($file);

        $claim = strpos($source, '->claim(CoreMaintenanceGuard::OPERATION_');
        $down = strpos($source, "Artisan::call('down',");
        $this->assertNotFalse($claim, "{$label} must claim the maintenance window");
        $this->assertNotFalse($down, "{$label} must call artisan down");
        $this->assertLessThan($down, $claim, "{$label} must claim ownership BEFORE artisan down");

        $downCall = substr($source, $down, 160);
        $this->assertStringContainsString("'--secret' => \$maintenanceSecret", $downCall, "{$label} must hand `down` the owner secret");

        $up = strpos($source, "Artisan::call('up')");
        $release = strpos($source, '->release()');
        $this->assertNotFalse($up, "{$label} must call artisan up");
        $this->assertNotFalse($release, "{$label} must release the owner record");
        $this->assertGreaterThan($up, $release, "{$label} must release ownership only AFTER artisan up");
    }
}
