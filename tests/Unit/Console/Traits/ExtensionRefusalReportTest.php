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

namespace Tests\Unit\Console\Traits;

use App\Console\Traits\EnforcesExtensionRequirements;
use App\Console\Traits\GuardsExtensionActivation;
use App\Enums\PluginEnableAction;
use App\Services\Extension\ExtensionActivationGate;
use App\Services\Extension\ExtensionRefusalReport;
use Mockery;
use Tests\TestCase;

/**
 * The checks that refuse an extension install record why, so the admin
 * panel can show that reason instead of the command's console buffer
 * (dixlase-core#654).
 */
class ExtensionRefusalReportTest extends TestCase
{
    public function test_an_unmet_requirement_is_recorded(): void
    {
        $message = $this->makeHost()->requirements(['version' => '1.0.0', 'requires' => ['php' => '>=99.0']]);

        $this->assertNotNull($message);
        $this->assertSame($message, app(ExtensionRefusalReport::class)->reason());
        $this->assertStringContainsString('>=99.0', $message);
    }

    public function test_a_met_requirement_records_nothing(): void
    {
        $this->assertNull($this->makeHost()->requirements(['requires' => ['php' => '>=7.0']]));
        $this->assertNull(app(ExtensionRefusalReport::class)->reason());
    }

    public function test_a_blocked_extension_is_recorded_without_the_console_hint(): void
    {
        $this->gateResolves(PluginEnableAction::Blocked);

        $this->assertFalse($this->makeHost()->gate(force: false, requireConfirmation: false));

        $reason = app(ExtensionRefusalReport::class)->reason();
        $this->assertSame(__('admin/command/extension-gate.blocked', [
            'name' => 'Demo',
            'kind' => 'plugin',
            'slug' => 'demo',
            'action' => __('admin/command/extension-gate.action.installed'),
        ]), $reason);
        $this->assertStringNotContainsString('php artisan', (string) $reason);
    }

    public function test_an_outcome_that_needs_confirmation_is_recorded_when_not_forced(): void
    {
        $this->gateResolves(PluginEnableAction::WarningRequired);

        $this->assertFalse($this->makeHost()->gate(force: false, requireConfirmation: true));
        $this->assertNotNull(app(ExtensionRefusalReport::class)->reason());
    }

    public function test_a_passing_gate_records_nothing(): void
    {
        $this->gateResolves(PluginEnableAction::Allowed);

        $this->assertTrue($this->makeHost()->gate(force: false, requireConfirmation: true));
        $this->assertNull(app(ExtensionRefusalReport::class)->reason());
    }

    private function gateResolves(PluginEnableAction $action): void
    {
        $gate = Mockery::mock(ExtensionActivationGate::class);
        $gate->shouldReceive('isEnforced')->andReturn(true);
        $gate->shouldReceive('resolve')->with('plugin', 'demo')->andReturn($action);
        $this->app->instance(ExtensionActivationGate::class, $gate);
    }

    private function makeHost(): object
    {
        return new class
        {
            use EnforcesExtensionRequirements;
            use GuardsExtensionActivation;

            /**
             * @param  array<string, mixed>  $manifest
             */
            public function requirements(array $manifest): ?string
            {
                return $this->unmetRequirementMessage($manifest, 'Demo', 'installed');
            }

            public function gate(bool $force, bool $requireConfirmation): bool
            {
                return $this->passesActivationGate('plugin', 'demo', 'Demo', 'installed', $force, $requireConfirmation);
            }

            public function error(string $message): void {}

            public function warn(string $message): void {}

            public function line(string $message): void {}
        };
    }
}
