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

use App\DTO\Plugin\HealthIssue;
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
        // SecuritySettingsRegistry caches resolved values; clear it so each
        // test starts from the registered defaults rather than a sibling
        // test's cached setting.
        SecuritySettingsRegistry::clearCache();
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

    private function makeResultWithIssue(int $score, PluginHealthStatus $status, string $issueType): HealthScoreResult
    {
        return new HealthScoreResult(
            score: $score,
            status: $status,
            issues: [new HealthIssue(type: $issueType, severity: 'warning', description: '')],
            hasCriticalIssue: false,
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

    /**
     * The Strict preset requires a signature: an unsigned plugin is blocked
     * even when its health score would otherwise allow activation.
     */
    public function test_resolve_blocks_unsigned_plugin_under_strict_preset(): void
    {
        SecuritySettingsRegistry::set('extension_security_preset', 'strict');

        $action = $this->resolver->resolve(
            $this->makeResultWithIssue(90, PluginHealthStatus::Healthy, 'signature_unsigned'),
            'plugin',
        );

        $this->assertSame(PluginEnableAction::Blocked, $action);
    }

    /**
     * A validly signed plugin is not blocked by the Strict preset.
     */
    public function test_resolve_allows_signed_plugin_under_strict_preset(): void
    {
        SecuritySettingsRegistry::set('extension_security_preset', 'strict');

        $action = $this->resolver->resolve(
            $this->makeResult(95, PluginHealthStatus::Healthy),
            'plugin',
        );

        $this->assertSame(PluginEnableAction::Allowed, $action);
    }

    /**
     * The Standard (Balanced) preset does not require a signature, so an
     * unsigned plugin is still installable.
     */
    public function test_resolve_allows_unsigned_plugin_under_balanced_preset(): void
    {
        SecuritySettingsRegistry::set('extension_security_preset', 'balanced');

        $action = $this->resolver->resolve(
            $this->makeResultWithIssue(90, PluginHealthStatus::Healthy, 'signature_unsigned'),
            'plugin',
        );

        $this->assertNotSame(PluginEnableAction::Blocked, $action);
    }

    /**
     * The Custom preset blocks unsigned plugins when require_signature is on.
     */
    public function test_resolve_blocks_unsigned_plugin_when_custom_requires_signature(): void
    {
        SecuritySettingsRegistry::set('extension_security_preset', 'custom');
        SecuritySettingsRegistry::set('extension_require_signature', true);

        $action = $this->resolver->resolve(
            $this->makeResultWithIssue(90, PluginHealthStatus::Healthy, 'signature_unsigned'),
            'plugin',
        );

        $this->assertSame(PluginEnableAction::Blocked, $action);
    }

    /**
     * The Custom preset allows unsigned plugins when require_signature is off.
     */
    public function test_resolve_allows_unsigned_plugin_when_custom_does_not_require_signature(): void
    {
        SecuritySettingsRegistry::set('extension_security_preset', 'custom');
        SecuritySettingsRegistry::set('extension_require_signature', false);

        $action = $this->resolver->resolve(
            $this->makeResultWithIssue(90, PluginHealthStatus::Healthy, 'signature_unsigned'),
            'plugin',
        );

        $this->assertNotSame(PluginEnableAction::Blocked, $action);
    }

    /**
     * A signature pending verification still counts as signed: the Strict
     * preset does not block it.
     */
    public function test_resolve_treats_pending_verification_as_signed_under_strict(): void
    {
        SecuritySettingsRegistry::set('extension_security_preset', 'strict');

        $action = $this->resolver->resolve(
            $this->makeResultWithIssue(90, PluginHealthStatus::Healthy, 'signature_pending_verification'),
            'plugin',
        );

        $this->assertNotSame(PluginEnableAction::Blocked, $action);
    }
}
