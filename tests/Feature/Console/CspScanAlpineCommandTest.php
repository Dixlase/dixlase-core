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

namespace Tests\Feature\Console;

use Tests\TestCase;

class CspScanAlpineCommandTest extends TestCase
{
    public function test_command_runs_and_prints_summary(): void
    {
        $this->artisan('dls:csp:scan-alpine', ['--scope' => 'core'])
            ->assertExitCode(0)
            ->expectsOutputToContain('Alpine CSP Migration Inventory')
            ->expectsOutputToContain('Total findings:');
    }

    public function test_unknown_scope_fails_gracefully(): void
    {
        $this->artisan('dls:csp:scan-alpine', ['--scope' => 'nope'])
            ->assertExitCode(1)
            ->expectsOutputToContain("No scan roots resolved for scope 'nope'.");
    }

    public function test_fail_on_threshold_returns_nonzero_when_exceeded(): void
    {
        $this->artisan('dls:csp:scan-alpine', [
            '--scope' => 'core',
            '--fail-on' => 0,
        ])
            ->assertExitCode(0); // fail-on=0 disables the gate

        // With a low threshold we expect a non-zero exit.
        $this->artisan('dls:csp:scan-alpine', [
            '--scope' => 'core',
            '--fail-on' => 1,
        ])
            ->assertExitCode(1)
            ->expectsOutputToContain('exceed --fail-on threshold');
    }

    public function test_json_option_writes_ledger_file(): void
    {
        $relPath = 'storage/app/private/csp-scan-test-'.uniqid().'.json';
        $absPath = base_path($relPath);

        try {
            $this->artisan('dls:csp:scan-alpine', [
                '--scope' => 'core',
                '--json' => $relPath,
            ])->assertExitCode(0);

            $this->assertFileExists($absPath);

            $data = json_decode((string) file_get_contents($absPath), true);
            $this->assertIsArray($data);
            $this->assertArrayHasKey('total', $data);
            $this->assertArrayHasKey('findings', $data);
            $this->assertArrayHasKey('generated_at', $data);
        } finally {
            if (file_exists($absPath)) {
                unlink($absPath);
            }
        }
    }
}
