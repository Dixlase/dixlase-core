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

namespace Tests\Unit\Services\Plugin;

use App\Services\Plugin\PluginPermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PluginPermissionServiceOptionalTest extends TestCase
{
    use RefreshDatabase;

    protected PluginPermissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PluginPermissionService();
    }

    /**
     * _optional が正しく取得されるテスト
     */
    public function test_get_optional_permissions(): void
    {
        $pluginDir = $this->createTempPlugin([
            'database' => ['own_tables' => true, 'core_tables_read' => [], 'core_tables_write' => []],
            'mail' => ['send' => true, 'bulk_send' => false],
            '_optional' => ['mail.send'],
            '_notes' => ['ja' => 'テスト', 'en' => 'Test'],
        ]);

        try {
            $optional = $this->service->getOptionalPermissions('test-plugin');

            $this->assertEquals(['mail.send'], $optional);
        } finally {
            File::deleteDirectory($pluginDir);
        }
    }

    /**
     * _notes が正しく取得されるテスト
     */
    public function test_get_permission_notes(): void
    {
        $pluginDir = $this->createTempPlugin([
            'database' => ['own_tables' => true, 'core_tables_read' => [], 'core_tables_write' => []],
            '_optional' => [],
            '_notes' => ['ja' => 'テスト説明', 'en' => 'Test description'],
        ]);

        try {
            $notes = $this->service->getPermissionNotes('test-plugin');

            $this->assertEquals('テスト説明', $notes['ja']);
            $this->assertEquals('Test description', $notes['en']);
        } finally {
            File::deleteDirectory($pluginDir);
        }
    }

    /**
     * _optional がない場合は空配列を返すテスト
     */
    public function test_get_optional_returns_empty_when_not_defined(): void
    {
        $pluginDir = $this->createTempPlugin([
            'database' => ['own_tables' => true, 'core_tables_read' => [], 'core_tables_write' => []],
        ]);

        try {
            $optional = $this->service->getOptionalPermissions('test-plugin');

            $this->assertEquals([], $optional);
        } finally {
            File::deleteDirectory($pluginDir);
        }
    }

    /**
     * isOptionalPermission() のテスト
     */
    public function test_is_optional_permission(): void
    {
        $pluginDir = $this->createTempPlugin([
            'database' => ['own_tables' => true, 'core_tables_read' => [], 'core_tables_write' => []],
            'mail' => ['send' => true, 'bulk_send' => false],
            '_optional' => ['mail.send', 'storage.temp_files'],
        ]);

        try {
            $this->assertTrue($this->service->isOptionalPermission('test-plugin', 'mail.send'));
            $this->assertTrue($this->service->isOptionalPermission('test-plugin', 'storage.temp_files'));
            $this->assertFalse($this->service->isOptionalPermission('test-plugin', 'database.own_tables'));
        } finally {
            File::deleteDirectory($pluginDir);
        }
    }

    /**
     * mergeWithDefaults が _optional と _notes を保持するテスト
     */
    public function test_merge_preserves_optional_and_notes(): void
    {
        $pluginDir = $this->createTempPlugin([
            'database' => ['own_tables' => true, 'core_tables_read' => [], 'core_tables_write' => []],
            '_optional' => ['mail.send'],
            '_notes' => ['ja' => 'テスト', 'en' => 'Test'],
        ]);

        try {
            // getPermissions() はマージ済みのデータを返す
            $permissions = $this->service->getPermissions('test-plugin');

            // マージ後も _optional と _notes が保持されていること
            $this->assertArrayHasKey('_optional', $permissions);
            $this->assertArrayHasKey('_notes', $permissions);
            $this->assertEquals(['mail.send'], $permissions['_optional']);
            $this->assertEquals(['ja' => 'テスト', 'en' => 'Test'], $permissions['_notes']);

            // デフォルトの権限もマージされていること
            $this->assertArrayHasKey('storage', $permissions);
            $this->assertArrayHasKey('members', $permissions);
        } finally {
            File::deleteDirectory($pluginDir);
        }
    }

    /**
     * getSummary() が optional と notes を含むテスト
     */
    public function test_get_summary_includes_optional_and_notes(): void
    {
        $pluginDir = $this->createTempPlugin([
            'database' => ['own_tables' => true, 'core_tables_read' => [], 'core_tables_write' => []],
            '_optional' => ['mail.send'],
            '_notes' => ['ja' => 'テスト', 'en' => 'Test'],
        ]);

        try {
            $summary = $this->service->getSummary('test-plugin');

            $this->assertArrayHasKey('optional', $summary);
            $this->assertArrayHasKey('notes', $summary);
            $this->assertEquals(['mail.send'], $summary['optional']);
            $this->assertEquals(['ja' => 'テスト', 'en' => 'Test'], $summary['notes']);
        } finally {
            File::deleteDirectory($pluginDir);
        }
    }

    /**
     * getSummary() のカテゴリに _optional と _notes が含まれないテスト
     */
    public function test_get_summary_categories_exclude_metadata(): void
    {
        $pluginDir = $this->createTempPlugin([
            'database' => ['own_tables' => true, 'core_tables_read' => [], 'core_tables_write' => []],
            'mail' => ['send' => true, 'bulk_send' => false],
            '_optional' => ['mail.send'],
            '_notes' => ['ja' => 'テスト', 'en' => 'Test'],
        ]);

        try {
            $summary = $this->service->getSummary('test-plugin');

            $this->assertArrayNotHasKey('_optional', $summary['categories']);
            $this->assertArrayNotHasKey('_notes', $summary['categories']);
            $this->assertArrayHasKey('database', $summary['categories']);
        } finally {
            File::deleteDirectory($pluginDir);
        }
    }

    /**
     * テスト用プラグインディレクトリを作成
     */
    protected function createTempPlugin(array $permissions): string
    {
        $pluginDir = base_path('plugins/TestPlugin');

        if (File::isDirectory($pluginDir)) {
            File::deleteDirectory($pluginDir);
        }

        File::makeDirectory($pluginDir, 0755, true);

        $pluginJson = [
            'name' => 'TestPlugin',
            'slug' => 'test-plugin',
            'version' => '1.0.0',
            'permissions' => $permissions,
        ];

        File::put(
            "{$pluginDir}/plugin.json",
            json_encode($pluginJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return $pluginDir;
    }
}
