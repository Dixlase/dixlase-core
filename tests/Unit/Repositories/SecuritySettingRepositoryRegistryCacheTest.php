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

namespace Tests\Unit\Repositories;

use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\Services\SecuritySettingsRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SecuritySettingsRegistry keeps a separate 300s read cache that the
 * extension audit / operation-status logic relies on. The settings pages
 * write through SecuritySettingRepository, not the registry, so the
 * repository must invalidate the registry cache on write — otherwise an
 * admin changing the CSP mode or extension security preset would not see
 * the new operation-status verdict on the extension cards for up to five
 * minutes.
 */
class SecuritySettingRepositoryRegistryCacheTest extends TestCase
{
    use RefreshDatabase;

    private SecuritySettingRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(SecuritySettingRepositoryInterface::class);
        SecuritySettingsRegistry::clearCache();
    }

    public function test_set_invalidates_registry_cache_for_csp_mode(): void
    {
        // Strict CSP mode (2).
        $this->repository->set('csp_mode', 2);

        // Prime the registry's 300s read cache with the current value.
        $this->assertSame(2, SecuritySettingsRegistry::get('csp_mode'));

        // An admin lowers the CSP mode back to development (0) through the
        // repository — the same path the security settings page uses.
        $this->repository->set('csp_mode', 0);

        // Without the registry cache invalidation in set(), this would
        // still return the stale 2 until the 300s TTL expired.
        $this->assertSame(0, SecuritySettingsRegistry::get('csp_mode'));
    }

    public function test_set_invalidates_registry_cache_for_extension_security_preset(): void
    {
        $this->repository->set('extension_security_preset', 'strict');
        $this->assertSame('strict', SecuritySettingsRegistry::get('extension_security_preset'));

        $this->repository->set('extension_security_preset', 'development');
        $this->assertSame('development', SecuritySettingsRegistry::get('extension_security_preset'));
    }

    public function test_delete_invalidates_registry_cache(): void
    {
        $this->repository->set('csp_mode', 2);
        $this->assertSame(2, SecuritySettingsRegistry::get('csp_mode'));

        $this->repository->delete('csp_mode');

        // After deletion the registry falls back to the definition default (1).
        $this->assertSame(1, SecuritySettingsRegistry::get('csp_mode'));
    }
}
