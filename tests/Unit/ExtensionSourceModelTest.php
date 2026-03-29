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

namespace Tests\Unit;

use App\Models\ExtensionSource;
use App\Models\Plugin;
use App\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtensionSourceModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_extension_source(): void
    {
        $source = ExtensionSource::query()->create([
            'name' => 'Test GitHub Source',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'owner' => 'TestOrg',
            'is_enabled' => true,
            'priority' => 0,
        ]);

        $this->assertDatabaseHas('extension_sources', [
            'name' => 'Test GitHub Source',
            'type' => 'github',
            'owner' => 'TestOrg',
        ]);
        $this->assertTrue($source->is_enabled);
        $source->refresh();
        $this->assertFalse($source->is_official);
    }

    public function test_auth_token_is_encrypted(): void
    {
        $source = ExtensionSource::query()->create([
            'name' => 'Token Test',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'auth_token' => 'ghp_test_token_123',
            'priority' => 0,
        ]);

        $source->refresh();
        $this->assertEquals('ghp_test_token_123', $source->auth_token);

        // Verify the raw DB value is not plain text
        $raw = \DB::table('extension_sources')->where('id', $source->id)->value('auth_token');
        $this->assertNotEquals('ghp_test_token_123', $raw);
    }

    public function test_auth_token_is_hidden_in_serialization(): void
    {
        $source = ExtensionSource::query()->create([
            'name' => 'Hidden Test',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'auth_token' => 'secret_token',
            'priority' => 0,
        ]);

        $array = $source->toArray();
        $this->assertArrayNotHasKey('auth_token', $array);
    }

    public function test_enabled_scope(): void
    {
        ExtensionSource::query()->create([
            'name' => 'Enabled',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'is_enabled' => true,
            'priority' => 10,
        ]);
        ExtensionSource::query()->create([
            'name' => 'Disabled',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'is_enabled' => false,
            'priority' => 0,
        ]);

        $enabled = ExtensionSource::query()->enabled()->get();
        $this->assertCount(1, $enabled);
        $this->assertEquals('Enabled', $enabled->first()->name);
    }

    public function test_official_scope(): void
    {
        ExtensionSource::query()->create([
            'name' => 'Official',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'is_official' => true,
            'priority' => 0,
        ]);
        ExtensionSource::query()->create([
            'name' => 'Unofficial',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'is_official' => false,
            'priority' => 0,
        ]);

        $official = ExtensionSource::query()->official()->get();
        $this->assertCount(1, $official);
        $this->assertEquals('Official', $official->first()->name);
    }

    public function test_of_type_scope(): void
    {
        ExtensionSource::query()->create([
            'name' => 'GitHub',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'priority' => 0,
        ]);
        ExtensionSource::query()->create([
            'name' => 'Custom',
            'type' => 'custom',
            'base_url' => 'https://example.com',
            'priority' => 0,
        ]);

        $github = ExtensionSource::query()->ofType('github')->get();
        $this->assertCount(1, $github);
        $this->assertEquals('GitHub', $github->first()->name);
    }

    public function test_has_authentication_helper(): void
    {
        $withToken = new ExtensionSource(['auth_token' => 'token']);
        $withoutToken = new ExtensionSource();

        $this->assertTrue($withToken->hasAuthentication());
        $this->assertFalse($withoutToken->hasAuthentication());
    }

    public function test_has_signature_helper(): void
    {
        $signed = new ExtensionSource(['official_signature' => 'sig']);
        $unsigned = new ExtensionSource();

        $this->assertTrue($signed->hasSignature());
        $this->assertFalse($unsigned->hasSignature());
    }

    public function test_mark_checked(): void
    {
        $source = ExtensionSource::query()->create([
            'name' => 'Check Test',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'priority' => 0,
        ]);

        $this->assertNull($source->last_checked_at);

        $source->markChecked();
        $source->refresh();

        $this->assertNotNull($source->last_checked_at);
    }

    public function test_settings_json_cast(): void
    {
        $source = ExtensionSource::query()->create([
            'name' => 'Settings Test',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'settings' => ['repo_prefix' => 'custom-', 'per_page' => 50],
            'priority' => 0,
        ]);

        $source->refresh();
        $this->assertIsArray($source->settings);
        $this->assertEquals('custom-', $source->settings['repo_prefix']);
        $this->assertEquals(50, $source->settings['per_page']);
    }

    public function test_plugins_relation(): void
    {
        $source = ExtensionSource::query()->create([
            'name' => 'Relation Test',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'priority' => 0,
        ]);

        Plugin::query()->create([
            'name' => 'TestPlugin',
            'directory' => 'TestPlugin',
            'slug' => 'test-plugin',
            'namespace' => 'Plugins\\TestPlugin',
            'version' => '1.0.0',
            'source_id' => $source->id,
        ]);

        $this->assertCount(1, $source->plugins);
        $this->assertEquals('test-plugin', $source->plugins->first()->slug);
    }

    public function test_themes_relation(): void
    {
        $source = ExtensionSource::query()->create([
            'name' => 'Theme Relation Test',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'priority' => 0,
        ]);

        Theme::query()->create([
            'name' => 'TestTheme',
            'directory' => 'TestTheme',
            'slug' => 'test-theme',
            'version' => '1.0.0',
            'source_id' => $source->id,
        ]);

        $this->assertCount(1, $source->themes);
        $this->assertEquals('test-theme', $source->themes->first()->slug);
    }

    public function test_priority_order_in_enabled_scope(): void
    {
        ExtensionSource::query()->create([
            'name' => 'Low Priority',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'is_enabled' => true,
            'priority' => 10,
        ]);
        ExtensionSource::query()->create([
            'name' => 'High Priority',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'is_enabled' => true,
            'priority' => 0,
        ]);

        $sources = ExtensionSource::query()->enabled()->get();
        $this->assertEquals('High Priority', $sources->first()->name);
        $this->assertEquals('Low Priority', $sources->last()->name);
    }
}
