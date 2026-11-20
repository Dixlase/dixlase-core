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

namespace Tests\Feature\Backup;

use App\Contracts\Backup\BackupServiceInterface;
use App\Models\AuditLog;
use App\Models\BackupRecord;
use App\Models\RestoreRecord;
use App\Services\Backup\BackupArchiveSigner;
use App\Services\Backup\CoreBackupService;
use App\Services\Backup\CoreRestoreService;
use App\Services\Security\IntegrityKeyring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * #635: a backup is signed with the integrity key when it is created, and
 * a restore refuses an archive whose signature does not verify unless the
 * operator explicitly accepts it, which is audited.
 */
class BackupSigningTest extends TestCase
{
    use RefreshDatabase;

    private string $customDirRelative;

    protected function setUp(): void
    {
        parent::setUp();

        // Restoring the custom target clears its directory first, so it
        // points at a scratch directory, never the real custom/.
        $this->customDirRelative = 'storage/framework/testing/custom-'.uniqid();
        config(['custom.custom_files_dir' => $this->customDirRelative]);
        @mkdir(base_path($this->customDirRelative), 0755, true);

        $this->useKey(null);
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

        parent::tearDown();
    }

    private function useKey(?string $current, ?string $previous = null): void
    {
        config([
            'security.integrity_keys.current' => $current,
            'security.integrity_keys.previous' => $previous,
        ]);
    }

    private function backup(): BackupRecord
    {
        $result = app(CoreBackupService::class)->backup([BackupServiceInterface::TARGET_CUSTOM]);
        $this->assertTrue($result->success, (string) $result->error);

        return BackupRecord::findOrFail($result->backupRecordId);
    }

    private function restore(BackupRecord $backup, array $options = []): \App\DTO\Backup\RestoreResultDTO
    {
        return app(CoreRestoreService::class)->restore($backup, [], ['skip_pre_restore_backup' => true] + $options);
    }

    private function auditCount(string $action): int
    {
        return AuditLog::where('action', $action)->count();
    }

    public function test_without_a_key_nothing_is_signed_and_restore_checks_the_hash_only(): void
    {
        $backup = $this->backup();

        $this->assertNull($backup->signature);
        $this->assertSame(BackupArchiveSigner::NOT_CHECKED, app(BackupArchiveSigner::class)->check($backup));
        $this->assertTrue($this->restore($backup)->success);
    }

    public function test_a_backup_is_signed_on_creation_and_restores(): void
    {
        $this->useKey(IntegrityKeyring::generate(3));

        $backup = $this->backup();

        $this->assertSame(BackupArchiveSigner::ALGORITHM, $backup->signature_algorithm);
        $this->assertSame(3, $backup->signature_key_version);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (string) $backup->signature);
        $this->assertSame(BackupArchiveSigner::VALID, app(BackupArchiveSigner::class)->check($backup));

