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

use App\DTO\PluginIntegration\DashboardNotificationDTO;
use PHPUnit\Framework\TestCase;

class DashboardNotificationDTOTest extends TestCase
{
    /**
     * DTO can be created with all parameters
     */
    public function test_can_create_with_all_parameters(): void
    {
        $dto = new DashboardNotificationDTO(
            key: 'test_key',
            level: 'warning',
            message: 'Test message',
            icon: 'fas fa-exclamation-triangle',
            pluginName: 'Test Plugin',
            url: '/admin/settings',
            actionLabel: 'Fix now',
        );

        $this->assertEquals('test_key', $dto->key);
        $this->assertEquals('warning', $dto->level);
        $this->assertEquals('Test message', $dto->message);
        $this->assertEquals('fas fa-exclamation-triangle', $dto->icon);
        $this->assertEquals('Test Plugin', $dto->pluginName);
        $this->assertEquals('/admin/settings', $dto->url);
        $this->assertEquals('Fix now', $dto->actionLabel);
        $this->assertEquals(DashboardNotificationDTO::SOURCE_PLUGIN, $dto->source);
    }

    /**
     * DTO can be created with optional parameters as null
     */
    public function test_can_create_with_optional_nulls(): void
    {
        $dto = new DashboardNotificationDTO(
            key: 'test_key',
            level: 'recommendation',
            message: 'Test message',
            icon: 'fas fa-robot',
            pluginName: 'Test Plugin',
        );

        $this->assertNull($dto->url);
        $this->assertNull($dto->actionLabel);
        $this->assertEquals(DashboardNotificationDTO::SOURCE_PLUGIN, $dto->source);
    }

    /**
     * DTO にカスタムソースを指定できること
     */
    public function test_can_create_with_custom_source(): void
    {
        $dto = new DashboardNotificationDTO(
            key: 'remote_update',
            level: 'info',
            message: 'New version available',
            icon: 'fas fa-cloud-download-alt',
            pluginName: 'System',
            source: DashboardNotificationDTO::SOURCE_REMOTE,
        );

        $this->assertEquals(DashboardNotificationDTO::SOURCE_REMOTE, $dto->source);
    }

    /**
     * DTO is readonly
     */
    public function test_dto_is_readonly(): void
    {
        $reflection = new \ReflectionClass(DashboardNotificationDTO::class);

        $this->assertTrue($reflection->isReadOnly());
    }
}
