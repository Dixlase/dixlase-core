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

use App\Enums\LoginIdentifierMode;
use PHPUnit\Framework\TestCase;

/**
 * LoginIdentifierMode Enum のユニットテスト
 */
class LoginIdentifierModeTest extends TestCase
{
    // ========================================
    // supportsEmail()
    // ========================================

    public function test_email_only_supports_email(): void
    {
        $this->assertTrue(LoginIdentifierMode::EmailOnly->supportsEmail());
    }

    public function test_email_or_account_name_supports_email(): void
    {
        $this->assertTrue(LoginIdentifierMode::EmailOrAccountName->supportsEmail());
    }

    public function test_account_name_only_does_not_support_email(): void
    {
        $this->assertFalse(LoginIdentifierMode::AccountNameOnly->supportsEmail());
    }

    // ========================================
    // supportsAccountName()
    // ========================================

    public function test_email_only_does_not_support_account_name(): void
    {
        $this->assertFalse(LoginIdentifierMode::EmailOnly->supportsAccountName());
    }

    public function test_email_or_account_name_supports_account_name(): void
    {
        $this->assertTrue(LoginIdentifierMode::EmailOrAccountName->supportsAccountName());
    }

    public function test_account_name_only_supports_account_name(): void
    {
        $this->assertTrue(LoginIdentifierMode::AccountNameOnly->supportsAccountName());
    }

    // ========================================
    // Enum値
    // ========================================

    public function test_enum_values(): void
    {
        $this->assertSame(0, LoginIdentifierMode::EmailOnly->value);
        $this->assertSame(1, LoginIdentifierMode::EmailOrAccountName->value);
        $this->assertSame(2, LoginIdentifierMode::AccountNameOnly->value);
    }

    public function test_try_from_valid_values(): void
    {
        $this->assertSame(LoginIdentifierMode::EmailOnly, LoginIdentifierMode::tryFrom(0));
        $this->assertSame(LoginIdentifierMode::EmailOrAccountName, LoginIdentifierMode::tryFrom(1));
        $this->assertSame(LoginIdentifierMode::AccountNameOnly, LoginIdentifierMode::tryFrom(2));
    }

    public function test_try_from_invalid_value_returns_null(): void
    {
        $this->assertNull(LoginIdentifierMode::tryFrom(99));
    }

    // ========================================
    // iconClass()
    // ========================================

    public function test_icon_class_returns_font_awesome_classes(): void
    {
        $this->assertStringStartsWith('fas fa-', LoginIdentifierMode::EmailOnly->iconClass());
        $this->assertStringStartsWith('fas fa-', LoginIdentifierMode::EmailOrAccountName->iconClass());
        $this->assertStringStartsWith('fas fa-', LoginIdentifierMode::AccountNameOnly->iconClass());
    }

    // ========================================
    // translationKey() / descriptionKey()
    // ========================================

    public function test_translation_keys_are_properly_formatted(): void
    {
        foreach (LoginIdentifierMode::cases() as $case) {
            $this->assertStringStartsWith('common.login_identifier_mode.', $case->translationKey());
            $this->assertStringStartsWith('common.login_identifier_mode.', $case->descriptionKey());
            $this->assertStringEndsWith('_description', $case->descriptionKey());
        }
    }

    // ========================================
    // cases() カバレッジ
    // ========================================

    public function test_has_exactly_three_cases(): void
    {
        $this->assertCount(3, LoginIdentifierMode::cases());
    }
}
