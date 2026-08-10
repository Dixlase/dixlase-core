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

namespace Tests\Feature\Security;

use App\Http\Middleware\CheckInstallationReady;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Dixlase members are created by an administrator. There is no self-service
 * signup, and there must not be one by accident.
 *
 * Fortify's Features::registration() had been left on, publishing
 * POST /register whose entire middleware stack was `web` +
 * RedirectIfAuthenticated — reachable by an anonymous visitor and bypassing
 * the AdminIpFilter / Authenticate / EnsureEmailIsVerified / CheckLockdown
 * chain that guards every other admin route.
 *
 * It went unnoticed because the endpoint happened to crash: its action
 * referenced App\Models\User, a class this application does not have. The
 * crash was the only thing standing between an anonymous visitor and a
 * self-registered admin member — and the test that would have caught it had
 * been skipped with the note "DixlaseUsers プラグイン (App\Models\User) 依存".
 * A crash is not an access control.
 */
class NoPublicRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function retiredFortifyRoutes(): array
    {
        return [
            'registration form' => ['GET', '/register'],
            'registration submit' => ['POST', '/register'],
            'password update' => ['PUT', '/user/password'],
            'profile update' => ['PUT', '/user/profile-information'],
        ];
    }

    #[DataProvider('retiredFortifyRoutes')]
    public function test_route_is_not_registered(string $method, string $uri): void
    {
        $match = collect(Route::getRoutes()->getRoutes())
            ->first(fn ($route) => $route->uri() === ltrim($uri, '/')
                && in_array($method, $route->methods(), true));

        $this->assertNull(
            $match,
            "{$method} {$uri} must not exist. Dixlase has no self-service signup: members are "
            .'created from the admin panel. Re-enabling the matching Fortify feature publishes '
            .'this route to anonymous visitors.'
        );
    }

    public function test_registration_feature_is_disabled(): void
    {
        $this->assertFalse(
            Features::enabled(Features::registration()),
            'Fortify registration must stay off; enabling it publishes POST /register.'
        );
    }

    public function test_profile_and_password_features_are_disabled(): void
    {
        // The admin panel edits both through its own controllers under
        // admin.profile.*; the Fortify endpoints were unused duplicates.
        $this->assertFalse(Features::enabled(Features::updateProfileInformation()));
        $this->assertFalse(Features::enabled(Features::updatePasswords()));
    }

    public function test_two_factor_authentication_is_still_enabled(): void
    {
        // Guards the blast radius of this change: 2FA is the one Fortify
        // feature Dixlase does use, and its routes must survive.
        $this->assertTrue(Features::enabled(Features::twoFactorAuthentication()));
    }

    public function test_anonymous_post_to_register_is_not_accepted(): void
    {
        // Stand the app up as "installed" and drop CheckInstallationReady:
        // otherwise every request 302s to the installer and the assertion
        // below would pass for the wrong reason, telling us nothing about
        // whether the route exists.
        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $this->withoutMiddleware([CheckInstallationReady::class]);

        try {
            $this->assertRegistrationRejected();
        } finally {
            putenv('INSTALLED=false');
            $_ENV['INSTALLED'] = 'false';
        }
    }

    private function assertRegistrationRejected(): void
    {
        // The end-to-end statement of intent: whatever the routing table says,
        // an anonymous visitor must not be able to create an account.
        $response = $this->post('/register', [
            'name' => 'Anonymous Intruder',
            'email' => 'intruder@example.com',
            'password' => 'password-password',
            'password_confirmation' => 'password-password',
        ]);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertGuest();
    }
}
