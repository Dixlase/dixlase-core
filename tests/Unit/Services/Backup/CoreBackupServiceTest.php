<?php

namespace Tests\Unit\Services\Backup;

use App\Contracts\Backup\BackupServiceInterface;
use App\DTO\Backup\BackupResultDTO;
use App\Services\Backup\CoreBackupService;
use Tests\TestCase;

class CoreBackupServiceTest extends TestCase
{
    private CoreBackupService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CoreBackupService();
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
     * Phase A スケルトン: backup() は未実装失敗を返す
     */
    public function test_backup_returns_not_implemented_failure(): void
    {
        $result = $this->service->backup([BackupServiceInterface::TARGET_DATABASE]);

        $this->assertInstanceOf(BackupResultDTO::class, $result);
        $this->assertFalse($result->success);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('not yet implemented', $result->error);
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
}
