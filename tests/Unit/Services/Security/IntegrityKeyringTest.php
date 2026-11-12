<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

namespace Tests\Unit\Services\Security;

use App\Services\Security\IntegrityKeyring;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Core #665: the integrity key, its previous versions and the keys derived
 * from it for each use.
 */
class IntegrityKeyringTest extends TestCase
{
    private function configure(?string $current, ?string $previous = null): IntegrityKeyring
    {
        config([
            'security.integrity_keys.current' => $current,
            'security.integrity_keys.previous' => $previous,
        ]);

        return new IntegrityKeyring;
    }

    public function test_nothing_is_configured_by_default(): void
    {
        $keyring = $this->configure(null);

        $this->assertFalse($keyring->isConfigured());
        $this->assertNull($keyring->currentVersion());
        $this->assertNull($keyring->currentKey(IntegrityKeyring::PURPOSE_AUDIT_CHAIN));
        $this->assertSame([], $keyring->problems());
    }

    public function test_current_and_previous_keys_verify_and_only_the_current_one_signs(): void
    {
        $v2 = IntegrityKeyring::generate(2);
        $v3 = IntegrityKeyring::generate(3);
        $keyring = $this->configure($v3, $v2);

        $this->assertSame(3, $keyring->currentVersion());
        $this->assertSame([3, 2], $keyring->versions());
        $this->assertTrue($keyring->has(2));
        $this->assertFalse($keyring->has(4));
        $this->assertSame(3, $keyring->currentKey(IntegrityKeyring::PURPOSE_AUDIT_SEAL)['version']);
    }

    public function test_each_use_and_each_version_gets_its_own_key(): void
    {
        $keyring = $this->configure(IntegrityKeyring::generate(3), IntegrityKeyring::generate(2));

        $keys = [
            $keyring->key(IntegrityKeyring::PURPOSE_AUDIT_CHAIN, 3),
            $keyring->key(IntegrityKeyring::PURPOSE_AUDIT_SEAL, 3),
            $keyring->key(IntegrityKeyring::PURPOSE_AUDIT_EPOCH, 3),
            $keyring->key(IntegrityKeyring::PURPOSE_AUDIT_CHAIN, 2),
        ];

        $this->assertCount(4, array_unique($keys));
        $this->assertSame(32, strlen((string) $keys[0]));
        $this->assertSame($keys[0], $keyring->key(IntegrityKeyring::PURPOSE_AUDIT_CHAIN, 3));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidKeys(): array
    {
        return [
            'reserved version 1' => ['1:base64:'.base64_encode(random_bytes(32))],
            'reserved version 0' => ['0:base64:'.base64_encode(random_bytes(32))],
            'key too short' => ['2:base64:'.base64_encode(random_bytes(16))],
            'no version' => ['base64:'.base64_encode(random_bytes(32))],
            'not base64' => ['2:base64:not*base64'],
        ];
    }

    #[DataProvider('invalidKeys')]
    public function test_an_invalid_current_key_is_ignored_and_reported(string $entry): void
    {
        $keyring = $this->configure($entry);

        $this->assertFalse($keyring->isConfigured());
        $this->assertCount(1, $keyring->problems());
        $this->assertNull(IntegrityKeyring::versionOf($entry));
    }

    public function test_a_previous_version_listed_twice_with_another_key_keeps_the_first(): void
    {
        $current = IntegrityKeyring::generate(3);
        $keyring = $this->configure($current, IntegrityKeyring::generate(3));

        $this->assertSame(3, $keyring->currentVersion());
        $this->assertCount(1, $keyring->problems());

        $expected = (new IntegrityKeyring)->key(IntegrityKeyring::PURPOSE_AUDIT_CHAIN, 3);
        $this->configure($current);
        $this->assertSame($expected, (new IntegrityKeyring)->key(IntegrityKeyring::PURPOSE_AUDIT_CHAIN, 3));
    }

    public function test_the_legacy_seal_key_is_the_audit_log_secret_or_the_application_key(): void
    {
        config(['app.audit_log_secret' => 'secret', 'app.key' => 'app-key']);
        $this->assertSame('secret', (new IntegrityKeyring)->legacySealKey());

        config(['app.audit_log_secret' => null]);
        $this->assertSame('app-key', (new IntegrityKeyring)->legacySealKey());
    }

    public function test_a_change_of_configuration_is_picked_up_by_the_same_instance(): void
    {
        $keyring = $this->configure(null);
        $this->assertFalse($keyring->isConfigured());

        config(['security.integrity_keys.current' => IntegrityKeyring::generate(2)]);
        $this->assertSame(2, $keyring->currentVersion());
    }

    public function test_generate_refuses_a_reserved_version(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        IntegrityKeyring::generate(1);
    }
}
