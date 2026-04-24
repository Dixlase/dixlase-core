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

namespace Tests\Unit\Services\Plugin\Scanning;

use App\Services\Plugin\Scanning\DatabaseDetectionPattern;
use App\Services\Plugin\Scanning\PatternRegistry;
use App\Services\Plugin\Scanning\ThemeAssetDetectionPattern;
use Tests\TestCase;

class PatternRegistryTest extends TestCase
{
    /**
     * createDefault()が全パターンを登録するテスト
     */
    public function test_create_default_registers_all_patterns(): void
    {
        $registry = PatternRegistry::createDefault();
        $all = $registry->all();

        // 主要な権限キーが存在することを確認
        $this->assertArrayHasKey('database.own_tables', $all);
        $this->assertArrayHasKey('database.core_tables_read', $all);
        $this->assertArrayHasKey('database.core_tables_write', $all);
        $this->assertArrayHasKey('storage.own_directory', $all);
        $this->assertArrayHasKey('members.read', $all);
        $this->assertArrayHasKey('mail.send', $all);
        $this->assertArrayHasKey('system.register_middleware', $all);
        $this->assertArrayHasKey('dangerous_api.exec', $all);
        $this->assertArrayHasKey('assets.custom_css', $all);
    }

    /**
     * パターン登録と取得のテスト
     */
    public function test_register_and_get_pattern(): void
    {
        $registry = new PatternRegistry();
        $pattern = new DatabaseDetectionPattern('own_tables');

        $registry->register($pattern);

        $all = $registry->all();
        $this->assertCount(1, $all);
        $this->assertArrayHasKey('database.own_tables', $all);
    }

    /**
     * getPatternsFor()がプラグイン用パターンを返すテスト
     */
    public function test_get_patterns_for_plugin(): void
    {
        $registry = new PatternRegistry();
        $registry->register(new DatabaseDetectionPattern('own_tables')); // both
        $registry->register(new ThemeAssetDetectionPattern('custom_css')); // theme only

        $pluginPatterns = $registry->getPatternsFor('plugin');

        $this->assertCount(1, $pluginPatterns);
        $this->assertArrayHasKey('database.own_tables', $pluginPatterns);
        $this->assertArrayNotHasKey('assets.custom_css', $pluginPatterns);
    }

    /**
     * getPatternsFor()がテーマ用パターンを返すテスト
     */
    public function test_get_patterns_for_theme(): void
    {
        $registry = new PatternRegistry();
        $registry->register(new DatabaseDetectionPattern('own_tables')); // both
        $registry->register(new ThemeAssetDetectionPattern('custom_css')); // theme only

        $themePatterns = $registry->getPatternsFor('theme');

        $this->assertCount(2, $themePatterns);
        $this->assertArrayHasKey('database.own_tables', $themePatterns);
        $this->assertArrayHasKey('assets.custom_css', $themePatterns);
    }

    /**
     * createDefault()のプラグイン用にテーマ専用が含まれないテスト
     */
    public function test_default_plugin_patterns_exclude_theme_only(): void
    {
        $registry = PatternRegistry::createDefault();
        $pluginPatterns = $registry->getPatternsFor('plugin');

        $this->assertArrayNotHasKey('assets.custom_css', $pluginPatterns);
        $this->assertArrayNotHasKey('assets.custom_js', $pluginPatterns);
        $this->assertArrayNotHasKey('assets.external_resources', $pluginPatterns);
    }

    /**
     * createDefault()のテーマ用にテーマ専用が含まれるテスト
     */
    public function test_default_theme_patterns_include_theme_assets(): void
    {
        $registry = PatternRegistry::createDefault();
        $themePatterns = $registry->getPatternsFor('theme');

        $this->assertArrayHasKey('assets.custom_css', $themePatterns);
        $this->assertArrayHasKey('assets.custom_js', $themePatterns);
        $this->assertArrayHasKey('assets.external_resources', $themePatterns);
    }

    /**
     * 同じキーのパターンを再登録すると上書きされるテスト
     */
    public function test_register_overwrites_existing_pattern(): void
    {
        $registry = new PatternRegistry();
        $pattern1 = new DatabaseDetectionPattern('own_tables');
        $pattern2 = new DatabaseDetectionPattern('own_tables');

        $registry->register($pattern1);
        $registry->register($pattern2);

        $this->assertCount(1, $registry->all());
    }
}
