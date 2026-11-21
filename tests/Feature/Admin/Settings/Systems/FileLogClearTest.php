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

namespace Tests\Feature\Admin\Settings\Systems;

use App\Enums\AdminMode;
use App\Enums\MemberRole;
use App\Helpers\AdminModeHelper;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\Concerns\BypassesRecentAuthentication;
use Tests\TestCase;

/**
 * Clearing the file logs is SUPER_ADMIN-only (#843).
 *
 * The route's menu key (settings.systems.logs) resolves through its
 * ADMIN-level children, so before the explicit check an ADMIN could blank
 * the admin-activity and error logs, although
 * Permission::SYSTEM_LOGS_DELETE is declared SUPER_ADMIN.
 *
 * The controller reads storage_path('logs'), so the storage path is moved
 * to a throwaway directory: the container's real logs are never touched.
 */
class FileLogClearTest extends TestCase
{
    use BypassesRecentAuthentication;
    use RefreshDatabase;

    private string $storage;

    private string $originalStorage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            ContentSecurityPolicy::class,
            EnsureEmailIsVerified::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        // File logs are hidden in simple mode, which redirects before any check
        SiteSetting::setValue('admin_mode', (string) AdminMode::Advanced->value);
        AdminModeHelper::clearCache();

        $this->originalStorage = $this->app->storagePath();
        $this->storage = $this->originalStorage.'/framework/testing/file-log-clear-'.uniqid();
        File::ensureDirectoryExists($this->storage.'/logs');
        File::put($this->storage.'/logs/admin_activity.log', "evidence\n");
        $this->app->useStoragePath($this->storage);
    }

    protected function tearDown(): void
    {
        $this->app->useStoragePath($this->originalStorage);
        File::deleteDirectory($this->storage);

        AdminModeHelper::clearCache();
        putenv('INSTALLED=false');
        unset($_ENV['INSTALLED']);

        parent::tearDown();
    }

    private function member(MemberRole $role): Member
    {
        return Member::factory()->create([
            'account_name' => strtolower($role->name).'x',
            'email' => strtolower($role->name).'@example.com',
            'email_verified_at' => now(),
            'role' => $role,
            'status' => 1,
        ]);
    }

    private function logContents(): string
    {
        return File::get($this->storage.'/logs/admin_activity.log');
    }

    public function test_an_admin_cannot_clear_the_file_logs(): void
    {
        $this->actingAs($this->member(MemberRole::ADMIN), 'member')
            ->post(route('admin.settings.systems.logs.clear', ['type' => 'activity']), ['days' => 0])
            ->assertForbidden();

        $this->assertSame("evidence\n", $this->logContents());
    }

    public function test_a_super_admin_can_clear_the_file_logs(): void
    {
        $this->actingAs($this->member(MemberRole::SUPER_ADMIN), 'member')
            ->post(route('admin.settings.systems.logs.clear', ['type' => 'activity']), ['days' => 0])
            ->assertRedirect(route('admin.settings.systems.logs.files', ['type' => 'activity']));

        $this->assertSame('', $this->logContents());
    }

    public function test_an_admin_can_still_download_the_file_logs(): void
    {
        $this->actingAs($this->member(MemberRole::ADMIN), 'member')
            ->get(route('admin.settings.systems.logs.download', ['type' => 'activity']))
            ->assertOk();
    }
}
