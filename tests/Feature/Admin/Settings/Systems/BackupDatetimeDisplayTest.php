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

namespace Tests\Feature\Admin\Settings\Systems;

use App\Enums\AdminMode;
use App\Enums\MemberRole;
use App\Helpers\AdminModeHelper;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMenuAccess;
use App\Http\Middleware\CheckMenuEdit;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\BackupRecord;
use App\Models\Member;
use App\Models\RestoreRecord;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * The backup screens printed stored times as they came off the model, so a
 * site whose display timezone is not UTC read every backup nine hours out.
 */
class BackupDatetimeDisplayTest extends TestCase
{
    use RefreshDatabase;

    private BackupRecord $backup;

    private mixed $serverInstalled;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            CheckMenuAccess::class,
            CheckMenuEdit::class,
            ContentSecurityPolicy::class,
            EnsureEmailIsVerified::class,
        ]);

        // ConfigHelper reads display_timezone from the database only when
        // INSTALLED is true, so all three channels are overridden.
        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $this->serverInstalled = $_SERVER['INSTALLED'] ?? null;
        $_SERVER['INSTALLED'] = 'true';

        // The service provider registers the admin view namespace only on an
        // installed site at boot, which a test is not.
        $adminTheme = config('themes.admin_theme', 'admin');
        View::addNamespace('admin', [resource_path("views/{$adminTheme}")]);

        SiteSetting::setValue('display_timezone', 'Asia/Tokyo');
        SiteSetting::setValue('admin_mode', (string) AdminMode::Advanced->value);
        AdminModeHelper::clearCache();

        $this->backup = BackupRecord::create([
            'plugin_slug' => 'core',
            'type' => BackupRecord::TYPE_FULL,
            'targets' => ['database'],
            'file_path' => '/backups/fixture.zip',
            'file_name' => 'fixture.zip',
            'file_size' => 1048576,
            'is_encrypted' => false,
            'verification_status' => BackupRecord::VERIFICATION_UNCHECKED,
            'status' => BackupRecord::STATUS_COMPLETED,
            'retention_until' => '2026-04-10 16:10:00',
        ]);
        DB::table('backup_records')->where('id', $this->backup->id)->update([
            'created_at' => '2026-03-01 15:30:45',
        ]);
    }

    protected function tearDown(): void
    {
        SiteSetting::query()->whereIn('name', ['display_timezone', 'admin_mode'])->delete();
        AdminModeHelper::clearCache();
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        // Laravel reads $_SERVER before $_ENV, and phpunit.xml leaves it
        // unset; leaving 'false' behind would override later tests that
        // set only $_ENV.
        if ($this->serverInstalled === null) {
            unset($_SERVER['INSTALLED']);
        } else {
            $_SERVER['INSTALLED'] = $this->serverInstalled;
        }

        parent::tearDown();
    }

    private function superAdmin(): Member
    {
        return Member::factory()->create([
            'account_name' => 'superadminx',
            'email' => 'superadmin@example.com',
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => 1,
        ]);
    }

    public function test_the_list_shows_the_backup_time_in_the_display_timezone(): void
    {
        $this->actingAs($this->superAdmin(), 'member')
            ->get(route('admin.settings.systems.backup.index'))
            ->assertOk()
            ->assertSee('2026-03-02 00:30:45', false)
            ->assertSee('title="2026-03-01 15:30:45 UTC"', false);
    }

    public function test_the_detail_shows_both_times_in_the_display_timezone(): void
    {
        $this->actingAs($this->superAdmin(), 'member')
            ->get(route('admin.settings.systems.backup.show', $this->backup))
            ->assertOk()
            ->assertSee('2026-03-02 00:30:45', false)
            ->assertSee('2026-04-11 01:10:00', false)
            ->assertSee('title="2026-04-10 16:10:00 UTC"', false);
    }

    public function test_the_detail_still_says_when_there_is_no_retention_limit(): void
    {
        $this->backup->update(['retention_until' => null]);

        $this->actingAs($this->superAdmin(), 'member')
            ->get(route('admin.settings.systems.backup.show', $this->backup))
            ->assertOk()
            ->assertSee(__('admin/settings/systems/backup/index.detail.retention_none'));
    }

    public function test_the_restore_history_shows_the_time_in_the_display_timezone(): void
    {
        RestoreRecord::create([
            'backup_record_id' => $this->backup->id,
            'restored_by' => null,
            'restored_by_name' => 'admin@example.com',
            'restored_at' => '2026-05-20 17:20:30',
            'targets' => ['database'],
            'status' => RestoreRecord::STATUS_PENDING,
        ]);

        $this->actingAs($this->superAdmin(), 'member')
            ->get(route('admin.settings.systems.backup.restores'))
            ->assertOk()
            ->assertSee('2026-05-21 02:20:30', false)
            ->assertSee('title="2026-05-20 17:20:30 UTC"', false);
    }
}
