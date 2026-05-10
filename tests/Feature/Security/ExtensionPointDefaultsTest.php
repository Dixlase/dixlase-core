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

namespace Tests\Feature\Security;

use App\Contracts\Security\PolicyEvaluatorInterface;
use App\Contracts\Security\RiskEvaluatorInterface;
use App\Contracts\Security\SecretProviderInterface;
use App\DTO\Security\LoginContext;
use App\DTO\Security\RiskScore;
use App\Enums\AccessRiskLevel;
use App\Enums\PolicyDecision;
use App\Http\Middleware\AuthenticateIap;
use App\Http\Middleware\AuthenticateMtls;
use App\Services\Security\EnvSecretProvider;
use App\Services\Security\LowRiskEvaluator;
use App\Services\Security\NullPolicyEvaluator;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Verifies that the Phase 1 reserved Zero Trust extension points resolve
 * to the documented no-op defaults out of the box.
 *
 * If these tests start failing, either:
 *  - A plugin's service provider is rebinding the contract during boot
 *    (expected once Phase 3 integrations land), or
 *  - The default binding has been changed without updating
 *    docs/development/extension-points.md.
 */
class ExtensionPointDefaultsTest extends TestCase
{
    public function test_secret_provider_default_is_env_backed(): void
    {
        $resolved = $this->app->make(SecretProviderInterface::class);

        $this->assertInstanceOf(EnvSecretProvider::class, $resolved);
    }

    public function test_secret_provider_returns_default_for_unknown_key(): void
    {
        $provider = $this->app->make(SecretProviderInterface::class);

        $this->assertNull($provider->get('NONEXISTENT_DIXLASE_SECRET_KEY_FOR_TEST'));
        $this->assertSame(
            'fallback',
            $provider->get('NONEXISTENT_DIXLASE_SECRET_KEY_FOR_TEST', 'fallback'),
        );
    }

    public function test_risk_evaluator_default_returns_low_risk(): void
    {
        $resolved = $this->app->make(RiskEvaluatorInterface::class);
        $this->assertInstanceOf(LowRiskEvaluator::class, $resolved);

        $score = $resolved->evaluate(new LoginContext);

        $this->assertInstanceOf(RiskScore::class, $score);
        $this->assertSame(AccessRiskLevel::Low, $score->level);
        $this->assertSame([], $score->signals);
        $this->assertFalse($score->atLeast(AccessRiskLevel::Medium));
    }

    public function test_policy_evaluator_default_defers_to_rbac(): void
    {
        $resolved = $this->app->make(PolicyEvaluatorInterface::class);
        $this->assertInstanceOf(NullPolicyEvaluator::class, $resolved);

        $decision = $resolved->evaluate(
            actor: null,
            action: 'front.edit',
            resource: null,
            context: ['ip' => '127.0.0.1'],
        );

        $this->assertSame(PolicyDecision::Defer, $decision);
    }

    public function test_auth_iap_middleware_aborts_with_501_by_default(): void
    {
        $middleware = new AuthenticateIap;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(501);

        $middleware->handle(Request::create('/admin'), fn ($request) => $request);
    }

    public function test_auth_mtls_middleware_aborts_with_501_by_default(): void
    {
        $middleware = new AuthenticateMtls;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(501);

        $middleware->handle(Request::create('/admin'), fn ($request) => $request);
    }

    public function test_access_risk_level_ordering_is_consistent(): void
    {
        $this->assertTrue(AccessRiskLevel::Critical->atLeast(AccessRiskLevel::Low));
        $this->assertTrue(AccessRiskLevel::High->atLeast(AccessRiskLevel::Medium));
        $this->assertFalse(AccessRiskLevel::Low->atLeast(AccessRiskLevel::Medium));
        $this->assertFalse(AccessRiskLevel::Medium->atLeast(AccessRiskLevel::High));
    }

    public function test_risk_score_factories_produce_correct_levels(): void
    {
        $this->assertSame(AccessRiskLevel::Low, RiskScore::low()->level);
        $this->assertSame(AccessRiskLevel::Medium, RiskScore::medium()->level);
        $this->assertSame(AccessRiskLevel::High, RiskScore::high(['new_country'])->level);
        $this->assertSame(AccessRiskLevel::Critical, RiskScore::critical()->level);

        $this->assertSame(['new_country'], RiskScore::high(['new_country'])->signals);
    }
}
