<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace Tests\Feature\Console;

use App\Models\AuditLog;
use App\Services\FileIntegrityService;
use App\Services\Security\IntegrityKeyring;
use App\Services\Security\IntegritySeal;
use App\Support\Install\EnvFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Core #665: `dls:integrity:key` creates and rotates the integrity key in
 * .env. The command is pointed at a throwaway .env, never the real one.
 */
class IntegrityKeyCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = storage_path('framework/testing/integrity-key-'.uniqid());
        File::makeDirectory($this->dir, 0755, true);
        File::put($this->dir.'/.env', "APP_NAME=Dixlase\nINTEGRITY_KEY=\n");
        $this->app->useEnvironmentPath($this->dir);
        // The first key seals the baseline in storage; keep it off the real one.
        File::makeDirectory($this->dir.'/storage', 0755, true);
        $this->app->useStoragePath($this->dir.'/storage');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);

        parent::tearDown();
    }

    public function test_it_creates_the_first_key_and_records_it(): void
    {
        $this->artisan('dls:integrity:key')->assertSuccessful();

        $key = EnvFile::read('INTEGRITY_KEY', $this->envPath());
        $this->assertSame(IntegrityKeyring::FIRST_VERSION, IntegrityKeyring::versionOf($key));
        $this->assertStringContainsString('APP_NAME=Dixlase', (string) File::get($this->envPath()));

        $row = AuditLog::where('action', AuditLog::ACTION_INTEGRITY_KEY_CREATED)->sole();
        $this->assertSame(AuditLog::CATEGORY_SECURITY, $row->category);
        $this->assertSame(2, $row->context['key_version']);
        $this->assertStringNotContainsString(substr($key, 9, 20), json_encode($row->context));
    }

    public function test_the_first_key_seals_the_existing_baseline_unchanged(): void
    {
        $service = new FileIntegrityService();
        $service->saveBaselineArray([
            'meta' => ['app_version' => '0.0.0-test'],
            'files' => ['app/Example.php' => str_repeat('a', 64)],
        ]);
        $this->assertSame(IntegritySeal::UNSEALED, $service->baselineSealStatus());

        $this->artisan('dls:integrity:key')->assertSuccessful();

        $this->assertSame(IntegritySeal::VALID, $service->baselineSealStatus());
        $this->assertTrue(AuditLog::where('action', AuditLog::ACTION_INTEGRITY_KEY_CREATED)->sole()->context['baseline_sealed']);
    }

    public function test_it_leaves_an_existing_key_alone_without_rotate(): void
    {
        $existing = IntegrityKeyring::generate(2);
        EnvFile::update(['INTEGRITY_KEY' => $existing], $this->envPath());

        $this->artisan('dls:integrity:key')->assertSuccessful();

        $this->assertSame($existing, EnvFile::read('INTEGRITY_KEY', $this->envPath()));
        $this->assertSame(0, AuditLog::where('action', AuditLog::ACTION_INTEGRITY_KEY_CREATED)->count());
    }

    public function test_rotate_moves_the_current_key_to_the_previous_keys(): void
    {
        $v2 = IntegrityKeyring::generate(2);
        $v3 = IntegrityKeyring::generate(3);
        EnvFile::update(['INTEGRITY_KEY' => $v3, 'INTEGRITY_PREVIOUS_KEYS' => $v2], $this->envPath());

        $this->artisan('dls:integrity:key', ['--rotate' => true])->assertSuccessful();

        $current = EnvFile::read('INTEGRITY_KEY', $this->envPath());
        $this->assertSame(4, IntegrityKeyring::versionOf($current));
        $this->assertSame($v3.','.$v2, EnvFile::read('INTEGRITY_PREVIOUS_KEYS', $this->envPath()));

        // What .env now holds is a keyring that verifies all three versions.
        config([
            'security.integrity_keys.current' => $current,
            'security.integrity_keys.previous' => EnvFile::read('INTEGRITY_PREVIOUS_KEYS', $this->envPath()),
        ]);
        $this->assertSame([4, 3, 2], (new IntegrityKeyring())->versions());

        $row = AuditLog::where('action', AuditLog::ACTION_INTEGRITY_KEY_ROTATED)->sole();
        $this->assertSame(4, $row->context['key_version']);
        $this->assertSame(3, $row->context['previous_version']);
    }

    public function test_rotate_without_a_key_fails(): void
    {
        $this->artisan('dls:integrity:key', ['--rotate' => true])->assertFailed();

        $this->assertSame('', EnvFile::read('INTEGRITY_KEY', $this->envPath()));
    }

    public function test_an_invalid_current_key_is_not_overwritten(): void
    {
        EnvFile::update(['INTEGRITY_KEY' => 'not-a-key'], $this->envPath());

        $this->artisan('dls:integrity:key', ['--rotate' => true])->assertFailed();

        $this->assertSame('not-a-key', EnvFile::read('INTEGRITY_KEY', $this->envPath()));
    }

    public function test_show_prints_the_line_and_writes_nothing(): void
    {
        $before = File::get($this->envPath());

        $this->artisan('dls:integrity:key', ['--show' => true])
            ->expectsOutputToContain('INTEGRITY_KEY=')
            ->assertSuccessful();

        $this->assertSame($before, File::get($this->envPath()));
        $this->assertSame(0, AuditLog::count());
    }

    private function envPath(): string
    {
        return $this->dir.'/.env';
    }
}
