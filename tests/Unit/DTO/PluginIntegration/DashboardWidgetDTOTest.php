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

namespace Tests\Unit\DTO\PluginIntegration;

use App\DTO\PluginIntegration\DashboardWidgetDTO;
use PHPUnit\Framework\TestCase;

class DashboardWidgetDTOTest extends TestCase
{
    /**
     * 全プロパティ指定でDTOを生成できることを確認
     */
    public function test_can_create_with_all_properties(): void
    {
        $dto = new DashboardWidgetDTO(
            key: 'pages_count',
            label: 'Pages',
            value: 12,
            icon: 'fas fa-file-alt',
            url: '/admin/pages',
            description: 'Total published pages',
            color: 'green',
        );

        $this->assertEquals('pages_count', $dto->key);
        $this->assertEquals('Pages', $dto->label);
        $this->assertEquals(12, $dto->value);
        $this->assertEquals('fas fa-file-alt', $dto->icon);
        $this->assertEquals('/admin/pages', $dto->url);
        $this->assertEquals('Total published pages', $dto->description);
        $this->assertEquals('green', $dto->color);
    }

    /**
     * デフォルト値でDTOを生成できることを確認
     */
    public function test_can_create_with_defaults(): void
    {
        $dto = new DashboardWidgetDTO(
            key: 'posts',
            label: 'Posts',
            value: 0,
            icon: 'fas fa-pencil-alt',
        );

        $this->assertNull($dto->url);
        $this->assertNull($dto->description);
        $this->assertEquals('blue', $dto->color);
    }

    /**
     * valueに文字列を使用できることを確認
     */
    public function test_value_accepts_string(): void
    {
        $dto = new DashboardWidgetDTO(
            key: 'status',
            label: 'Status',
            value: 'Active',
            icon: 'fas fa-check',
        );

        $this->assertEquals('Active', $dto->value);
    }

    /**
     * valueに整数を使用できることを確認
     */
    public function test_value_accepts_integer(): void
    {
        $dto = new DashboardWidgetDTO(
            key: 'count',
            label: 'Count',
            value: 42,
            icon: 'fas fa-hashtag',
        );

        $this->assertEquals(42, $dto->value);
    }

    /**
     * DTOがreadonlyであることを確認
     */
    public function test_dto_is_readonly(): void
    {
        $reflection = new \ReflectionClass(DashboardWidgetDTO::class);

        $this->assertTrue($reflection->isReadOnly());
    }
}
