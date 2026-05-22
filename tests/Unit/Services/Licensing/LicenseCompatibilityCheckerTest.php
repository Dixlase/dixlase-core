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

namespace Tests\Unit\Services\Licensing;

use App\Services\Licensing\LicenseCompatibilityChecker;
use App\Services\Licensing\LicenseValidator;
use Tests\TestCase;

class LicenseCompatibilityCheckerTest extends TestCase
{
    private LicenseCompatibilityChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->checker = new LicenseCompatibilityChecker(new LicenseValidator());
    }

    public function test_accepted_license_passes(): void
    {
        $result = $this->checker->evaluateForInstall('MIT');
        $this->assertSame(LicenseCompatibilityChecker::VERDICT_PASS, $result['verdict']);
    }

    public function test_missing_license_fails_by_default(): void
    {
        $result = $this->checker->evaluateForInstall(null);
        $this->assertSame(LicenseCompatibilityChecker::VERDICT_FAIL, $result['verdict']);
        $this->assertSame(LicenseValidator::STATUS_MISSING, $result['status']);
    }

    public function test_refused_license_fails(): void
    {
        $result = $this->checker->evaluateForInstall('GPL-2.0-only');
        $this->assertSame(LicenseCompatibilityChecker::VERDICT_FAIL, $result['verdict']);
        $this->assertSame(LicenseValidator::STATUS_REFUSED, $result['status']);
    }

    public function test_unknown_license_warns_but_passes_install(): void
    {
        // EUPL-1.2 is valid SPDX but not in the Phase 1 whitelist
        $result = $this->checker->evaluateForInstall('EUPL-1.2');
        $this->assertSame(LicenseCompatibilityChecker::VERDICT_WARN, $result['verdict']);
        $this->assertSame(LicenseValidator::STATUS_UNKNOWN, $result['status']);
    }

    public function test_invalid_spdx_form_fails(): void
    {
        // A free-text license string with spaces that are not SPDX operators
        // (WITH / AND / OR) cannot pass the syntactic check.
        $result = $this->checker->evaluateForInstall('MIT License');
        $this->assertSame(LicenseCompatibilityChecker::VERDICT_FAIL, $result['verdict']);
        $this->assertSame(LicenseValidator::STATUS_INVALID_SPDX, $result['status']);
    }

    public function test_install_guard_policy_overrides_default(): void
    {
        config(['licensing.install_guard.on_missing' => 'warn']);

        $result = $this->checker->evaluateForInstall(null);
        $this->assertSame(LicenseCompatibilityChecker::VERDICT_WARN, $result['verdict']);
    }

    public function test_is_compatible_with_core_for_permissive_licenses(): void
    {
        $this->assertTrue($this->checker->isCompatibleWithCore('MIT'));
        $this->assertTrue($this->checker->isCompatibleWithCore('Apache-2.0'));
        $this->assertTrue($this->checker->isCompatibleWithCore('GPL-3.0-or-later'));
    }

    public function test_is_compatible_with_core_returns_false_for_refused(): void
    {
        $this->assertFalse($this->checker->isCompatibleWithCore('GPL-2.0-only'));
    }

    public function test_commercial_escape_is_compatible_with_core(): void
    {
        $this->assertTrue($this->checker->isCompatibleWithCore('LicenseRef-Dixlase-Commercial'));
    }

    public function test_proprietary_is_compatible_with_core_via_exception(): void
    {
        $this->assertTrue($this->checker->isCompatibleWithCore('proprietary'));
    }

    public function test_plain_gpl_3_0_is_compatible_with_core(): void
    {
        $this->assertTrue($this->checker->isCompatibleWithCore('GPL-3.0'));
    }
}
