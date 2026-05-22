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

namespace Tests\Unit\Presenters\Admin;

use App\Presenters\Admin\ExtensionCardPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * The Strict security preset requires a signature. An unsigned extension must
 * therefore never be reported as compatible with the Strict preset, even when
 * its health status is otherwise high enough.
 */
class ExtensionCardPresenterPresetBadgesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{compatible: bool|null}>
     */
    private function buildBadges(?string $healthStatus, ?string $signatureStatus): array
    {
        $method = new ReflectionMethod(ExtensionCardPresenter::class, 'buildPresetCompatibilityBadges');
        $method->setAccessible(true);

        return $method->invoke(null, $healthStatus, $signatureStatus);
    }

    public function test_unsigned_healthy_plugin_is_not_strict_compatible(): void
    {
        $badges = $this->buildBadges('healthy', 'unsigned');

        $this->assertFalse(
            $badges['strict']['compatible'],
            'An unsigned plugin must not be compatible with the signature-requiring Strict preset.'
        );
        // Development and Balanced do not require a signature.
        $this->assertTrue($badges['development']['compatible']);
        $this->assertTrue($badges['balanced']['compatible']);
    }

    public function test_validly_signed_healthy_plugin_is_strict_compatible(): void
    {
        $badges = $this->buildBadges('healthy', 'valid');

        $this->assertTrue($badges['strict']['compatible']);
    }

    public function test_pending_verification_counts_as_signed_for_strict(): void
    {
        $badges = $this->buildBadges('healthy', 'pending_verification');

        $this->assertTrue(
            $badges['strict']['compatible'],
            'A plugin that carries a signature pending verification still counts as signed.'
        );
    }

    public function test_invalid_signature_is_not_strict_compatible(): void
    {
        $badges = $this->buildBadges('healthy', 'invalid');

        $this->assertFalse($badges['strict']['compatible']);
    }

    public function test_null_health_status_yields_unknown_badges(): void
    {
        $badges = $this->buildBadges(null, 'unsigned');

        $this->assertNull($badges['strict']['compatible']);
        $this->assertNull($badges['balanced']['compatible']);
        $this->assertNull($badges['development']['compatible']);
    }
}
