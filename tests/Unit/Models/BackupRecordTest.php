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

namespace Tests\Unit\Models;

use App\Models\BackupRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupRecordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * レコード作成テスト
     */
    public function test_create_backup_record(): void
    {
        $record = BackupRecord::create([
            'plugin_slug' => 'dixlase-backup',
            'type' => BackupRecord::TYPE_FULL,
            'targets' => ['core', 'database', 'plugins'],
            'file_path' => '/backups/2026-04-08_full.zip',
            'file_name' => '2026-04-08_full.zip',
            'file_size' => 1048576,
            'is_encrypted' => false,
            'verification_status' => BackupRecord::VERIFICATION_UNCHECKED,
            'status' => BackupRecord::STATUS_COMPLETED,
            'metadata' => ['duration' => 12.5, 'table_count' => 20],
        ]);

        $this->assertDatabaseHas('backup_records', [
            'plugin_slug' => 'dixlase-backup',
            'type' => 'full',
            'file_name' => '2026-04-08_full.zip',
        ]);

        $this->assertEquals(['core', 'database', 'plugins'], $record->targets);
        $this->assertFalse($record->is_encrypted);
    }

    /**
     * markAsVerified のテスト
     */
    public function test_mark_as_verified(): void
    {
        $record = $this->createRecord();

        $record->markAsVerified();
        $record->refresh();

        $this->assertEquals(BackupRecord::VERIFICATION_VALID, $record->verification_status);
        $this->assertNotNull($record->last_verified_at);
    }

    /**
     * markAsInvalid のテスト
     */
    public function test_mark_as_invalid(): void
    {
        $record = $this->createRecord();

        $record->markAsInvalid();
        $record->refresh();

        $this->assertEquals(BackupRecord::VERIFICATION_INVALID, $record->verification_status);
        $this->assertNotNull($record->last_verified_at);
    }

    /**
     * markAsExpired のテスト
     */
    public function test_mark_as_expired(): void
    {
        $record = $this->createRecord();

        $record->markAsExpired();
        $record->refresh();

        $this->assertEquals(BackupRecord::STATUS_EXPIRED, $record->status);
    }

    /**
     * markAsDeleted のテスト
     */
    public function test_mark_as_deleted(): void
    {
        $record = $this->createRecord();

        $record->markAsDeleted();
        $record->refresh();

        $this->assertEquals(BackupRecord::STATUS_DELETED, $record->status);
    }

    /**
     * isExpired の動作テスト：期限切れ
     */
    public function test_is_expired_returns_true_when_past(): void
    {
        $record = $this->createRecord([
            'retention_until' => now()->subDay(),
        ]);

        $this->assertTrue($record->isExpired());
    }

    /**
     * isExpired の動作テスト：期限内
     */
    public function test_is_expired_returns_false_when_future(): void
    {
        $record = $this->createRecord([
            'retention_until' => now()->addDay(),
        ]);

        $this->assertFalse($record->isExpired());
    }

    /**
     * isExpired の動作テスト：retention_until が null
     */
    public function test_is_expired_returns_false_when_null(): void
    {
        $record = $this->createRecord([
            'retention_until' => null,
        ]);

        $this->assertFalse($record->isExpired());
    }

    /**
     * scopeForPlugin のテスト
     */
    public function test_scope_for_plugin(): void
    {
        $this->createRecord(['plugin_slug' => 'dixlase-backup']);
        $this->createRecord(['plugin_slug' => 'other-backup']);
        $this->createRecord(['plugin_slug' => 'dixlase-backup']);

        $records = BackupRecord::forPlugin('dixlase-backup')->get();

        $this->assertCount(2, $records);
    }

    /**
     * scopeEncrypted のテスト
     */
    public function test_scope_encrypted(): void
    {
        $this->createRecord(['is_encrypted' => true]);
        $this->createRecord(['is_encrypted' => false]);
        $this->createRecord(['is_encrypted' => true]);

        $records = BackupRecord::encrypted()->get();

        $this->assertCount(2, $records);
    }

    /**
     * scopeValid のテスト
     */
    public function test_scope_valid(): void
    {
        $this->createRecord(['verification_status' => BackupRecord::VERIFICATION_VALID]);
        $this->createRecord(['verification_status' => BackupRecord::VERIFICATION_UNCHECKED]);
        $this->createRecord(['verification_status' => BackupRecord::VERIFICATION_INVALID]);

        $records = BackupRecord::valid()->get();

        $this->assertCount(1, $records);
    }

    /**
     * scopeExpired のテスト
     */
    public function test_scope_expired(): void
    {
        $this->createRecord(['retention_until' => now()->subDay()]);
        $this->createRecord(['retention_until' => now()->addDay()]);
        $this->createRecord(['retention_until' => null]);

        $records = BackupRecord::expired()->get();

        $this->assertCount(1, $records);
    }

    /**
     * scopeByType のテスト
     */
    public function test_scope_by_type(): void
    {
        $this->createRecord(['type' => BackupRecord::TYPE_FILES]);
        $this->createRecord(['type' => BackupRecord::TYPE_DATABASE]);
        $this->createRecord(['type' => BackupRecord::TYPE_FULL]);

        $this->assertCount(1, BackupRecord::byType(BackupRecord::TYPE_FILES)->get());
        $this->assertCount(1, BackupRecord::byType(BackupRecord::TYPE_DATABASE)->get());
        $this->assertCount(1, BackupRecord::byType(BackupRecord::TYPE_FULL)->get());
    }

    /**
     * getMetadataValue のテスト
     */
    public function test_get_metadata_value(): void
    {
        $record = $this->createRecord([
            'metadata' => ['duration' => 12.5, 'table_count' => 20],
        ]);

        $this->assertEquals(12.5, $record->getMetadataValue('duration'));
        $this->assertEquals(20, $record->getMetadataValue('table_count'));
        $this->assertNull($record->getMetadataValue('nonexistent'));
        $this->assertEquals('default', $record->getMetadataValue('nonexistent', 'default'));
    }

    /**
     * getMetadataValue で metadata が null の場合
     */
    public function test_get_metadata_value_with_null_metadata(): void
    {
        $record = $this->createRecord(['metadata' => null]);

        $this->assertNull($record->getMetadataValue('any_key'));
        $this->assertEquals('fallback', $record->getMetadataValue('any_key', 'fallback'));
    }

    /**
     * ステータス定数の値を確認
     */
    public function test_status_constants(): void
    {
        $this->assertEquals('completed', BackupRecord::STATUS_COMPLETED);
        $this->assertEquals('failed', BackupRecord::STATUS_FAILED);
        $this->assertEquals('expired', BackupRecord::STATUS_EXPIRED);
        $this->assertEquals('deleted', BackupRecord::STATUS_DELETED);
    }

    /**
     * 検証ステータス定数の値を確認
     */
    public function test_verification_status_constants(): void
    {
        $this->assertEquals('unchecked', BackupRecord::VERIFICATION_UNCHECKED);
        $this->assertEquals('valid', BackupRecord::VERIFICATION_VALID);
        $this->assertEquals('invalid', BackupRecord::VERIFICATION_INVALID);
    }

    /**
     * タイプ定数の値を確認
     */
    public function test_type_constants(): void
    {
        $this->assertEquals('files', BackupRecord::TYPE_FILES);
        $this->assertEquals('database', BackupRecord::TYPE_DATABASE);
        $this->assertEquals('full', BackupRecord::TYPE_FULL);
    }

    /**
     * テスト用 BackupRecord を作成するヘルパー
     */
    private function createRecord(array $overrides = []): BackupRecord
    {
        return BackupRecord::create(array_merge([
            'plugin_slug' => 'dixlase-backup',
            'type' => BackupRecord::TYPE_FULL,
            'targets' => ['core', 'database'],
            'file_path' => '/backups/test_'.uniqid().'.zip',
            'file_name' => 'test.zip',
            'file_size' => 1048576,
            'is_encrypted' => false,
            'verification_status' => BackupRecord::VERIFICATION_UNCHECKED,
            'status' => BackupRecord::STATUS_COMPLETED,
        ], $overrides));
    }
}
