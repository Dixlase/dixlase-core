<?php

namespace Tests\Unit\Services\Backup;

use App\Contracts\Backup\RestoreServiceInterface;
use App\DTO\Backup\RestoreResultDTO;
use App\Models\BackupRecord;
use App\Services\Backup\CoreRestoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreRestoreServiceTest extends TestCase
{
    use RefreshDatabase;

    private CoreRestoreService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CoreRestoreService();
    }

    /**
     * RestoreServiceInterface を実装していることを確認
     */
    public function test_implements_interface(): void
    {
        $this->assertInstanceOf(RestoreServiceInterface::class, $this->service);
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
     * Phase A スケルトン: restore() は未実装失敗を返す
     */
    public function test_restore_returns_not_implemented_failure(): void
    {
        $backup = BackupRecord::create([
            'plugin_slug' => 'core',
            'type' => BackupRecord::TYPE_FULL,
            'targets' => ['database'],
            'file_path' => '/tmp/test.zip',
            'file_name' => 'test.zip',
            'file_size' => 100,
            'is_encrypted' => false,
            'verification_status' => BackupRecord::VERIFICATION_UNCHECKED,
            'status' => BackupRecord::STATUS_COMPLETED,
        ]);

        $result = $this->service->restore($backup);

        $this->assertInstanceOf(RestoreResultDTO::class, $result);
        $this->assertFalse($result->success);
        $this->assertStringContainsString('not yet implemented', $result->error);
    }
}
