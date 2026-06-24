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

namespace Tests\Unit\Events;

use App\Events\ConsentChanged;
use PHPUnit\Framework\TestCase;

class ConsentChangedTest extends TestCase
{
    public function test_first_time_acceptance_uses_empty_previous(): void
    {
        $event = new ConsentChanged(
            previous: [],
            current: ['necessary' => true, 'analytics' => false],
            version: 1,
        );

        $this->assertSame([], $event->previous);
        $this->assertSame(['necessary' => true, 'analytics' => false], $event->current);
        $this->assertSame(1, $event->version);
    }

    public function test_re_consent_carries_both_snapshots(): void
    {
        $event = new ConsentChanged(
            previous: ['necessary' => true, 'analytics' => false],
            current: ['necessary' => true, 'analytics' => true],
            version: 2,
        );

        $this->assertFalse($event->previous['analytics']);
        $this->assertTrue($event->current['analytics']);
    }

    public function test_payload_is_serialisable_via_json(): void
    {
        // The Wasm / Capability Broker boundary requires the payload to
        // round-trip through JSON without lossy custom-object encoding.
        $event = new ConsentChanged(
            previous: ['necessary' => true],
            current: ['necessary' => true, 'analytics' => true, 'marketing' => false],
            version: 3,
        );

        $encoded = json_encode([
            'previous' => $event->previous,
            'current' => $event->current,
            'version' => $event->version,
        ]);
        $this->assertIsString($encoded);

        $decoded = json_decode((string) $encoded, true);
        $this->assertSame($event->previous, $decoded['previous']);
        $this->assertSame($event->current, $decoded['current']);
        $this->assertSame($event->version, $decoded['version']);
    }

    public function test_schema_version_constant_is_exposed(): void
    {
        $this->assertSame(1, ConsentChanged::SCHEMA_VERSION);
    }
}
