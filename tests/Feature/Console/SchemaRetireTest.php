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

use App\Models\AuditLog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Feature coverage for `dls:schema:retire`.
 *
 * A freshly migrated test database has none of the retired objects, so the
 * "clean" case needs no setup. The other cases recreate the leftovers an
 * existing beta site actually carries: the laragear/webauthn table, and the
 * table / column / marker rows the core-update verification fixtures write.
 */
class SchemaRetireTest extends TestCase
{
    use RefreshDatabase;

    private const MARKERS = [
        'core_update_migration_marker',
        'core_update_round2_migration_marker',
        'core_update_round3_migration_marker',
        'core_update_round4_migration_marker',
        'core_update_round5_migration_marker',
        'core_update_round6_migration_marker',
        'core_update_seeder_marker',
    ];

    // ------------------------------------------------------------------
    // Fixture helpers
    // ------------------------------------------------------------------

    protected function createLegacyWebauthnTable(int $rows = 0): void
    {
        Schema::create('webauthn_credentials', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('authenticatable_type');
            $table->unsignedBigInteger('authenticatable_id');
            $table->timestamps();
        });

        for ($i = 1; $i <= $rows; $i++) {
            DB::table('webauthn_credentials')->insert([
                'id' => "cred-{$i}",
                'authenticatable_type' => 'App\\Models\\Member',
                'authenticatable_id' => $i,
            ]);
        }
    }

    protected function createFixtureLeftovers(): void
    {
        Schema::create('core_migration_smoke', function (Blueprint $table) {
            $table->id();
            $table->string('label');
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->boolean('migration_smoke_flag')->default(false);
        });

        foreach (self::MARKERS as $name) {
            DB::table('global_settings')->insert(['name' => $name, 'value' => '0.3.x']);
        }
    }

    protected function auditCount(): int
    {
        return AuditLog::query()->where('action', AuditLog::ACTION_SCHEMA_RETIRED)->count();
    }

    // ------------------------------------------------------------------
    // Tests
    // ------------------------------------------------------------------

    public function test_reports_nothing_to_retire_on_a_fresh_install(): void
    {
        $exit = Artisan::call('dls:schema:retire', ['--confirm' => true]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Nothing to retire', Artisan::output());
        $this->assertSame(0, $this->auditCount());
    }

    public function test_dry_run_lists_targets_without_changing_anything(): void
    {
        $this->createLegacyWebauthnTable();
        $this->createFixtureLeftovers();

        Artisan::call('dls:schema:retire');
        $output = Artisan::output();

        $this->assertStringContainsString('[DRY-RUN]', $output);
        $this->assertStringContainsString('webauthn_credentials', $output);
        $this->assertTrue(Schema::hasTable('webauthn_credentials'));
        $this->assertTrue(Schema::hasTable('core_migration_smoke'));
        $this->assertTrue(Schema::hasColumn('sites', 'migration_smoke_flag'));
        $this->assertSame(7, DB::table('global_settings')->whereIn('name', self::MARKERS)->count());
    }

    public function test_confirm_removes_every_listed_object(): void
    {
        $this->createLegacyWebauthnTable();
        $this->createFixtureLeftovers();

        Artisan::call('dls:schema:retire', ['--confirm' => true]);

        $this->assertStringContainsString('[APPLIED]', Artisan::output());
        $this->assertFalse(Schema::hasTable('webauthn_credentials'));
        $this->assertFalse(Schema::hasTable('core_migration_smoke'));
        $this->assertFalse(Schema::hasColumn('sites', 'migration_smoke_flag'));
        $this->assertSame(0, DB::table('global_settings')->whereIn('name', self::MARKERS)->count());
        $this->assertTrue(Schema::hasTable('members_passkeys'));
    }

    public function test_second_run_is_a_no_op(): void
    {
        $this->createLegacyWebauthnTable();
        $this->createFixtureLeftovers();

        Artisan::call('dls:schema:retire', ['--confirm' => true]);
        Artisan::call('dls:schema:retire', ['--confirm' => true]);

        $this->assertStringContainsString('Nothing to retire', Artisan::output());
        $this->assertSame(1, $this->auditCount());
    }

    public function test_leaves_unrelated_settings_and_columns_alone(): void
    {
        $this->createFixtureLeftovers();
        DB::table('global_settings')->insert(['name' => 'core_update_something_else', 'value' => 'keep']);
        $settingsBefore = DB::table('global_settings')->whereNotIn('name', self::MARKERS)->count();
        $siteName = DB::table('sites')->where('id', 1)->value('name');

        Artisan::call('dls:schema:retire', ['--confirm' => true]);

        $this->assertSame($settingsBefore, DB::table('global_settings')->count());
        $this->assertTrue(DB::table('global_settings')->where('name', 'core_update_something_else')->exists());
        $this->assertSame($siteName, DB::table('sites')->where('id', 1)->value('name'));
    }

    public function test_json_output_describes_each_target(): void
    {
        $this->createLegacyWebauthnTable();

        Artisan::call('dls:schema:retire', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame('retire', $payload['action']);
        $this->assertSame('dry_run', $payload['status']);

        $byName = array_column($payload['targets'], null, 'name');
        $this->assertTrue($byName['webauthn_credentials']['present']);
        $this->assertSame(0, $byName['webauthn_credentials']['rows']);
        $this->assertFalse($byName['core_migration_smoke']['present']);
        $this->assertFalse($byName['sites.migration_smoke_flag']['present']);
        $this->assertSame([], $payload['warnings']);
    }

    public function test_warns_when_legacy_passkeys_would_be_deleted(): void
    {
        $this->createLegacyWebauthnTable(rows: 2);

        Artisan::call('dls:schema:retire', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertCount(1, $payload['warnings']);
        $this->assertStringContainsString('2 legacy passkey row(s) will be deleted', $payload['warnings'][0]);
        $this->assertTrue(Schema::hasTable('webauthn_credentials'));
    }

    public function test_audit_entry_is_written_only_on_confirm(): void
    {
        $this->createLegacyWebauthnTable(rows: 1);

        Artisan::call('dls:schema:retire');
        $this->assertSame(0, $this->auditCount());

        Artisan::call('dls:schema:retire', ['--confirm' => true]);
        $this->assertSame(1, $this->auditCount());

        $entry = AuditLog::query()->where('action', AuditLog::ACTION_SCHEMA_RETIRED)->firstOrFail();
        $this->assertSame(AuditLog::CATEGORY_SYSTEM, $entry->category);
        $this->assertStringContainsString('webauthn_credentials', (string) $entry->target_label);
    }
}
