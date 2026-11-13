<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
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

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\CspReportController;
use Tests\TestCase;

/**
 * The CSP-report dev-tool exclusion keeps a generic built-in list (Vite's
 * default :5173, HMR markers, …) and merges operator-specific patterns from
 * config('csp.dev_tool_patterns'). Environment-specific ports are no longer
 * hardcoded in core.
 */
class CspReportControllerDevToolTest extends TestCase
{
    private function controller(): object
    {
        return new class extends CspReportController
        {
            /**
             * @param  array<string, string>  $report
             */
            public function check(array $report): bool
            {
                return $this->isDevToolViolation($report);
            }
        };
    }

    public function test_the_generic_vite_default_port_is_still_excluded(): void
    {
        config(['csp.dev_tool_patterns' => []]);

        $this->assertTrue($this->controller()->check(['blocked-uri' => 'https://example.test:5173/app.js']));
    }

    public function test_an_operator_specific_port_is_not_excluded_without_config(): void
    {
        config(['csp.dev_tool_patterns' => []]);

        $this->assertFalse($this->controller()->check(['blocked-uri' => 'https://example.test:44173/app.js']));
    }

    public function test_an_operator_specific_port_is_excluded_when_configured(): void
    {
        config(['csp.dev_tool_patterns' => [':44173']]);

        $this->assertTrue($this->controller()->check(['blocked-uri' => 'https://example.test:44173/app.js']));
    }

    public function test_a_plain_violation_is_not_excluded(): void
    {
        config(['csp.dev_tool_patterns' => []]);

        $this->assertFalse($this->controller()->check(['blocked-uri' => 'https://cdn.evil.test/x.js']));
    }
}
