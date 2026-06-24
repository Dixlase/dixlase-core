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

namespace Tests\Unit\Enums;

use App\Enums\ConsentCategory;
use PHPUnit\Framework\TestCase;

/**
 * Stability tests for the ConsentCategory enum.
 *
 * The string values are persisted in client cookies, in plugin
 * consent records, and in cross-plugin event payloads. Changing a
 * value would silently invalidate every existing consent record, so
 * these tests act as a tripwire against an accidental rename in
 * future refactors.
 */
class ConsentCategoryTest extends TestCase
{
    public function test_backed_values_are_stable(): void
    {
        $this->assertSame('necessary', ConsentCategory::Necessary->value);
        $this->assertSame('functional', ConsentCategory::Functional->value);
        $this->assertSame('analytics', ConsentCategory::Analytics->value);
        $this->assertSame('marketing', ConsentCategory::Marketing->value);
    }

    public function test_from_resolves_each_standard_value(): void
    {
        $this->assertSame(ConsentCategory::Necessary, ConsentCategory::from('necessary'));
        $this->assertSame(ConsentCategory::Functional, ConsentCategory::from('functional'));
        $this->assertSame(ConsentCategory::Analytics, ConsentCategory::from('analytics'));
        $this->assertSame(ConsentCategory::Marketing, ConsentCategory::from('marketing'));
    }

    public function test_try_from_returns_null_for_unknown_category(): void
    {
        $this->assertNull(ConsentCategory::tryFrom('marketing-email'));
        $this->assertNull(ConsentCategory::tryFrom(''));
        $this->assertNull(ConsentCategory::tryFrom('NECESSARY'));
    }

    public function test_has_exactly_four_cases(): void
    {
        $this->assertCount(4, ConsentCategory::cases());
    }
}
