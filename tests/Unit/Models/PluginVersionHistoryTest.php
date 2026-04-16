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

use App\Models\PluginVersionHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PluginVersionHistoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 初回インストール時のレコードが作成できること
     */
    public function test_install_record_can_be_created(): void
    {
        PluginVersionHistory::create([
            'plugin_slug' => 'test-plugin',
            'old_version' => null,
            'new_version' => '1.0.0',
            'new_signing_key_id' => 'dixlase-authority-2026',
            'new_author_id' => 'exc-d-inc',
            'signing_key_changed' => false,
            'author_id_changed' => false,
            'installation_method' => PluginVersionHistory::METHOD_INSTALL,
            'applied_at' => now(),
        ]);

        $history = PluginVersionHistory::where('plugin_slug', 'test-plugin')->firstOrFail();

        $this->assertEquals('test-plugin', $history->plugin_slug);
        $this->assertNull($history->old_version);
        $this->assertEquals('1.0.0', $history->new_version);
        $this->assertEquals('install', $history->installation_method);
        $this->assertFalse($history->signing_key_changed);
        $this->assertFalse($history->author_id_changed);
    }

    /**
     * アップデート時のレコードで変更検出フラグが動作すること
     */
    public function test_update_record_tracks_signing_key_change(): void
    {
        $history = PluginVersionHistory::create([
            'plugin_slug' => 'test-plugin',
            'old_version' => '1.0.0',
            'new_version' => '1.1.0',
            'old_signing_key_id' => 'dixlase-authority-2025',
            'new_signing_key_id' => 'dixlase-authority-2026',
            'signing_key_changed' => true,
            'installation_method' => PluginVersionHistory::METHOD_UPDATE,
            'applied_at' => now(),
        ]);

        $this->assertTrue($history->signing_key_changed);
        $this->assertEquals('1.0.0', $history->old_version);
        $this->assertEquals('1.1.0', $history->new_version);
    }

    /**
     * author_id 変更フラグが動作すること
     */
    public function test_update_record_tracks_author_id_change(): void
    {
        $history = PluginVersionHistory::create([
            'plugin_slug' => 'test-plugin',
            'old_version' => '1.0.0',
            'new_version' => '2.0.0',
            'old_author_id' => 'original-author',
            'new_author_id' => 'new-author',
            'author_id_changed' => true,
            'installation_method' => PluginVersionHistory::METHOD_UPDATE,
            'applied_at' => now(),
        ]);

        $this->assertTrue($history->author_id_changed);
    }

    /**
     * メソッド定数が定義されていること
     */
    public function test_method_constants_are_defined(): void
    {
        $this->assertEquals('install', PluginVersionHistory::METHOD_INSTALL);
        $this->assertEquals('update', PluginVersionHistory::METHOD_UPDATE);
        $this->assertEquals('rollback', PluginVersionHistory::METHOD_ROLLBACK);
    }

    /**
     * キャストが正しく動作すること
     */
    public function test_boolean_casts_work(): void
    {
        PluginVersionHistory::create([
            'plugin_slug' => 'test-plugin',
            'new_version' => '1.0.0',
            'signing_key_changed' => 1,
            'author_id_changed' => 0,
            'installation_method' => PluginVersionHistory::METHOD_INSTALL,
            'applied_at' => now(),
        ]);

        $history = PluginVersionHistory::where('plugin_slug', 'test-plugin')->firstOrFail();

        $this->assertIsBool($history->signing_key_changed);
        $this->assertIsBool($history->author_id_changed);
        $this->assertTrue($history->signing_key_changed);
        $this->assertFalse($history->author_id_changed);
    }
}
