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

namespace Tests\Unit\Console;

use Tests\TestCase;

/**
 * Remote-sandbox verification found a HIGH-severity bug: the core update,
 * rollback and backup-restore pipelines swap the ENTIRE vendor/ directory
 * with the release's prebuilt vendor. That directory contains
 * vendor/composer/autoload_psr4.php, into which the installer persisted the
 * site-local theme/plugin PSR-4 (Themes\<Dir>\App\, Plugins\<Dir>\App\ — see
 * InstallConfirmController + ComposerLocalHelper::syncAutoload()). The
 * swapped-in copy is the release's pristine autoload and lacks those entries,
 * so after any vendor swap every extension admin/settings page 500s with
 *
 *     ReflectionException: Class "Themes\<Dir>\App\..." does not exist
 *
 * until the local autoload is re-persisted.
 *
 * The fix: each pipeline calls ComposerLocalHelper::syncAutoload() AFTER the
 * vendor swap (it rewrites composer.local.json and runs composer
 * dump-autoload). This test pins that call — and its position after the swap —
 * so a future refactor cannot drop it or move it before the swap.
 *
 * Source-inspection style matches the sibling ordering tests
 * (CoreRollbackOrderingTest, ExtensionRollbackOrderingTest): reproducing a
 * real vendor swap + autoload regeneration in PHPUnit needs full filesystem +
 * Composer orchestration, and the invariant we care about is textual (the call
 * exists and follows the swap).
 */
class VendorSwapAutoloadResyncTest extends TestCase
{
    public function test_core_update_resyncs_local_autoload_after_vendor_swap(): void
    {
        $this->assertResyncFollowsSwap(
            app_path('Services/Core/CoreUpdater.php'),
            '$this->vendorManager->swap($payloadRoot)'
        );
    }

    public function test_core_rollback_resyncs_local_autoload_after_vendor_swap(): void
    {
        $this->assertResyncFollowsSwap(
            app_path('Console/Commands/CoreRollback.php'),
            'refetchAndSwap('
        );
    }

    public function test_backup_restore_resyncs_local_autoload_after_vendor_refetch(): void
    {
        $this->assertResyncFollowsSwap(
            app_path('Console/Commands/Backup/BackupRestoreCommand.php'),
            'refetchAndSwap('
        );
    }

    private function assertResyncFollowsSwap(string $file, string $swapNeedle): void
    {
        $source = (string) file_get_contents($file);
        $rel = str_replace(base_path().'/', '', $file);

        $swapPos = strpos($source, $swapNeedle);
        $resyncPos = strpos($source, 'ComposerLocalHelper::syncAutoload()');

        $this->assertNotFalse(
            $swapPos,
            "{$rel} must swap vendor/ via `{$swapNeedle}`."
        );
        $this->assertNotFalse(
            $resyncPos,
            "{$rel} must call `ComposerLocalHelper::syncAutoload()` after the "
            .'vendor swap. The swap replaces vendor/composer/autoload_psr4.php '
            .'with the release copy, wiping the site-local theme/plugin PSR-4; '
            .'without re-syncing, extension admin/settings pages 500 with a '
            .'ReflectionException.'
        );
        $this->assertGreaterThan(
            $swapPos,
            $resyncPos,
            "In {$rel}, `ComposerLocalHelper::syncAutoload()` MUST run AFTER the "
            .'vendor swap — re-persisting the local autoload only makes sense '
            .'once the release vendor/ (with its pristine autoload_psr4.php) is '
            .'in place. Do not move it before the swap.'
        );
    }
}
