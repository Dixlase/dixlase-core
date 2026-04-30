<?php

namespace Tests\Unit\Services\Backup;

use App\Contracts\Backup\BackupServiceInterface;
use App\Contracts\Backup\RestoreServiceInterface;
use App\DTO\Backup\RestoreResultDTO;
use App\Models\BackupRecord;
use App\Models\RestoreRecord;
use App\Services\Backup\CoreBackupService;
use App\Services\Backup\CoreRestoreService;
use App\Services\Verification\CoreFileVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreRestoreServiceTest extends TestCase
{
    use RefreshDatabase;

    private CoreBackupService $backupService;

    private CoreRestoreService $restoreService;

    protected function setUp(): void
    {
        parent::setUp();

        $verifier = new CoreFileVerificationService();
        $this->backupService = new CoreBackupService($verifier);
        $this->restoreService = new CoreRestoreService($verifier, $this->backupService);
    }

    protected function tearDown(): void
    {
        $this->cleanupBackupFiles();
        parent::tearDown();
    }

    /**
     * RestoreServiceInterface を実装していることを確認
     */
    public function test_implements_interface(): void
    {
        $this->assertInstanceOf(RestoreServiceInterface::class, $this->restoreService);
    }

    /**
     * サービスコンテナから解決可能
     */
    public function test_resolvable_from_container(): void
    {
        $resolved = app(RestoreServiceInterface::class);

        $this->assertInstanceOf(CoreRestoreService::class, $resolved);
    }

    /**
     * バックアップファイルが存在しない場合は失敗
     */
    public function test_restore_fails_when_backup_file_missing(): void
    {
        $backup = BackupRecord::create([
            'plugin_slug' => 'core',
            'type' => BackupRecord::TYPE_DATABASE,
            'targets' => ['database'],
            'file_path' => '/nonexistent/backup.zip',
            'file_name' => 'backup.zip',
            'file_size' => 100,
            'is_encrypted' => false,
            'verification_status' => BackupRecord::VERIFICATION_UNCHECKED,
            'status' => BackupRecord::STATUS_COMPLETED,
        ]);

        $result = $this->restoreService->restore($backup);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('not found', $result->error);
    }

    /**
     * ハッシュが一致しない場合は失敗
     */
    public function test_restore_fails_on_hash_mismatch(): void
    {
        $backupResult = $this->backupService->backup([BackupServiceInterface::TARGET_CUSTOM]);
        $this->assertTrue($backupResult->success);

        $backup = BackupRecord::find($backupResult->backupRecordId);
        // ハッシュを意図的に書き換える
        $backup->update(['hash' => 'invalid_hash_value_for_test']);

        $result = $this->restoreService->restore($backup);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('hash mismatch', $result->error);
    }

    /**
     * カスタムディレクトリの往復テスト（バックアップ → 改変 → 復元 → 元に戻る）
     */
    public function test_restore_custom_directory_round_trip(): void
    {
        $customDir = base_path('custom');
        if (! is_dir($customDir)) {
            mkdir($customDir, 0755, true);
        }

        $testFile = $customDir.'/restore-test-'.uniqid().'.txt';
        $originalContent = 'original content '.uniqid();
        file_put_contents($testFile, $originalContent);

        try {
            // 1. バックアップ作成
            $backupResult = $this->backupService->backup([BackupServiceInterface::TARGET_CUSTOM]);
            $this->assertTrue($backupResult->success, 'Backup failed: '.$backupResult->error);
            $backup = BackupRecord::find($backupResult->backupRecordId);

            // 2. ファイル改変
            file_put_contents($testFile, 'modified content');
            $this->assertEquals('modified content', file_get_contents($testFile));

            // 3. 復元
            $result = $this->restoreService->restore($backup, [BackupServiceInterface::TARGET_CUSTOM]);

            $this->assertTrue($result->success, 'Restore failed: '.$result->error);
            $this->assertNotNull($result->restoreRecordId);

            // 4. 内容が元に戻っていることを確認
            $this->assertFileExists($testFile);
            $this->assertEquals($originalContent, file_get_contents($testFile));
        } finally {
            @unlink($testFile);
        }
    }

    /**
     * RestoreRecord が正しく作成される
     */
    public function test_restore_creates_restore_record(): void
    {
        $backupResult = $this->backupService->backup([BackupServiceInterface::TARGET_CUSTOM]);
        $backup = BackupRecord::find($backupResult->backupRecordId);

        $result = $this->restoreService->restore($backup);

        $this->assertTrue($result->success);
        $record = RestoreRecord::find($result->restoreRecordId);
        $this->assertNotNull($record);
        $this->assertEquals(RestoreRecord::STATUS_COMPLETED, $record->status);
        $this->assertEquals($backup->id, $record->backup_record_id);
        $this->assertEquals(['custom'], $record->targets);
    }

    /**
     * セーフティスナップショットが自動取得される
     */
    public function test_restore_takes_pre_restore_snapshot_by_default(): void
    {
        $backupResult = $this->backupService->backup([BackupServiceInterface::TARGET_CUSTOM]);
        $backup = BackupRecord::find($backupResult->backupRecordId);

        $result = $this->restoreService->restore($backup);

        $this->assertTrue($result->success);
        $this->assertNotNull($result->preRestoreBackupRecordId);

        $snapshotRecord = BackupRecord::find($result->preRestoreBackupRecordId);
        $this->assertNotNull($snapshotRecord);
    }

    /**
     * skip_pre_restore_backup オプションでスナップショットをスキップ
     */
    public function test_restore_skips_pre_restore_snapshot_when_requested(): void
    {
        $backupResult = $this->backupService->backup([BackupServiceInterface::TARGET_CUSTOM]);
        $backup = BackupRecord::find($backupResult->backupRecordId);

        $result = $this->restoreService->restore(
            $backup,
            [],
            ['skip_pre_restore_backup' => true],
        );

        $this->assertTrue($result->success);
        $this->assertNull($result->preRestoreBackupRecordId);
    }

    /**
     * バックアップに含まれない target は無視される
     */
    public function test_restore_filters_unavailable_targets(): void
    {
        // database のみのバックアップ
        $backupResult = $this->backupService->backup([BackupServiceInterface::TARGET_DATABASE]);
        $backup = BackupRecord::find($backupResult->backupRecordId);

        // バックアップに含まれない media を指定
        $result = $this->restoreService->restore($backup, [BackupServiceInterface::TARGET_MEDIA]);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('No valid targets', $result->error);
    }

    /**
     * rollback() の動作確認
     */
    public function test_rollback_restores_pre_restore_state(): void
    {
        $customDir = base_path('custom');
        if (! is_dir($customDir)) {
            mkdir($customDir, 0755, true);
        }

        $testFile = $customDir.'/rollback-test-'.uniqid().'.txt';
        $stateA = 'state A '.uniqid();
        $stateB = 'state B '.uniqid();

        try {
            // ファイル状態 A
            file_put_contents($testFile, $stateA);
            $backupA = $this->backupService->backup([BackupServiceInterface::TARGET_CUSTOM]);
            $backupARecord = BackupRecord::find($backupA->backupRecordId);

            // ファイル状態 B に変更
            file_put_contents($testFile, $stateB);

            // backupA を復元（自動的に状態 B のセーフティスナップショットが取られる）
            $restoreResult = $this->restoreService->restore($backupARecord, [BackupServiceInterface::TARGET_CUSTOM]);
            $this->assertTrue($restoreResult->success);
            $this->assertEquals($stateA, file_get_contents($testFile));

            $restoreRecord = RestoreRecord::find($restoreResult->restoreRecordId);
            $this->assertTrue($restoreRecord->canRollback());

            // rollback で状態 B に戻る
            $rollbackResult = $this->restoreService->rollback($restoreRecord);
            $this->assertTrue($rollbackResult->success, 'Rollback failed: '.$rollbackResult->error);
            $this->assertEquals($stateB, file_get_contents($testFile));

            $restoreRecord->refresh();
            $this->assertEquals(RestoreRecord::STATUS_ROLLED_BACK, $restoreRecord->status);
        } finally {
            @unlink($testFile);
        }
    }

    /**
     * rollback できない RestoreRecord で失敗
     */
    public function test_rollback_fails_for_pending_record(): void
    {
        $backupResult = $this->backupService->backup([BackupServiceInterface::TARGET_CUSTOM]);
        $backup = BackupRecord::find($backupResult->backupRecordId);

        $record = RestoreRecord::create([
            'backup_record_id' => $backup->id,
            'pre_restore_backup_id' => null,
            'restored_by_name' => 'test',
            'restored_at' => now(),
            'targets' => ['custom'],
            'status' => RestoreRecord::STATUS_PENDING,
        ]);

        $result = $this->restoreService->rollback($record);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('cannot be rolled back', $result->error);
    }

    /**
     * 失敗結果 DTO の検証
     */
    public function test_restore_returns_failure_dto(): void
    {
        $backup = BackupRecord::create([
            'plugin_slug' => 'core',
            'type' => BackupRecord::TYPE_DATABASE,
            'targets' => ['database'],
            'file_path' => '/missing.zip',
            'file_name' => 'missing.zip',
            'file_size' => 0,
            'is_encrypted' => false,
            'verification_status' => BackupRecord::VERIFICATION_UNCHECKED,
            'status' => BackupRecord::STATUS_COMPLETED,
        ]);

        $result = $this->restoreService->restore($backup);

        $this->assertInstanceOf(RestoreResultDTO::class, $result);
        $this->assertFalse($result->success);
    }

    /**
     * テストで生成されたバックアップファイルとtempディレクトリを削除
     */
    private function cleanupBackupFiles(): void
    {
        $backupDir = storage_path('app/private/backups');
        if (is_dir($backupDir)) {
            foreach (glob($backupDir.'/dixlase-backup-*.zip') as $file) {
                @unlink($file);
            }
        }

        $tempBase = storage_path('app/private/.backup-tmp');
        if (is_dir($tempBase)) {
            foreach (glob($tempBase.'/backup_*') as $dir) {
                if (is_dir($dir)) {
                    $this->removeDirectory($dir);
                }
            }
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (new \DirectoryIterator($dir) as $item) {
            if ($item->isDot()) {
                continue;
            }
            if ($item->isDir()) {
                $this->removeDirectory($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($dir);
    }
}
