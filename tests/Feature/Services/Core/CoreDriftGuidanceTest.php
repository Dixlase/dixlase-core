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

declare(strict_types=1);

namespace Tests\Feature\Services\Core;

use App\Models\CoreVersionHistory;
use App\Services\Core\VersionDriftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What the version-drift banner tells the operator to run.
 *
 * The two drift directions want opposite things, and the banner used to
 * name one command for both. In the `behind` direction that command
 * refuses, and the obvious next step a reader takes from a refusal —
 * adding `--force` — is the one thing that must not be done there, because
 * it writes an older synthetic row over the ledger evidence of whatever
 * actually happened. See dixlase-core#924.
 *
 * The commands are run for real here, against a stubbed drift reading, so
 * the banner's promise about each of them is checked rather than assumed.
 */
class CoreDriftGuidanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Fix the drift reading, through the real classifier so the shape
     * cannot drift from VersionDriftService's own.
     */
    private function stubDrift(string $onDisk, string $ledger): void
    {
        $this->app->instance(VersionDriftService::class, new class($onDisk, $ledger) extends VersionDriftService
        {
            public function __construct(private string $onDiskVersion, private string $ledgerVersion) {}

            public function detect(): array
            {
                return $this->classify($this->onDiskVersion, $this->ledgerVersion);
            }
        });
    }

    private function viewSource(): string
    {
        return (string) file_get_contents(
            resource_path('views/admin/settings/systems/updates/index.blade.php')
        );
    }

    /**
     * The view with its Blade comments removed — what an operator can
     * actually read on the page.
     */
    private function renderedView(): string
    {
        return (string) preg_replace('/\{\{--.*?--\}\}/s', '', $this->viewSource());
    }

    #[Test]
    public function the_behind_direction_names_the_command_that_writes_nothing(): void
    {
        $view = $this->viewSource();

        $this->assertMatchesRegularExpression(
            "/kind'\] === 'behind'.*?drift\.fix_instruction_behind.*?php artisan dls:core:reconcile<\/code>/s",
            $view,
            'The behind branch must name the bare command, which writes nothing.'
        );
    }

    #[Test]
    public function the_ahead_direction_is_unchanged(): void
    {
        $this->assertMatchesRegularExpression(
            "/@else.*?drift\.fix_instruction'\).*?php artisan dls:core:reconcile --confirm<\/code>/s",
            $this->viewSource(),
            'The ahead direction keeps the command and the wording it had.'
        );
    }

    #[Test]
    public function the_panel_never_tells_an_operator_to_force_a_reconcile(): void
    {
        // Not in what the page shows...
        $this->assertStringNotContainsString('--force', $this->renderedView());

        // ...and not in anything it prints from the translations.
        foreach (['en', 'ja'] as $locale) {
            $strings = require base_path("lang/{$locale}/admin/settings/systems/updates.php");
            $this->assertStringNotContainsString(
                '--force',
                json_encode($strings, JSON_UNESCAPED_UNICODE),
                "lang/{$locale} must not offer --force."
            );
        }
    }

    #[Test]
    public function both_locales_carry_both_instructions(): void
    {
        foreach (['en', 'ja'] as $locale) {
            $strings = require base_path("lang/{$locale}/admin/settings/systems/updates.php");

            $this->assertArrayHasKey('fix_instruction', $strings['drift'], "lang/{$locale}");
            $this->assertArrayHasKey('fix_instruction_behind', $strings['drift'], "lang/{$locale}");
            $this->assertNotSame(
                $strings['drift']['fix_instruction'],
                $strings['drift']['fix_instruction_behind'],
                "lang/{$locale}: the two directions must not read the same."
            );
        }
    }

    #[Test]
    public function the_command_named_for_behind_writes_nothing(): void
    {
        $this->stubDrift(onDisk: '0.2.1', ledger: '0.2.5');
        $before = CoreVersionHistory::count();

        // Exactly what the banner now shows: no --confirm, no --force.
        $this->artisan('dls:core:reconcile')->assertExitCode(1);

        $this->assertSame(
            $before,
            CoreVersionHistory::count(),
            'The command the banner names for the behind direction must not write to the ledger.'
        );
    }

    #[Test]
    public function the_command_named_for_ahead_reconciles_the_ledger(): void
    {
        CoreVersionHistory::create([
            'old_version' => null,
            'new_version' => '0.2.1',
            'installation_method' => CoreVersionHistory::METHOD_INSTALL,
            'applied_at' => now(),
        ]);
        $this->stubDrift(onDisk: '0.2.5', ledger: '0.2.1');

        // Without --confirm it previews and writes nothing...
        $this->artisan('dls:core:reconcile')->assertExitCode(0);
        $this->assertSame(1, CoreVersionHistory::count());

        // ...and the banner's command completes it.
        $this->artisan('dls:core:reconcile --confirm')->assertExitCode(0);
        $this->assertSame(2, CoreVersionHistory::count());
        $this->assertSame('0.2.5', CoreVersionHistory::query()->orderByDesc('id')->first()->new_version);
    }
}
