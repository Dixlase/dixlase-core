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

namespace Tests\Unit\Database\Factories;

use App\Enums\AuthenticationMode;
use App\Services\TwoFa\TwoFaStatusService;
use Database\Factories\MemberFactory;
use Tests\TestCase;

/**
 * Guards that core can build a usable Member on its own (#511).
 *
 * tests/TestCase prefers a DixlaseCoreDevKit factory when that plugin is on
 * disk, and the plugin's MemberFactory sets two_fa_mode while core's did not.
 * Because the plugin is present in every CI run that clones the integration
 * plugins, the gap in core's own factory stayed invisible, and a checkout
 * without the plugin got a Member whose two_fa_mode was unset — which the 2FA
 * status code then dereferenced.
 *
 * These tests instantiate core's factory directly, bypassing that resolver,
 * so they exercise core's own definition whether or not the plugin is there.
 */
class MemberFactoryTwoFaModeTest extends TestCase
{
    public function test_cores_own_factory_sets_two_fa_mode(): void
    {
        $member = (new MemberFactory())->make();

        $this->assertNotNull(
            $member->two_fa_mode,
            "core's MemberFactory must set two_fa_mode; the 2FA status code reads ->value on it"
        );
        $this->assertSame(AuthenticationMode::Disabled, $member->two_fa_mode);
    }

    public function test_two_fa_mode_value_accepts_an_unset_attribute(): void
    {
        // A Member built without the attribute reports null. The column
        // defaults to 0, so that is what an unset attribute must read as.
        $this->assertSame(
            AuthenticationMode::Disabled->value,
            TwoFaStatusService::twoFaModeValue(null)
        );
    }

    public function test_two_fa_mode_value_accepts_an_enum_and_an_int(): void
    {
        $this->assertSame(
            AuthenticationMode::Always->value,
            TwoFaStatusService::twoFaModeValue(AuthenticationMode::Always)
        );
        $this->assertSame(
            AuthenticationMode::DifferentDevice->value,
            TwoFaStatusService::twoFaModeValue(AuthenticationMode::DifferentDevice->value)
        );
    }
}
