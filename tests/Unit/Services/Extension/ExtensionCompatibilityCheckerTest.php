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

namespace Tests\Unit\Services\Extension;

use App\Enums\ExtensionCompatibilityStatus;
use App\Extension\ExtensionApi;
use App\Services\Extension\ExtensionCompatibilityChecker;
use PHPUnit\Framework\TestCase;

class ExtensionCompatibilityCheckerTest extends TestCase
{
    private ExtensionCompatibilityChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = new ExtensionCompatibilityChecker();
    }

    public function test_compatible_when_declared_range_satisfies_core_version(): void
    {
        $result = $this->checker->check([
            'requires' => ['dixlase_api' => '^0.1'],
        ]);

        $this->assertSame(ExtensionCompatibilityStatus::Compatible, $result->status);
        $this->assertTrue($result->isCompatible());
        $this->assertSame('^0.1', $result->declared);
        $this->assertSame(ExtensionApi::CURRENT_VERSION, $result->coreVersion);
    }

    public function test_incompatible_when_declared_range_is_higher_than_core(): void
    {
        $result = $this->checker->check([
            'requires' => ['dixlase_api' => '^0.2'],
        ]);

        $this->assertSame(ExtensionCompatibilityStatus::Incompatible, $result->status);
        $this->assertFalse($result->isCompatible());
        $this->assertSame('^0.2', $result->declared);
        $this->assertStringContainsString('^0.2', $result->message);
        $this->assertStringContainsString(ExtensionApi::CURRENT_VERSION, $result->message);
    }

    public function test_incompatible_when_declared_range_is_lower_than_core(): void
    {
        // ^0.0.5 means >=0.0.5 <0.1.0, which excludes 0.1.0.
        $result = $this->checker->check([
            'requires' => ['dixlase_api' => '^0.0.5'],
        ]);

        $this->assertSame(ExtensionCompatibilityStatus::Incompatible, $result->status);
        $this->assertFalse($result->isCompatible());
    }

    public function test_missing_declaration_when_requires_block_is_absent(): void
    {
        $result = $this->checker->check([]);

        $this->assertSame(ExtensionCompatibilityStatus::MissingDeclaration, $result->status);
        $this->assertNull($result->declared);
    }

    public function test_missing_declaration_when_dixlase_api_key_is_absent(): void
    {
        $result = $this->checker->check([
            'requires' => ['dixlase' => '^0.1.0', 'php' => '>=8.2'],
        ]);

        $this->assertSame(ExtensionCompatibilityStatus::MissingDeclaration, $result->status);
        $this->assertNull($result->declared);
    }

    public function test_missing_declaration_when_value_is_empty_string(): void
    {
        $result = $this->checker->check([
            'requires' => ['dixlase_api' => ''],
        ]);

        $this->assertSame(ExtensionCompatibilityStatus::MissingDeclaration, $result->status);
    }

    public function test_missing_declaration_when_value_is_not_a_string(): void
    {
        $result = $this->checker->check([
            'requires' => ['dixlase_api' => 123],
        ]);

        $this->assertSame(ExtensionCompatibilityStatus::MissingDeclaration, $result->status);
    }

    public function test_malformed_constraint_when_value_is_garbage(): void
    {
        $result = $this->checker->check([
            'requires' => ['dixlase_api' => 'banana'],
        ]);

        $this->assertSame(ExtensionCompatibilityStatus::MalformedConstraint, $result->status);
        $this->assertSame('banana', $result->declared);
    }

    public function test_result_is_json_serializable(): void
    {
        $result = $this->checker->check([
            'requires' => ['dixlase_api' => '^0.1'],
        ]);

        $json = json_encode($result);
        $this->assertIsString($json);

        $decoded = json_decode($json, true);
        $this->assertSame('compatible', $decoded['status']);
        $this->assertSame('^0.1', $decoded['declared']);
        $this->assertSame(ExtensionApi::CURRENT_VERSION, $decoded['core_version']);
    }
}
