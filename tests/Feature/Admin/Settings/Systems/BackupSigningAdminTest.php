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

namespace Tests\Feature\Admin\Settings\Systems;

use App\Contracts\Backup\BackupServiceInterface;
use App\Enums\MemberRole;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\AuditLog;
use App\Models\BackupRecord;
use App\Models\Member;
use App\Models\RestoreRecord;
use App\Services\Backup\CoreBackupService;
use App\Services\Security\IntegrityKeyring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\Concerns\BypassesRecentAuthentication;
use Tests\TestCase;

/**
 * #635: the backup screens say when a backup's signature does not verify,
 * the restore confirmation then carries the explicit override, and a
 * restore without it is refused.
 */
class BackupSigningAdminTest extends TestCase
{
    use BypassesRecentAuthentication;
    use RefreshDatabase;

    private string $customDirRelative;

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

        // The admin view namespace is registered at boot only on an installed site
        View::addNamespace('admin', [resource_path('views/'.config('themes.admin_theme', 'admin'))]);
        $this->withoutVite();

        $this->customDirRelative = 'storage/framework/testing/custom-'.uniqid();
        config(['custom.custom_files_dir' => $this->customDirRelative]);
        @mkdir(base_path($this->customDirRelative), 0755, true);
        config(['security.integrity_keys.current' => null, 'security.integrity_keys.previous' => null]);

        $this->actingAs(Member::factory()->create([
            'account_name' => 'superadminx',
            'email' => 'superadmin@example.com',
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => 1,
        ]), 'member');
    }

    protected function tearDown(): void
    {
        foreach (glob(storage_path('app/private/backups').'/dixlase-backup-*.zip') ?: [] as $file) {
            @unlink($file);
        }
        $dir = base_path($this->customDirRelative);
        foreach (glob($dir.'/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($dir);
        putenv('INSTALLED=false');
        unset($_ENV['INSTALLED']);

        parent::tearDown();
    }

    /** A backup taken before the site had a key, viewed after one is set */
    private function unsignedBackupUnderKey(): BackupRecord
    {
        $result = app(CoreBackupService::class)->backup([BackupServiceInterface::TARGET_CUSTOM]);
        $this->assertTrue($result->success, (string) $result->error);
        config(['security.integrity_keys.current' => IntegrityKeyring::generate(2)]);

        return BackupRecord::findOrFail($result->backupRecordId);
    }

    public function test_the_list_warns_in_the_restore_confirmation_and_carries_the_override(): void
    {
        $this->unsignedBackupUnderKey();

        $this->get(route('admin.settings.systems.backup.index'))
            ->assertOk()
            ->assertSee(__('admin/settings/systems/backup/index.restore_modal.unverified_confirm_label'))
            ->assertSee(__('admin/settings/systems/backup/index.signature_statuses.unsigned'))
            ->assertSee('name="allow_unverified_signature"', false);
    }

    public function test_a_signed_backup_gets_the_ordinary_confirmation(): void
    {
        config(['security.integrity_keys.current' => IntegrityKeyring::generate(2)]);
        app(CoreBackupService::class)->backup([BackupServiceInterface::TARGET_CUSTOM]);

        $this->get(route('admin.settings.systems.backup.index'))
            ->assertOk()
            ->assertDontSee(__('admin/settings/systems/backup/index.restore_modal.unverified_confirm_label'))
            ->assertDontSee('name="allow_unverified_signature"', false);
    }

    public function test_the_detail_screen_shows_the_signature_state(): void
    {
        $backup = $this->unsignedBackupUnderKey();

        $this->get(route('admin.settings.systems.backup.show', $backup))
            ->assertOk()
            ->assertSee(__('admin/settings/systems/backup/index.signature_statuses.unsigned'));
    }

    public function test_a_restore_without_the_override_is_refused(): void
    {
        $backup = $this->unsignedBackupUnderKey();

        $this->post(route('admin.settings.systems.backup.restore', $backup))
            ->assertRedirect(route('admin.settings.systems.backup.index'))
            ->assertSessionHas('error');

        $this->assertSame(0, RestoreRecord::count());
        $this->assertSame(1, AuditLog::where('action', AuditLog::ACTION_BACKUP_SIGNATURE_REJECTED)->count());
    }

    public function test_a_restore_with_the_override_goes_ahead_and_is_audited(): void
    {
        $backup = $this->unsignedBackupUnderKey();

        $this->post(route('admin.settings.systems.backup.restore', $backup), ['allow_unverified_signature' => '1'])
            ->assertRedirect(route('admin.settings.systems.backup.restores'))
            ->assertSessionHas('success');

        $this->assertSame(1, RestoreRecord::where('status', RestoreRecord::STATUS_COMPLETED)->count());
        $this->assertSame(1, AuditLog::where('action', AuditLog::ACTION_BACKUP_SIGNATURE_OVERRIDDEN)->count());
    }

    public function test_the_rollback_confirmation_checks_the_snapshot(): void
    {
        config(['security.integrity_keys.current' => IntegrityKeyring::generate(2)]);
        $result = app(CoreBackupService::class)->backup([BackupServiceInterface::TARGET_CUSTOM]);
        $restored = app(\App\Services\Backup\CoreRestoreService::class)->restore(BackupRecord::findOrFail($result->backupRecordId));
        $this->assertTrue($restored->success, (string) $restored->error);

        $this->get(route('admin.settings.systems.backup.restores'))
            ->assertOk()
            ->assertDontSee(__('admin/settings/systems/backup/restores.rollback_modal.unverified_confirm_label'));

        BackupRecord::whereKey($restored->preRestoreBackupRecordId)->update(['signature' => null]);

        $this->get(route('admin.settings.systems.backup.restores'))
            ->assertOk()
            ->assertSee(__('admin/settings/systems/backup/restores.rollback_modal.unverified_confirm_label'))
            ->assertSee('name="allow_unverified_signature"', false);
    }
}
