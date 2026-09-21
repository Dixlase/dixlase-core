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
 * Source-level guard for where the three preflight stages sit inside
 * CoreUpdater::update(), in the style of CoreMaintenanceBracketRegressionTest.
 * The value of a preflight is entirely in its position: each stage must run
 * before the first step it protects, or it just reports a failure that has
 * already happened.
 *
 * Pure file reads: no framework boot, no database.
 */
class CorePreflightOrderRegressionTest extends TestCase
{
    private string $source;

    protected function setUp(): void
    {
        parent::setUp();

        $this->source = (string) file_get_contents(__DIR__.'/../../../../app/Services/Core/CoreUpdater.php');
    }

    public function test_environment_preflight_runs_before_the_snapshot(): void
    {
        $this->assertBefore('CorePreflightChecker::class)->run()', '$this->snapshotter->capture()');
    }

    public function test_a_failed_environment_preflight_is_recorded_and_clears_the_in_progress_flag(): void
    {
        // Stage 1 runs outside the try/finally, so it must do the bookkeeping
        // the finally would otherwise do.
        $start = strpos($this->source, 'if ($preflight->failed())');
        $this->assertNotFalse($start);
        $block = substr($this->source, $start, 600);

        $this->assertStringContainsString("'update_failed_at' => now()", $block);
        $this->assertStringContainsString('@unlink(self::inProgressFlagPath())', $block);
        $this->assertStringContainsString('throw new RuntimeException', $block);
    }

    public function test_archive_space_is_checked_after_download_and_before_extraction(): void
    {
        $this->assertBefore('$this->sourceManager->downloadCore($version)', '->checkDownloadedArchive($zipPath, $stagingPath)');
        $this->assertBefore('->checkDownloadedArchive($zipPath, $stagingPath)', '$this->extractToStaging($zipPath, $stagingPath)');
    }

    public function test_release_requirements_are_checked_before_backup_and_maintenance(): void
    {
        $this->assertBefore('$this->validateStagedPayload($stagingPath)', '->checkReleaseRequirements($payloadRoot)');
        $this->assertBefore('->checkReleaseRequirements($payloadRoot)', '$this->backupService->backup(');
        $this->assertBefore('->checkReleaseRequirements($payloadRoot)', "Artisan::call('down',");
    }

    private function assertBefore(string $first, string $second): void
    {
        $a = strpos($this->source, $first);
        $b = strpos($this->source, $second);

        $this->assertNotFalse($a, "CoreUpdater must contain `{$first}`");
        $this->assertNotFalse($b, "CoreUpdater must contain `{$second}`");
        $this->assertLessThan($b, $a, "`{$first}` must come before `{$second}`");
    }
}
