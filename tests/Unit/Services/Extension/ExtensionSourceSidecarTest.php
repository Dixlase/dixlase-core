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

namespace Tests\Unit\Services\Extension;

use App\Services\Extension\ExtensionSourceSidecar;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The sidecar is how the download step tells the install step which
 * extension source served a plugin / theme. It lives in a throwaway
 * directory here — never under the real plugins/ or themes/.
 */
class ExtensionSourceSidecarTest extends TestCase
{
    private string $dir;

    private ExtensionSourceSidecar $sidecar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = base_path('storage/framework/testing/sidecar-'.uniqid());
        File::ensureDirectoryExists($this->dir);
        $this->sidecar = new ExtensionSourceSidecar();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->dir)) {
            File::deleteDirectory($this->dir);
        }
        parent::tearDown();
    }

    public function test_write_then_read_round_trips_the_linkage_and_keeps_the_file(): void
    {
        $linkage = [
            'source_id' => 7,
            'source_repo' => 'plugin-dixlase-cookie',
            'installation_method' => 'github',
            'installed_from_url' => 'https://github.com/Dixlase/plugin-dixlase-cookie',
        ];

        $this->sidecar->write($this->dir, $linkage);

        $this->assertFileExists($this->dir.'/'.ExtensionSourceSidecar::FILENAME);
        $this->assertSame($linkage, $this->sidecar->read($this->dir));
        // read() is non-destructive so the install step can re-read on retry.
        $this->assertFileExists($this->dir.'/'.ExtensionSourceSidecar::FILENAME);
    }

    public function test_consume_returns_the_linkage_and_removes_the_file(): void
    {
        $this->sidecar->write($this->dir, ['source_id' => 3, 'source_repo' => null, 'installation_method' => 'github', 'installed_from_url' => null]);

        $data = $this->sidecar->consume($this->dir);

        $this->assertSame(3, $data['source_id']);
        $this->assertFileDoesNotExist($this->dir.'/'.ExtensionSourceSidecar::FILENAME);
        $this->assertNull($this->sidecar->read($this->dir));
    }

    public function test_read_returns_null_when_there_is_no_sidecar(): void
    {
        $this->assertNull($this->sidecar->read($this->dir));
        $this->assertNull($this->sidecar->consume($this->dir));
    }

    public function test_read_returns_null_for_a_sidecar_without_source_id(): void
    {
        file_put_contents($this->dir.'/'.ExtensionSourceSidecar::FILENAME, json_encode(['installation_method' => 'github']));

        $this->assertNull($this->sidecar->read($this->dir));
    }

    public function test_read_returns_null_for_malformed_json(): void
    {
        file_put_contents($this->dir.'/'.ExtensionSourceSidecar::FILENAME, '{not json');

        $this->assertNull($this->sidecar->read($this->dir));
    }

    public function test_delete_is_a_noop_without_a_sidecar(): void
    {
        $this->sidecar->delete($this->dir);

        $this->assertDirectoryExists($this->dir);
    }

    public function test_accepts_a_trailing_slash_on_the_directory(): void
    {
        $this->sidecar->write($this->dir.'/', ['source_id' => 1, 'source_repo' => null, 'installation_method' => 'github', 'installed_from_url' => null]);

        $this->assertFileExists($this->dir.'/'.ExtensionSourceSidecar::FILENAME);
        $this->assertSame(1, $this->sidecar->read($this->dir)['source_id']);
    }
}
