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

use App\Services\Licensing\LicenseValidator;
use Tests\TestCase;

class LicenseValidatorTest extends TestCase
{
    private LicenseValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new LicenseValidator();
    }

    public function test_missing_license_is_reported(): void
    {
        $result = $this->validator->validate(null);
        $this->assertSame(LicenseValidator::STATUS_MISSING, $result['status']);
        $this->assertNull($result['spdx']);
    }

    public function test_empty_string_is_reported_as_missing(): void
    {
        $result = $this->validator->validate('   ');
        $this->assertSame(LicenseValidator::STATUS_MISSING, $result['status']);
    }

    public function test_accepted_spdx_returns_accepted_with_metadata(): void
    {
        $result = $this->validator->validate('MIT');
        $this->assertSame(LicenseValidator::STATUS_ACCEPTED, $result['status']);
        $this->assertSame('MIT', $result['spdx']);
        $this->assertSame('permissive', $result['tier']);
        $this->assertSame('gpl_compatible', $result['compatibility']);
    }

    public function test_agpl_or_later_is_accepted(): void
    {
        $result = $this->validator->validate('AGPL-3.0-or-later');
        $this->assertSame(LicenseValidator::STATUS_ACCEPTED, $result['status']);
        $this->assertSame('strong_copyleft', $result['tier']);
    }

    public function test_commercial_escape_hatch_is_accepted(): void
    {
        $result = $this->validator->validate('LicenseRef-Dixlase-Commercial');
        $this->assertSame(LicenseValidator::STATUS_ACCEPTED, $result['status']);
        $this->assertSame('commercial_escape', $result['compatibility']);
    }

    public function test_refused_license_is_flagged_with_reason(): void
    {
        $result = $this->validator->validate('GPL-2.0-only');
        $this->assertSame(LicenseValidator::STATUS_REFUSED, $result['status']);
        $this->assertNotNull($result['reason']);
        $this->assertStringContainsString('GPL-2.0-only', $result['reason']);
    }

    public function test_plain_proprietary_is_refused(): void
    {
        $result = $this->validator->validate('proprietary');
        $this->assertSame(LicenseValidator::STATUS_REFUSED, $result['status']);
        $this->assertStringContainsString('LicenseRef-', $result['reason']);
    }

    public function test_non_spdx_form_is_reported_as_invalid(): void
    {
        $result = $this->validator->validate('MIT License');
        $this->assertSame(LicenseValidator::STATUS_INVALID_SPDX, $result['status']);
    }

    public function test_unknown_spdx_is_reported_as_unknown(): void
    {
        // EUPL-1.2 is not in the accepted whitelist for Phase 1 — but the
        // identifier is valid SPDX, so the validator returns "unknown",
        // not "invalid_spdx".
        $result = $this->validator->validate('EUPL-1.2');
        $this->assertSame(LicenseValidator::STATUS_UNKNOWN, $result['status']);
    }

    public function test_spdx_expression_with_or_operator_is_syntactically_valid(): void
    {
        $this->assertTrue($this->validator->isSyntacticallyValidSpdx('MIT OR Apache-2.0'));
    }

    public function test_user_defined_license_ref_is_syntactically_valid(): void
    {
        $this->assertTrue($this->validator->isSyntacticallyValidSpdx('LicenseRef-Acme-Forms-1.0'));
    }
}
