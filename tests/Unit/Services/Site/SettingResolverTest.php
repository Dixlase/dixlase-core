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

declare(strict_types=1);

namespace Tests\Unit\Services\Site;

use App\Enums\SettingScope;
use App\Models\GlobalSetting;
use App\Models\Site;
use App\Models\SiteSetting;
use App\Services\Site\Exceptions\UnknownSettingException;
use App\Services\Site\SettingDefinition;
use App\Services\Site\SettingDefinitionRegistry;
use App\Services\Site\SettingResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class SettingResolverTest extends TestCase
{
    use RefreshDatabase;

    private SettingDefinitionRegistry $registry;

    private SettingResolver $resolver;

    private Site $primarySite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->primarySite = Site::create([
            'slug' => 'main',
            'name' => 'Main Site',
            'primary_locale' => 'en',
            'timezone' => 'UTC',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->registry = app(SettingDefinitionRegistry::class);
        $this->resolver = app(SettingResolver::class);
    }

    public function test_get_throws_when_key_is_not_registered(): void
    {
        $this->expectException(UnknownSettingException::class);

        $this->resolver->get('unknown.key');
    }

    public function test_get_returns_default_when_no_row_exists(): void
    {
        $this->registry->register(new SettingDefinition(
            name: 'test.global',
            scope: SettingScope::Global,
            default: 'fallback',
        ));

        $this->assertSame('fallback', $this->resolver->get('test.global'));
    }

    public function test_get_returns_global_value_for_global_scope(): void
    {
        $this->registry->register(new SettingDefinition(
            name: 'test.global',
            scope: SettingScope::Global,
            default: 'fallback',
        ));
        GlobalSetting::create(['name' => 'test.global', 'value' => 'stored']);

        $this->assertSame('stored', $this->resolver->get('test.global'));
    }

    public function test_get_returns_per_site_value_for_per_site_scope(): void
    {
        $this->registry->register(new SettingDefinition(
            name: 'test.persite',
            scope: SettingScope::PerSite,
            default: 'fallback',
        ));
        SiteSetting::query()->withoutGlobalScope('belongs_to_site')->create([
            'site_id' => $this->primarySite->id,
            'name' => 'test.persite',
            'value' => 'site-value',
        ]);

        $this->assertSame('site-value', $this->resolver->get('test.persite'));
    }

    public function test_overridable_prefers_per_site_over_global(): void
    {
        $this->registry->register(new SettingDefinition(
            name: 'test.over',
            scope: SettingScope::Overridable,
            default: 'fallback',
        ));
        GlobalSetting::create(['name' => 'test.over', 'value' => 'global-value']);
        SiteSetting::query()->withoutGlobalScope('belongs_to_site')->create([
            'site_id' => $this->primarySite->id,
            'name' => 'test.over',
            'value' => 'site-value',
        ]);

        $this->assertSame('site-value', $this->resolver->get('test.over'));
    }

    public function test_overridable_falls_back_to_global_when_no_per_site_row(): void
    {
        $this->registry->register(new SettingDefinition(
            name: 'test.over',
            scope: SettingScope::Overridable,
            default: 'fallback',
        ));
        GlobalSetting::create(['name' => 'test.over', 'value' => 'global-value']);

        $this->assertSame('global-value', $this->resolver->get('test.over'));
    }

    public function test_overridable_falls_back_to_default_when_neither_row_exists(): void
    {
        $this->registry->register(new SettingDefinition(
            name: 'test.over',
            scope: SettingScope::Overridable,
            default: 'fallback',
        ));

        $this->assertSame('fallback', $this->resolver->get('test.over'));
    }

    public function test_set_writes_to_global_for_global_scope(): void
    {
        $this->registry->register(new SettingDefinition(
            name: 'test.global',
            scope: SettingScope::Global,
        ));

        $this->resolver->set('test.global', 'value');

        $this->assertDatabaseHas('global_settings', ['name' => 'test.global', 'value' => 'value']);
        $this->assertDatabaseMissing('site_settings', ['name' => 'test.global']);
    }

    public function test_set_writes_to_per_site_for_per_site_scope(): void
    {
        $this->registry->register(new SettingDefinition(
            name: 'test.persite',
            scope: SettingScope::PerSite,
        ));

        $this->resolver->set('test.persite', 'value');

        $this->assertDatabaseHas('site_settings', [
            'name' => 'test.persite',
            'value' => 'value',
            'site_id' => $this->primarySite->id,
        ]);
        $this->assertDatabaseMissing('global_settings', ['name' => 'test.persite']);
    }

    public function test_set_overridable_without_site_id_writes_globally(): void
    {
        $this->registry->register(new SettingDefinition(
            name: 'test.over',
            scope: SettingScope::Overridable,
        ));

        $this->resolver->set('test.over', 'global-value');

        $this->assertDatabaseHas('global_settings', ['name' => 'test.over', 'value' => 'global-value']);
        $this->assertDatabaseMissing('site_settings', ['name' => 'test.over']);
    }

    public function test_set_overridable_with_site_id_writes_per_site(): void
    {
        $this->registry->register(new SettingDefinition(
            name: 'test.over',
            scope: SettingScope::Overridable,
        ));

        $this->resolver->set('test.over', 'site-value', $this->primarySite->id);

        $this->assertDatabaseHas('site_settings', [
            'name' => 'test.over',
            'value' => 'site-value',
            'site_id' => $this->primarySite->id,
        ]);
        $this->assertDatabaseMissing('global_settings', ['name' => 'test.over']);
    }

    public function test_set_global_rejects_per_site_scope(): void
    {
        $this->registry->register(new SettingDefinition(
            name: 'test.persite',
            scope: SettingScope::PerSite,
        ));

        $this->expectException(InvalidArgumentException::class);
        $this->resolver->setGlobal('test.persite', 'value');
    }

    public function test_set_for_site_rejects_global_scope(): void
    {
        $this->registry->register(new SettingDefinition(
            name: 'test.global',
            scope: SettingScope::Global,
        ));

        $this->expectException(InvalidArgumentException::class);
        $this->resolver->setForSite('test.global', 'value', $this->primarySite->id);
    }

    public function test_delete_overridable_with_site_id_clears_per_site_only(): void
    {
        $this->registry->register(new SettingDefinition(
            name: 'test.over',
            scope: SettingScope::Overridable,
        ));
        GlobalSetting::create(['name' => 'test.over', 'value' => 'global-value']);
        SiteSetting::query()->withoutGlobalScope('belongs_to_site')->create([
            'site_id' => $this->primarySite->id,
            'name' => 'test.over',
            'value' => 'site-value',
        ]);

        $this->resolver->delete('test.over', $this->primarySite->id);

        $this->assertDatabaseMissing('site_settings', ['name' => 'test.over']);
        $this->assertDatabaseHas('global_settings', ['name' => 'test.over', 'value' => 'global-value']);
    }

    public function test_delete_overridable_without_site_id_clears_global_only(): void
    {
        $this->registry->register(new SettingDefinition(
            name: 'test.over',
            scope: SettingScope::Overridable,
        ));
        GlobalSetting::create(['name' => 'test.over', 'value' => 'global-value']);
        SiteSetting::query()->withoutGlobalScope('belongs_to_site')->create([
            'site_id' => $this->primarySite->id,
            'name' => 'test.over',
            'value' => 'site-value',
        ]);

        $this->resolver->delete('test.over');

        $this->assertDatabaseMissing('global_settings', ['name' => 'test.over']);
        $this->assertDatabaseHas('site_settings', ['name' => 'test.over']);
    }

    public function test_int_type_is_cast(): void
    {
        $this->registry->register(new SettingDefinition(
            name: 'test.count',
            scope: SettingScope::Global,
            type: 'int',
        ));
        GlobalSetting::create(['name' => 'test.count', 'value' => '42']);

        $this->assertSame(42, $this->resolver->get('test.count'));
    }

    public function test_bool_type_is_cast(): void
    {
        $this->registry->register(new SettingDefinition(
            name: 'test.enabled',
            scope: SettingScope::Global,
            type: 'bool',
        ));
        GlobalSetting::create(['name' => 'test.enabled', 'value' => '1']);

        $this->assertTrue($this->resolver->get('test.enabled'));
    }

    public function test_array_type_is_cast_from_json(): void
    {
        $this->registry->register(new SettingDefinition(
            name: 'test.list',
            scope: SettingScope::Global,
            type: 'array',
        ));
        GlobalSetting::create(['name' => 'test.list', 'value' => '["a","b","c"]']);

        $this->assertSame(['a', 'b', 'c'], $this->resolver->get('test.list'));
    }

    public function test_array_value_is_serialized_to_json_on_write(): void
    {
        $this->registry->register(new SettingDefinition(
            name: 'test.list',
            scope: SettingScope::Global,
            type: 'array',
        ));

        $this->resolver->set('test.list', ['x', 'y']);

        $this->assertDatabaseHas('global_settings', [
            'name' => 'test.list',
            'value' => '["x","y"]',
        ]);
    }
}
