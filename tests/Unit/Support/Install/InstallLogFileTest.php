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

namespace Tests\Unit\Support\Install;

use App\Services\Install\InstallFinalizer;
use App\Support\Install\InstallLogFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The installer log's lifetime: it ends when the install does.
 *
 * Three callers end it, and each is pinned here. The class itself is
 * exercised against a throwaway file; the two services are pinned by
 * reading their source, because calling them for real would mutate
 * `.env` (InstallFinalizer::finalize) or apply a release over the
 * working tree (CoreUpdater::update).
 *
 * @see \App\Support\Install\InstallLogFile
 */
class InstallLogFileTest extends TestCase
{
    private string $logPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Never the real storage/logs: a test that removes files must not
        // be able to reach the logs of the checkout it runs in.
        $dir = storage_path('framework/testing/install-log-'.uniqid());
        @mkdir($dir, 0775, true);
        $this->logPath = $dir.'/install.log';

        config(['logging.channels.install.path' => $this->logPath]);
    }

    protected function tearDown(): void
    {
        if (is_file($this->logPath)) {
            @unlink($this->logPath);
        }
        @rmdir(dirname($this->logPath));

        parent::tearDown();
    }

    #[Test]
    public function it_resolves_the_path_from_the_install_channel(): void
    {
        $this->assertSame($this->logPath, InstallLogFile::path());
    }

    #[Test]
    public function it_has_no_path_when_the_channel_does_not_write_to_a_file(): void
    {
        config(['logging.channels.install.path' => null]);
        $this->assertNull(InstallLogFile::path());

        config(['logging.channels.install.path' => '']);
        $this->assertNull(InstallLogFile::path());

        // A path-less channel has nothing to remove, and must not throw.
        $this->assertFalse(InstallLogFile::discard());
    }

    #[Test]
    public function it_removes_the_log_and_says_it_did(): void
    {
        file_put_contents($this->logPath, "wizard said something\n");

        $this->assertTrue(InstallLogFile::discard());
        $this->assertFileDoesNotExist($this->logPath);
    }

    #[Test]
    public function it_is_idempotent_and_reports_nothing_removed(): void
    {
        $this->assertFileDoesNotExist($this->logPath);
        $this->assertFalse(InstallLogFile::discard());
        $this->assertFalse(InstallLogFile::discard());
    }

    #[Test]
    public function the_basename_is_the_file_the_install_channel_writes(): void
    {
        // The backup target matches on the basename, because it projects
        // storage/logs as a directory instead of resolving the channel.
        // If the channel is ever pointed elsewhere, that match goes stale
        // silently — so pin the two together.
        $configured = config('logging.channels.install.path');

        config(['logging.channels.install.path' => null]);
        $shipped = require base_path('config/logging.php');

        $this->assertSame(
            InstallLogFile::BASENAME,
            basename($shipped['channels']['install']['path']),
            'InstallLogFile::BASENAME must name the file config/logging.php writes the install channel to.'
        );

        config(['logging.channels.install.path' => $configured]);
    }

    #[Test]
    public function the_finalizer_discards_the_log_after_the_response(): void
    {
        $source = file_get_contents(app_path('Services/Install/InstallFinalizer.php'));

        $this->assertStringContainsString(
            'app()->terminating(',
            $source,
            'The discard must be deferred: finalize()\'s callers keep writing to the install channel afterwards, which would recreate the file.'
        );
        $this->assertStringContainsString('InstallLogFile::discard()', $source);

        // ...and it is reached from finalize(), not merely defined.
        $this->assertMatchesRegularExpression(
            '/public function finalize\(\).*?\$this->scheduleInstallLogDiscard\(\);/s',
            $source,
            'finalize() must schedule the discard.'
        );
    }

    #[Test]
    public function the_finalizers_deferred_discard_really_removes_the_file(): void
    {
        file_put_contents($this->logPath, "wizard said something\n");

        $schedule = new \ReflectionMethod(InstallFinalizer::class, 'scheduleInstallLogDiscard');
        $schedule->invoke(app(InstallFinalizer::class));

        // Still there: the request is not over, and finalize()'s callers
        // are still writing to the install channel.
        $this->assertFileExists($this->logPath);

        $this->app->terminate();

        $this->assertFileDoesNotExist($this->logPath);
    }

    #[Test]
    public function the_core_update_discards_a_leftover_log(): void
    {
        $source = file_get_contents(app_path('Services/Core/CoreUpdater.php'));

        $this->assertStringContainsString('InstallLogFile::discard()', $source);

        $discardAt = strpos($source, 'InstallLogFile::discard()');
        $applyAt = strpos($source, "\$log('Applying source over live tree...');");
        $migrateAt = strpos($source, "\$log('Running migrations...');");

        $this->assertNotFalse($applyAt);
        $this->assertNotFalse($migrateAt);

        // After the release is on disk, before the migrate step: the
        // file is a leftover of the site, not of the payload.
        $this->assertGreaterThan($applyAt, $discardAt);
        $this->assertLessThan($migrateAt, $discardAt);
    }

    #[Test]
    public function the_logs_backup_target_leaves_the_install_log_out(): void
    {
        $source = file_get_contents(app_path('Services/Backup/CoreBackupService.php'));

        $this->assertStringContainsString(
            'private const LOGS_EXCLUDE_FILES = [',
            $source,
            'The logs target needs a stated exclusion list.'
        );
        $this->assertStringContainsString('InstallLogFile::BASENAME', $source);
        $this->assertMatchesRegularExpression(
            '/TARGET_LOGS => \$this->addDirectoryToZip\(.*?excludeFiles: self::LOGS_EXCLUDE_FILES,/s',
            $source,
            'The logs target must pass the exclusion to addDirectoryToZip().'
        );
    }
}
