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

namespace Tests\Unit\Services;

use App\Enums\SafeMode;
use App\Services\SafeModeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SafeModeServiceTest extends TestCase
{
    use RefreshDatabase;

    private SafeModeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SafeModeService::class);
    }

    public function test_activate_sets_session_flag(): void
    {
        $this->service->activate(SafeMode::Csp);

        $this->assertTrue($this->service->isActive(SafeMode::Csp));
    }

    public function test_deactivate_clears_session_flag(): void
    {
        $this->service->activate(SafeMode::Csp);
        $this->service->deactivate(SafeMode::Csp);

        $this->assertFalse($this->service->isActive(SafeMode::Csp));
    }

    public function test_multiple_modes_can_be_active(): void
    {
        $this->service->activate(SafeMode::Csp);
        $this->service->activate(SafeMode::Plugins);

        $this->assertTrue($this->service->isActive(SafeMode::Csp));
        $this->assertTrue($this->service->isActive(SafeMode::Plugins));
    }

    public function test_deactivate_all_clears_all_modes(): void
    {
        $this->service->activate(SafeMode::Csp);
        $this->service->activate(SafeMode::Plugins);

        $this->service->deactivateAll();

        $this->assertFalse($this->service->isActive(SafeMode::Csp));
        $this->assertFalse($this->service->isActive(SafeMode::Plugins));
    }

    public function test_has_any_active(): void
    {
        $this->assertFalse($this->service->hasAnyActive());

        $this->service->activate(SafeMode::Csp);

        $this->assertTrue($this->service->hasAnyActive());
    }

    public function test_get_active_modes(): void
    {
        $this->service->activate(SafeMode::Csp);
        $this->service->activate(SafeMode::Plugins);

        $modes = $this->service->getActiveModes();

        $this->assertCount(2, $modes);
    }

    public function test_is_active_returns_false_for_inactive_mode(): void
    {
        $this->assertFalse($this->service->isActive(SafeMode::Csp));
    }
}
