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

namespace Tests\Feature\Console;

use App\Console\Traits\TakesExtensionBackup;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Pins the pre-update backup / restore / retention behaviour that
 * dls:{theme,plugin}:update and dls:{theme,plugin}:rollback build on.
 *
 * The backup must be a verbatim copy of the live tree INCLUDING the
 * gitignored resources/assets prebuilt output, so a rollback restores the
 * exact assets the site was serving with no npm run; restore must move the
 * current tree aside (recoverable) and preserve the backup; retention must
 * bound the kept set.
 */
class ExtensionBackupTest extends TestCase
{
    private object $subject;

    private string $slug = '__test_ext_backup__';

    private string $liveDir;

    protected function setUp(): void
    {
        parent::setUp();

        // A class exposing the protected trait methods for assertion.
        $this->subject = new class
        {
            use TakesExtensionBackup {
                extensionBackupRoot as public root;
                takeExtensionBackup as public take;
                listExtensionBackups as public listBackups;
                latestExtensionBackupPath as public latest;
                resolveExtensionBackupPath as public resolve;
                pruneExtensionBackups as public prune;
                restoreExtensionBackupInto as public restoreInto;
            }
        };

        $this->cleanup();
        $this->liveDir = storage_path('framework/testing/'.$this->slug.'-live');
        $this->deleteTree($this->liveDir);
        $this->writeFile($this->liveDir.'/theme.json', '{"version":"1.0.0"}');
        $this->writeFile($this->liveDir.'/resources/assets/js/app.js', 'PREBUILT-V1');
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        $this->deleteTree($this->liveDir);
        parent::tearDown();
    }

    public function test_backup_copies_the_whole_tree_including_prebuilt_assets(): void
    {
        $backup = $this->subject->take('theme', $this->slug, $this->liveDir);

        $this->assertDirectoryExists($backup);
        $this->assertFileExists($backup.'/theme.json');
        // The gitignored prebuilt asset must be carried into the backup.
        $this->assertSame('PREBUILT-V1', file_get_contents($backup.'/resources/assets/js/app.js'));
    }

    public function test_list_and_latest_are_chronological(): void
    {
        $b1 = $this->subject->take('theme', $this->slug, $this->liveDir);
        $b2 = $this->subject->take('theme', $this->slug, $this->liveDir);

        $names = $this->subject->listBackups('theme', $this->slug);
        $this->assertSame([basename($b1), basename($b2)], $names);
        $this->assertSame($b2, $this->subject->latest('theme', $this->slug));
    }

    public function test_resolve_selects_specific_timestamp_or_latest(): void
    {
        $b1 = $this->subject->take('theme', $this->slug, $this->liveDir);
        $b2 = $this->subject->take('theme', $this->slug, $this->liveDir);

        $this->assertSame($b1, $this->subject->resolve('theme', $this->slug, basename($b1)));
        $this->assertSame($b2, $this->subject->resolve('theme', $this->slug, null));
        $this->assertNull($this->subject->resolve('theme', $this->slug, 'nonexistent-ts'));
    }

    public function test_prune_keeps_only_the_newest_n(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->subject->take('theme', $this->slug, $this->liveDir);
        }
        $this->assertCount(5, $this->subject->listBackups('theme', $this->slug));

        $this->subject->prune('theme', $this->slug, 3);

        $kept = $this->subject->listBackups('theme', $this->slug);
        $this->assertCount(3, $kept);
        // The three kept must be the newest three.
        $this->assertSame($this->subject->latest('theme', $this->slug), $this->subject->root('theme', $this->slug).'/'.end($kept));
    }

    public function test_restore_moves_current_aside_and_preserves_the_backup(): void
    {
        // Backup at v1, then mutate the live tree to v2.
        $backup = $this->subject->take('theme', $this->slug, $this->liveDir);
        $this->writeFile($this->liveDir.'/resources/assets/js/app.js', 'PREBUILT-V2');
        $this->writeFile($this->liveDir.'/theme.json', '{"version":"2.0.0"}');

        $aside = $this->subject->restoreInto($backup, $this->liveDir);

        // Live tree restored to v1 (byte-identical asset).
        $this->assertSame('PREBUILT-V1', file_get_contents($this->liveDir.'/resources/assets/js/app.js'));
        // The moved-aside copy holds the v2 (pre-rollback) state, recoverable.
        $this->assertSame('PREBUILT-V2', file_get_contents($aside.'/resources/assets/js/app.js'));
        // The backup itself is preserved (copied, not consumed) so --to can be reused.
        $this->assertDirectoryExists($backup);
        $this->assertSame('PREBUILT-V1', file_get_contents($backup.'/resources/assets/js/app.js'));
    }

    private function cleanup(): void
    {
        $this->deleteTree(storage_path('app/private/extension-backups/themes/'.$this->slug));
        $this->deleteTree(storage_path('app/private/extension-backups/plugins/'.$this->slug));
    }

    private function writeFile(string $path, string $contents): void
    {
        File::ensureDirectoryExists(dirname($path));
        file_put_contents($path, $contents);
    }

    private function deleteTree(string $dir): void
    {
        if (is_dir($dir)) {
            File::deleteDirectory($dir);
        }
    }
}
