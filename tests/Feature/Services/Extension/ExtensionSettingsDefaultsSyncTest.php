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

namespace Tests\Feature\Services\Extension;

use App\Contracts\Extension\ProvidesSettingsDefaultsInterface;
use App\Services\Extension\ExtensionSettingsDefaultsSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExtensionSettingsDefaultsSyncTest extends TestCase
{
    use RefreshDatabase;

    private string $tempTable = 'test_extension_settings_sync';

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Standalone settings table with the Dixlase-convention shape.
        Schema::create($this->tempTable, function ($t) {
            $t->id();
            $t->string('name')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });

        // Fake extension directory holding a fake manifest.
        $this->tempDir = storage_path('framework/testing/sync-'.uniqid());
        File::makeDirectory($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists($this->tempTable);
        if (isset($this->tempDir) && File::isDirectory($this->tempDir)) {
            File::deleteDirectory($this->tempDir);
        }
        parent::tearDown();
    }

    private function writeManifest(string $kind, array $providers): void
    {
        $file = $kind === 'theme' ? 'theme.json' : 'plugin.json';
        File::put($this->tempDir.'/'.$file, json_encode(['providers' => $providers]));
    }

    public function test_it_inserts_only_missing_keys(): void
    {
        // Simulate an existing install: two keys already in the DB,
        // one with an operator-edited value that must be preserved.
        DB::table($this->tempTable)->insert([
            ['name' => 'existing_a', 'value' => 'operator-edited', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'existing_b', 'value' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        FakeSyncProvider::$table = $this->tempTable;
        FakeSyncProvider::$defaults = [
            'existing_a' => 'CONTRACT-DEFAULT-A', // must NOT overwrite
            'existing_b' => 'CONTRACT-DEFAULT-B', // must NOT overwrite (null is present)
            'new_key_c' => 'default-c',           // MUST insert
            'new_key_d' => null,                  // MUST insert (as null)
        ];
        $this->writeManifest('theme', [FakeSyncProvider::class]);

        $result = app(ExtensionSettingsDefaultsSync::class)->syncForExtension($this->tempDir, 'theme');

        $this->assertSame($this->tempTable, $result['table']);
        $this->assertEqualsCanonicalizing(['new_key_c', 'new_key_d'], $result['synced_keys']);

        $rows = DB::table($this->tempTable)->pluck('value', 'name')->all();
        $this->assertSame('operator-edited', $rows['existing_a']);
        $this->assertNull($rows['existing_b']);
        $this->assertSame('default-c', $rows['new_key_c']);
        $this->assertNull($rows['new_key_d']);
    }

    public function test_it_is_idempotent_across_repeated_calls(): void
    {
        FakeSyncProvider::$table = $this->tempTable;
        FakeSyncProvider::$defaults = ['a' => '1', 'b' => '2'];
        $this->writeManifest('theme', [FakeSyncProvider::class]);

        $sync = app(ExtensionSettingsDefaultsSync::class);

        $first = $sync->syncForExtension($this->tempDir, 'theme');
        $second = $sync->syncForExtension($this->tempDir, 'theme');

        $this->assertEqualsCanonicalizing(['a', 'b'], $first['synced_keys']);
        $this->assertSame([], $second['synced_keys']); // nothing new to do on the second run
        $this->assertSame(2, DB::table($this->tempTable)->count());
    }

    public function test_it_coerces_bool_int_float_to_string(): void
    {
        FakeSyncProvider::$table = $this->tempTable;
        FakeSyncProvider::$defaults = [
            'as_bool_true' => true,
            'as_bool_false' => false,
            'as_int' => 42,
            'as_float' => 1.5,
            'as_string' => 'plain',
            'as_null' => null,
        ];
        $this->writeManifest('theme', [FakeSyncProvider::class]);

        app(ExtensionSettingsDefaultsSync::class)->syncForExtension($this->tempDir, 'theme');

        $rows = DB::table($this->tempTable)->pluck('value', 'name')->all();
        $this->assertSame('1', $rows['as_bool_true']);
        $this->assertSame('0', $rows['as_bool_false']);
        $this->assertSame('42', $rows['as_int']);
        $this->assertSame('1.5', $rows['as_float']);
        $this->assertSame('plain', $rows['as_string']);
        $this->assertNull($rows['as_null']);
    }

    public function test_it_no_ops_when_manifest_is_missing(): void
    {
        // Directory exists but no manifest file inside it.
        $result = app(ExtensionSettingsDefaultsSync::class)->syncForExtension($this->tempDir, 'theme');

        $this->assertSame(['synced_keys' => [], 'table' => null], $result);
        $this->assertSame(0, DB::table($this->tempTable)->count());
    }

    public function test_it_no_ops_when_no_provider_implements_the_contract(): void
    {
        $this->writeManifest('theme', [\stdClass::class]); // exists, but wrong shape

        $result = app(ExtensionSettingsDefaultsSync::class)->syncForExtension($this->tempDir, 'theme');

        $this->assertSame(['synced_keys' => [], 'table' => null], $result);
        $this->assertSame(0, DB::table($this->tempTable)->count());
    }

    public function test_it_no_ops_when_declared_table_does_not_exist(): void
    {
        FakeSyncProvider::$table = 'table_that_does_not_exist';
        FakeSyncProvider::$defaults = ['a' => '1'];
        $this->writeManifest('theme', [FakeSyncProvider::class]);

        $result = app(ExtensionSettingsDefaultsSync::class)->syncForExtension($this->tempDir, 'theme');

        $this->assertSame(['synced_keys' => [], 'table' => null], $result);
    }

    public function test_plugin_kind_falls_back_to_dixlase_json_alias(): void
    {
        FakeSyncProvider::$table = $this->tempTable;
        FakeSyncProvider::$defaults = ['from_alias' => 'yes'];
        // plugin.json absent → dixlase.json used instead.
        File::put($this->tempDir.'/dixlase.json', json_encode(['providers' => [FakeSyncProvider::class]]));

        $result = app(ExtensionSettingsDefaultsSync::class)->syncForExtension($this->tempDir, 'plugin');

        $this->assertSame(['from_alias'], $result['synced_keys']);
        $this->assertSame('yes', DB::table($this->tempTable)->where('name', 'from_alias')->value('value'));
    }
}

/**
 * Stub provider that satisfies the contract via static state so a
 * single class covers every test case's shape without spinning up
 * one throwaway subclass per test.
 */
class FakeSyncProvider implements ProvidesSettingsDefaultsInterface
{
    public static string $table = '';

    /** @var array<string, string|int|float|bool|null> */
    public static array $defaults = [];

    public function getSettingsTable(): string
    {
        return self::$table;
    }

    public function getSettingsDefaults(): array
    {
        return self::$defaults;
    }
}
