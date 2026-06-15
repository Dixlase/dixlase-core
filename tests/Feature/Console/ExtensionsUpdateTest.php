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

use App\Console\Commands\ExtensionsUpdate;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The admin updates page spawns dls:extensions:update detached and shows
 * a polling placeholder gated on the in-progress flag. The flag must be
 * cleared when the command finishes — otherwise the UI is stuck on the
 * placeholder forever. These tests pin that the command always clears
 * the flag in its finally block, including the no-op empty batch.
 */
class ExtensionsUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        File::ensureDirectoryExists(dirname(ExtensionsUpdate::inProgressFlagPath()));
        File::put(ExtensionsUpdate::inProgressFlagPath(), json_encode(['started_at' => 0]));
    }

    protected function tearDown(): void
    {
        File::delete(ExtensionsUpdate::inProgressFlagPath());
        parent::tearDown();
    }

    public function test_empty_batch_clears_the_in_progress_flag_and_succeeds(): void
    {
        $this->assertFileExists(ExtensionsUpdate::inProgressFlagPath());

        $this->artisan('dls:extensions:update')->assertExitCode(0);

        $this->assertFileDoesNotExist(
            ExtensionsUpdate::inProgressFlagPath(),
            'the command must clear the in-progress flag so the polling UI is released',
        );
    }

    public function test_flag_path_lives_under_private_storage(): void
    {
        // Must be under storage/app/private so an extension update (which
        // only rewrites plugins/ and themes/) never clobbers the flag.
        $this->assertStringContainsString(
            'storage/app/private/extension-update',
            ExtensionsUpdate::inProgressFlagPath(),
        );
    }
}
