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

namespace Tests\Unit\Services\Extension;

use App\DTO\Plugin\HealthScoreResult;
use App\Enums\ExtensionSecurityLevel;
use App\Enums\PluginEnableAction;
use App\Enums\PluginHealthStatus;
use App\Services\Extension\ExtensionEnableActionResolver;
use App\Services\SecuritySettingsRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtensionEnableActionResolverTest extends TestCase
{
    use RefreshDatabase;

    private ExtensionEnableActionResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new ExtensionEnableActionResolver();
    }

    private function makeResult(int $score, PluginHealthStatus $status, bool $critical = false): HealthScoreResult
    {
        return new HealthScoreResult(
            score: $score,
            status: $status,
            issues: [],
            hasCriticalIssue: $critical,
        );
    }

    /**
     * A status worse than the explicit max level is blocked.
     */
    public function test_resolve_blocks_when_status_exceeds_max_level(): void
    {
        $action = $this->resolver->resolve(
            $this->makeResult(80, PluginHealthStatus::NeedsAttention),
            'plugin',
            ExtensionSecurityLevel::Warning,
        );

        $this->assertSame(PluginEnableAction::Blocked, $action);
    }

    /**
     * A high score within the allowed level is allowed outright.
     */
    public function test_resolve_allows_high_score(): void
    {
        $action = $this->resolver->resolve(
            $this->makeResult(95, PluginHealthStatus::Healthy),
            'plugin',
            ExtensionSecurityLevel::NotVerified,
        );

        $this->assertSame(PluginEnableAction::Allowed, $action);
    }

    /**
     * A mid score (>= 70) within the allowed level requires a warning.
     */
    public function test_resolve_requires_warning_for_mid_score(): void
    {
        $action = $this->resolver->resolve(
            $this->makeResult(80, PluginHealthStatus::Advisory),
            'plugin',
            ExtensionSecurityLevel::NotVerified,
        );

        $this->assertSame(PluginEnableAction::WarningRequired, $action);
    }

    /**
     * A low score (< 70) within the allowed level requires acknowledgement.
     */
    public function test_resolve_requires_acknowledgement_for_low_score(): void
    {
        $action = $this->resolver->resolve(
            $this->makeResult(55, PluginHealthStatus::NeedsAttention),
            'plugin',
            ExtensionSecurityLevel::NotVerified,
        );

        $this->assertSame(PluginEnableAction::AcknowledgementRequired, $action);
    }

    /**
     * A critical issue downgrades to acknowledgement even with a high score.
     */
    public function test_resolve_requires_acknowledgement_when_critical_issue_present(): void
    {
        $action = $this->resolver->resolve(
            $this->makeResult(95, PluginHealthStatus::Healthy, critical: true),
            'theme',
            ExtensionSecurityLevel::NotVerified,
        );

        $this->assertSame(PluginEnableAction::AcknowledgementRequired, $action);
    }

    /**
     * Themes and plugins are gated by their own max-health-level setting:
     * the same NeedsAttention status is blocked for a plugin (gate Warning)
     * but permitted for a theme (gate NeedsAttention).
     */
    public function test_resolve_applies_per_type_max_health_level(): void
    {
        SecuritySettingsRegistry::set('extension_security_preset', 'balanced');
        SecuritySettingsRegistry::set('extension_plugin_max_health_level', ExtensionSecurityLevel::Warning->value);
        SecuritySettingsRegistry::set('extension_theme_max_health_level', ExtensionSecurityLevel::NeedsAttention->value);

        $result = $this->makeResult(80, PluginHealthStatus::NeedsAttention);

        $this->assertSame(
            PluginEnableAction::Blocked,
            $this->resolver->resolve($result, 'plugin'),
            'Plugin gate (Warning) should block a NeedsAttention status.',
        );
        $this->assertSame(
            PluginEnableAction::WarningRequired,
            $this->resolver->resolve($result, 'theme'),
            'Theme gate (NeedsAttention) should permit the same status.',
        );
    }

    /**
     * The Development preset bypasses health gating even when a stricter
     * max-health-level value lingers from a previous preset.
     */
    public function test_resolve_development_preset_bypasses_health_gating(): void
    {
        SecuritySettingsRegistry::set('extension_security_preset', 'development');
        SecuritySettingsRegistry::set('extension_plugin_max_health_level', ExtensionSecurityLevel::Healthy->value);
        SecuritySettingsRegistry::set('extension_theme_max_health_level', ExtensionSecurityLevel::Healthy->value);

        $action = $this->resolver->resolve(
            $this->makeResult(40, PluginHealthStatus::NeedsAttention),
            'theme',
        );

        $this->assertNotSame(PluginEnableAction::Blocked, $action);
        $this->assertSame(PluginEnableAction::AcknowledgementRequired, $action);
    }
}
