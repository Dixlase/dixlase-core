<?php

namespace Tests\Unit\Services\Backup;

use App\Contracts\Backup\BackupServiceInterface;
use App\DTO\Backup\BackupResultDTO;
use App\Models\BackupRecord;
use App\Services\Backup\CoreBackupService;
use App\Services\Verification\CoreFileVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreBackupServiceTest extends TestCase
{
    use RefreshDatabase;

    private CoreBackupService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CoreBackupService(new CoreFileVerificationService());
    }

    protected function tearDown(): void
    {
        // テストで生成したバックアップファイルとtempディレクトリを削除
        $this->cleanupBackupFiles();

        parent::tearDown();
    }

    /**
     * BackupServiceInterface を実装していることを確認
     */
    public function test_implements_interface(): void
    {
        $this->assertInstanceOf(BackupServiceInterface::class, $this->service);
    }

    /**
     * サービスコンテナから解決可能
     */
    public function test_resolvable_from_container(): void
    {
        $resolved = app(BackupServiceInterface::class);

        $this->assertInstanceOf(CoreBackupService::class, $resolved);
    }

    /**
     * getAvailableTargets が全対象を返す
     */
    public function test_get_available_targets_returns_all_targets(): void
    {
        $targets = $this->service->getAvailableTargets();

        $this->assertContains(BackupServiceInterface::TARGET_DATABASE, $targets);
        $this->assertContains(BackupServiceInterface::TARGET_MEDIA, $targets);
        $this->assertContains(BackupServiceInterface::TARGET_PRIVATE, $targets);
        $this->assertContains(BackupServiceInterface::TARGET_CUSTOM, $targets);
        $this->assertContains(BackupServiceInterface::TARGET_LOGS, $targets);
        $this->assertCount(5, $targets);
    }

    /**
     * getDefaultTargets はオプション項目（logs）を除く
     */
    public function test_get_default_targets_excludes_optional_targets(): void
    {
        $targets = $this->service->getDefaultTargets();

        $this->assertContains(BackupServiceInterface::TARGET_DATABASE, $targets);
        $this->assertContains(BackupServiceInterface::TARGET_MEDIA, $targets);
        $this->assertContains(BackupServiceInterface::TARGET_PRIVATE, $targets);
        $this->assertContains(BackupServiceInterface::TARGET_CUSTOM, $targets);
        $this->assertNotContains(BackupServiceInterface::TARGET_LOGS, $targets);
        $this->assertCount(4, $targets);
    }

    /**
     * バックアップ対象定数の値を確認
     */
    public function test_target_constants(): void
    {
        $this->assertEquals('database', BackupServiceInterface::TARGET_DATABASE);
        $this->assertEquals('media', BackupServiceInterface::TARGET_MEDIA);
        $this->assertEquals('private', BackupServiceInterface::TARGET_PRIVATE);
        $this->assertEquals('custom', BackupServiceInterface::TARGET_CUSTOM);
        $this->assertEquals('logs', BackupServiceInterface::TARGET_LOGS);
    }

    /**
     * 空の targets で失敗結果を返す
     */
    public function test_backup_with_empty_targets_returns_failure(): void
    {
        $result = $this->service->backup([]);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('No valid backup targets', $result->error);
    }

    /**
     * 不正な targets が無視される
     */
    public function test_backup_filters_invalid_targets(): void
    {
        $result = $this->service->backup(['invalid_target_xyz']);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('No valid backup targets', $result->error);
    }

    /**
     * データベースバックアップを実行できる
     */
    public function test_backup_database_creates_zip_with_sql(): void
    {
        $result = $this->service->backup([BackupServiceInterface::TARGET_DATABASE]);

        $this->assertTrue($result->success, 'Backup failed: '.$result->error);
        $this->assertNotNull($result->backupRecordId);
        $this->assertNotNull($result->filePath);
        $this->assertFileExists($result->filePath);
        $this->assertGreaterThan(0, $result->fileSize);

        // ZIP 内容の確認
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($result->filePath) === true);
        $this->assertNotFalse($zip->locateName('database.sql'));
        $this->assertNotFalse($zip->locateName('manifest.json'));
        $zip->close();
    }

    /**
     * BackupRecord が正しく作成される
     */
    public function test_backup_creates_backup_record(): void
    {
        $result = $this->service->backup([BackupServiceInterface::TARGET_DATABASE]);

        $this->assertTrue($result->success);

        $record = BackupRecord::find($result->backupRecordId);
        $this->assertNotNull($record);
        $this->assertEquals('core', $record->plugin_slug);
        $this->assertEquals(BackupRecord::TYPE_DATABASE, $record->type);
        $this->assertEquals(['database'], $record->targets);
        $this->assertEquals(BackupRecord::STATUS_COMPLETED, $record->status);
        $this->assertEquals('sha256', $record->hash_algorithm);
        $this->assertNotEmpty($record->hash);
        $this->assertFalse($record->is_encrypted);
    }

    /**
     * リテンション期間オプションが反映される
     */
    public function test_backup_applies_retention_option(): void
    {
        $result = $this->service->backup(
            [BackupServiceInterface::TARGET_DATABASE],
            ['retention_days' => 30],
        );

        $this->assertTrue($result->success);
        $record = BackupRecord::find($result->backupRecordId);
        $this->assertNotNull($record->retention_until);
        $this->assertTrue($record->retention_until->isFuture());
    }

    /**
     * カスタムディレクトリのバックアップ
     */
    public function test_backup_custom_directory(): void
    {
        // custom/ ディレクトリにテストファイルを配置
        $customDir = base_path('custom');
        $testFile = $customDir.'/backup-test-'.uniqid().'.txt';
        if (! is_dir($customDir)) {
            mkdir($customDir, 0755, true);
        }
        file_put_contents($testFile, 'test content');

        try {
            $result = $this->service->backup([BackupServiceInterface::TARGET_CUSTOM]);

            $this->assertTrue($result->success, 'Backup failed: '.$result->error);

            $zip = new \ZipArchive();
            $zip->open($result->filePath);
            $entries = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entries[] = $zip->getNameIndex($i);
            }
            $zip->close();

            // custom/ プレフィックスが付いたエントリがあること
            $hasCustomEntry = false;
            foreach ($entries as $entry) {
                if (str_starts_with($entry, 'custom/')) {
                    $hasCustomEntry = true;
                    break;
                }
            }
            $this->assertTrue($hasCustomEntry, 'No custom/ entries found in backup');
        } finally {
            @unlink($testFile);
        }
    }

    /**
     * delete() でファイルとレコードが削除される
     */
    public function test_delete_removes_file_and_marks_record(): void
    {
        $result = $this->service->backup([BackupServiceInterface::TARGET_DATABASE]);
        $this->assertTrue($result->success);

        $record = BackupRecord::find($result->backupRecordId);
        $filePath = $record->file_path;
        $this->assertFileExists($filePath);

        $deleted = $this->service->delete($record);

        $this->assertTrue($deleted);
        $this->assertFileDoesNotExist($filePath);
        $record->refresh();
        $this->assertEquals(BackupRecord::STATUS_DELETED, $record->status);
    }

    /**
     * delete() でファイルが既に存在しない場合もレコードはマークされる
     */
    public function test_delete_marks_record_when_file_missing(): void
    {
        $record = BackupRecord::create([
            'plugin_slug' => 'core',
            'type' => BackupRecord::TYPE_DATABASE,
            'targets' => ['database'],
            'file_path' => '/nonexistent/path/backup.zip',
            'file_name' => 'backup.zip',
            'file_size' => 100,
            'is_encrypted' => false,
            'verification_status' => BackupRecord::VERIFICATION_UNCHECKED,
            'status' => BackupRecord::STATUS_COMPLETED,
        ]);

        $deleted = $this->service->delete($record);

        $this->assertTrue($deleted);
        $record->refresh();
        $this->assertEquals(BackupRecord::STATUS_DELETED, $record->status);
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