        $result = $this->restore($backup);
        $this->assertTrue($result->success, (string) $result->error);
        $this->assertSame(0, $this->auditCount(AuditLog::ACTION_BACKUP_SIGNATURE_OVERRIDDEN));
    }

    public function test_an_archive_swapped_together_with_its_hash_is_refused(): void
    {
        $this->useKey(IntegrityKeyring::generate(2));
        $backup = $this->backup();

        // Someone who can write the backup storage and the database puts
        // another archive in place and makes the record's hash agree.
        file_put_contents(base_path($this->customDirRelative).'/planted.txt', 'planted');
        $other = $this->backup();
        copy($other->file_path, $backup->file_path);
        $backup->update(['hash' => $other->hash]);

        $result = $this->restore($backup->fresh());

        $this->assertFalse($result->success);
        $this->assertStringContainsString('signature does not match', (string) $result->error);
        $this->assertSame(1, $this->auditCount(AuditLog::ACTION_BACKUP_SIGNATURE_REJECTED));
        $this->assertSame(0, RestoreRecord::count());
    }

    public function test_the_signature_covers_the_targets_of_the_record(): void
    {
        $this->useKey(IntegrityKeyring::generate(2));
        $backup = $this->backup();

        $backup->update(['targets' => [BackupServiceInterface::TARGET_CUSTOM, BackupServiceInterface::TARGET_DATABASE]]);

        $this->assertSame(BackupArchiveSigner::INVALID, app(BackupArchiveSigner::class)->check($backup->fresh()));
    }

    public function test_a_backup_taken_before_the_key_is_refused_once_a_key_is_set(): void
    {
        $backup = $this->backup();
        $this->useKey(IntegrityKeyring::generate(2));

        $result = $this->restore($backup);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('not signed', (string) $result->error);
        $rejection = AuditLog::where('action', AuditLog::ACTION_BACKUP_SIGNATURE_REJECTED)->sole();
        $this->assertSame(BackupArchiveSigner::UNSIGNED, $rejection->context['signature_status'] ?? null);
    }

    public function test_the_explicit_override_restores_and_is_audited(): void
    {
        $backup = $this->backup();
        $this->useKey(IntegrityKeyring::generate(2));

        $result = $this->restore($backup, [CoreRestoreService::OPTION_ALLOW_UNVERIFIED_SIGNATURE => true]);

        $this->assertTrue($result->success, (string) $result->error);
        $override = AuditLog::where('action', AuditLog::ACTION_BACKUP_SIGNATURE_OVERRIDDEN)->sole();
        $this->assertSame(BackupArchiveSigner::UNSIGNED, $override->context['signature_status'] ?? null);
        $this->assertSame($backup->id, $override->context['backup_record_id'] ?? null);
        $this->assertSame(0, $this->auditCount(AuditLog::ACTION_BACKUP_SIGNATURE_REJECTED));
    }

    public function test_a_rotated_key_still_verifies_while_it_is_kept(): void
    {
        $old = IntegrityKeyring::generate(2);
        $this->useKey($old);
        $backup = $this->backup();

        $this->useKey(IntegrityKeyring::generate(3), $old);
        $this->assertSame(BackupArchiveSigner::VALID, app(BackupArchiveSigner::class)->check($backup));

        $this->useKey(IntegrityKeyring::generate(3));
        $this->assertSame(BackupArchiveSigner::KEY_UNAVAILABLE, app(BackupArchiveSigner::class)->check($backup));
        $this->assertFalse($this->restore($backup)->success);
    }

    public function test_a_different_key_of_the_same_version_does_not_verify(): void
    {
        $this->useKey(IntegrityKeyring::generate(2));
        $backup = $this->backup();

        $this->useKey(IntegrityKeyring::generate(2));

        $this->assertSame(BackupArchiveSigner::INVALID, app(BackupArchiveSigner::class)->check($backup));
    }

    public function test_rollback_checks_the_snapshot_and_takes_the_override(): void
    {
        $this->useKey(IntegrityKeyring::generate(2));
        $backup = $this->backup();

        $restored = app(CoreRestoreService::class)->restore($backup);
        $this->assertTrue($restored->success, (string) $restored->error);
        $restore = RestoreRecord::findOrFail($restored->restoreRecordId);

        // The snapshot was signed when it was taken; strip it to stand in
        // for one taken before the key.
        BackupRecord::whereKey($restored->preRestoreBackupRecordId)->update(['signature' => null]);

        $refused = app(CoreRestoreService::class)->rollback($restore->fresh());
        $this->assertFalse($refused->success);

        $rolledBack = app(CoreRestoreService::class)->rollback($restore->fresh(), [CoreRestoreService::OPTION_ALLOW_UNVERIFIED_SIGNATURE => true]);
        $this->assertTrue($rolledBack->success, (string) $rolledBack->error);
        $this->assertSame(1, $this->auditCount(AuditLog::ACTION_BACKUP_SIGNATURE_OVERRIDDEN));
    }

    public function test_the_command_refuses_and_names_the_option(): void
    {
        $backup = $this->backup();
        $this->useKey(IntegrityKeyring::generate(2));

        $this->artisan('dls:backup:restore', ['id' => $backup->id, '--force' => true, '--no-snapshot' => true])
            ->expectsOutputToContain('--allow-unverified-signature')
            ->assertExitCode(1);
        $this->assertSame(0, RestoreRecord::count());
        $this->assertSame(1, $this->auditCount(AuditLog::ACTION_BACKUP_SIGNATURE_REJECTED));

        $this->artisan('dls:backup:restore', [
            'id' => $backup->id, '--force' => true, '--no-snapshot' => true, '--allow-unverified-signature' => true,
        ])->assertExitCode(0);
        $this->assertSame(1, $this->auditCount(AuditLog::ACTION_BACKUP_SIGNATURE_OVERRIDDEN));
    }
}
