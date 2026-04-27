<?php

namespace Tests\Unit\Models;

use App\Models\BackupRecord;
use App\Models\RestoreRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestoreRecordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * レコード作成テスト
     */
    public function test_create_restore_record(): void
    {
        $backup = $this->createBackupRecord();

        $record = RestoreRecord::create([
            'backup_record_id' => $backup->id,
            'restored_by' => null,
            'restored_by_name' => 'admin@example.com',
            'restored_at' => now(),
            'targets' => ['database', 'media'],
            'status' => RestoreRecord::STATUS_PENDING,
            'metadata' => ['warning_count' => 0],
        ]);

        $this->assertDatabaseHas('restore_records', [
            'backup_record_id' => $backup->id,
            'restored_by_name' => 'admin@example.com',
            'status' => 'pending',
        ]);

        $this->assertEquals(['database', 'media'], $record->targets);
    }

    /**
     * markAsInProgress のテスト
     */
    public function test_mark_as_in_progress(): void
    {
        $record = $this->createRecord();

        $record->markAsInProgress();
        $record->refresh();

        $this->assertEquals(RestoreRecord::STATUS_IN_PROGRESS, $record->status);
    }

    /**
     * markAsCompleted のテスト
     */
    public function test_mark_as_completed(): void
    {
        $record = $this->createRecord();

        $record->markAsCompleted(120);
        $record->refresh();

        $this->assertEquals(RestoreRecord::STATUS_COMPLETED, $record->status);
        $this->assertEquals(120, $record->duration_seconds);
    }

    /**
     * markAsFailed のテスト
     */
    public function test_mark_as_failed(): void
    {
        $record = $this->createRecord();

        $record->markAsFailed('Database connection lost');
        $record->refresh();

        $this->assertEquals(RestoreRecord::STATUS_FAILED, $record->status);
        $this->assertEquals('Database connection lost', $record->error);
    }

    /**
     * markAsRolledBack のテスト
     */
    public function test_mark_as_rolled_back(): void
    {
        $record = $this->createRecord();

        $record->markAsRolledBack();
        $record->refresh();

        $this->assertEquals(RestoreRecord::STATUS_ROLLED_BACK, $record->status);
    }

    /**
     * scopeCompleted のテスト
     */
    public function test_scope_completed(): void
    {
        $this->createRecord(['status' => RestoreRecord::STATUS_COMPLETED]);
        $this->createRecord(['status' => RestoreRecord::STATUS_PENDING]);
        $this->createRecord(['status' => RestoreRecord::STATUS_FAILED]);

        $records = RestoreRecord::completed()->get();

        $this->assertCount(1, $records);
    }

    /**
     * scopeFailed のテスト
     */
    public function test_scope_failed(): void
    {
        $this->createRecord(['status' => RestoreRecord::STATUS_FAILED]);
        $this->createRecord(['status' => RestoreRecord::STATUS_COMPLETED]);
        $this->createRecord(['status' => RestoreRecord::STATUS_FAILED]);

        $records = RestoreRecord::failed()->get();

        $this->assertCount(2, $records);
    }

    /**
     * scopeFromBackup のテスト
     */
    public function test_scope_from_backup(): void
    {
        $backup1 = $this->createBackupRecord();
        $backup2 = $this->createBackupRecord();

        $this->createRecord(['backup_record_id' => $backup1->id]);
        $this->createRecord(['backup_record_id' => $backup2->id]);
        $this->createRecord(['backup_record_id' => $backup1->id]);

        $records = RestoreRecord::fromBackup($backup1->id)->get();

        $this->assertCount(2, $records);
    }

    /**
     * canRollback のテスト：可能なケース
     */
    public function test_can_rollback_when_completed_with_pre_restore_backup(): void
    {
        $backup = $this->createBackupRecord();
        $preRestoreBackup = $this->createBackupRecord();

        $record = $this->createRecord([
            'backup_record_id' => $backup->id,
            'pre_restore_backup_id' => $preRestoreBackup->id,
            'status' => RestoreRecord::STATUS_COMPLETED,
        ]);

        $this->assertTrue($record->canRollback());
    }

    /**
     * canRollback のテスト：pre_restore_backup_id が null
     */
    public function test_cannot_rollback_without_pre_restore_backup(): void
    {
        $record = $this->createRecord([
            'pre_restore_backup_id' => null,
            'status' => RestoreRecord::STATUS_COMPLETED,
        ]);

        $this->assertFalse($record->canRollback());
    }

    /**
     * canRollback のテスト：完了していない
     */
    public function test_cannot_rollback_when_not_completed(): void
    {
        $preRestoreBackup = $this->createBackupRecord();

        $record = $this->createRecord([
            'pre_restore_backup_id' => $preRestoreBackup->id,
            'status' => RestoreRecord::STATUS_PENDING,
        ]);

        $this->assertFalse($record->canRollback());
    }

    /**
     * getMetadataValue のテスト
     */
    public function test_get_metadata_value(): void
    {
        $record = $this->createRecord([
            'metadata' => ['restored_tables' => 15, 'warning_count' => 2],
        ]);

        $this->assertEquals(15, $record->getMetadataValue('restored_tables'));
        $this->assertEquals(2, $record->getMetadataValue('warning_count'));
        $this->assertNull($record->getMetadataValue('nonexistent'));
        $this->assertEquals('default', $record->getMetadataValue('nonexistent', 'default'));
    }

    /**
     * バックアップとのリレーションテスト
     */
    public function test_backup_record_relation(): void
    {
        $backup = $this->createBackupRecord();
        $record = $this->createRecord(['backup_record_id' => $backup->id]);

        $this->assertEquals($backup->id, $record->backupRecord->id);
    }

    /**
     * 復元前バックアップとのリレーションテスト
     */
    public function test_pre_restore_backup_relation(): void
    {
        $backup = $this->createBackupRecord();
        $preRestoreBackup = $this->createBackupRecord();

        $record = $this->createRecord([
            'backup_record_id' => $backup->id,
            'pre_restore_backup_id' => $preRestoreBackup->id,
        ]);

        $this->assertEquals($preRestoreBackup->id, $record->preRestoreBackup->id);
    }

    /**
     * ステータス定数の値を確認
     */
    public function test_status_constants(): void
    {
        $this->assertEquals('pending', RestoreRecord::STATUS_PENDING);
        $this->assertEquals('in_progress', RestoreRecord::STATUS_IN_PROGRESS);
        $this->assertEquals('completed', RestoreRecord::STATUS_COMPLETED);
        $this->assertEquals('failed', RestoreRecord::STATUS_FAILED);
        $this->assertEquals('rolled_back', RestoreRecord::STATUS_ROLLED_BACK);
    }

    /**
     * テスト用 BackupRecord を作成するヘルパー
     */
    private function createBackupRecord(array $overrides = []): BackupRecord
    {
        return BackupRecord::create(array_merge([
            'plugin_slug' => 'dixlase-backup',
            'type' => BackupRecord::TYPE_FULL,
            'targets' => ['database'],
            'file_path' => '/backups/test_'.uniqid().'.zip',
            'file_name' => 'test.zip',
            'file_size' => 1024,
            'is_encrypted' => false,
            'verification_status' => BackupRecord::VERIFICATION_UNCHECKED,
            'status' => BackupRecord::STATUS_COMPLETED,
        ], $overrides));
    }

    /**
     * テスト用 RestoreRecord を作成するヘルパー
     */
    private function createRecord(array $overrides = []): RestoreRecord
    {
        $defaults = [
            'backup_record_id' => null,
            'restored_by' => null,
            'restored_by_name' => 'tester',
            'restored_at' => now(),
            'targets' => ['database'],
            'status' => RestoreRecord::STATUS_PENDING,
        ];

        // backup_record_id が未指定なら新規作成
        if (! array_key_exists('backup_record_id', $overrides)) {
            $defaults['backup_record_id'] = $this->createBackupRecord()->id;
        }

        return RestoreRecord::create(array_merge($defaults, $overrides));
    }
}
