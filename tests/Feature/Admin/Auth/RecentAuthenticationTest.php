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

namespace Tests\Feature\Admin\Auth;

use App\Enums\MemberRole;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\RequireRecentAuthentication;
use App\Models\AuditLog;
use App\Models\Member;
use App\Services\Security\RecentAuthentication;
use App\Services\TwoFa\TwoFaPasskeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Core #675: sensitive admin routes require the member to have confirmed
 * their password or a passkey within the last 15 minutes.
 */
class RecentAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every admin route that requires a recent confirmation. A route added
     * to or taken off the list has to be added or taken off here as well,
     * so the change is made on purpose.
     *
     * Left out on purpose: ending sessions and revoking API keys (incident
     * response must not wait on a confirmation, and they lock an intruder
     * out rather than in), reading pages, and routine content editing.
     */
    private const PROTECTED_ROUTES = [
        // Own account
        'admin.profile.basic.update',
        'admin.profile.password.update',
        'admin.profile.two-fa.update',
        'admin.profile.passkey.register-options',
        'admin.profile.passkey.register',
        'admin.profile.passkey.revoke',
        'admin.profile.passkey.revoke-all',
        'admin.profile.recovery-codes.generate',
        'admin.profile.recovery-codes.regenerate',
        // Other members and roles
        'admin.members.store',
        'admin.members.update',
        'admin.members.destroy',
        'admin.members.passkey.revoke',
        'admin.members.passkey.revoke-all',
        'admin.members.recovery-codes.revoke',
        'admin.members.unlock-lockout',
        'admin.members.roles.update',
        // Personal data
        'admin.privacy.users.export',
        'admin.privacy.users.delete',
        // Base settings
        'admin.settings.base.admin.update',
        'admin.settings.base.mail.update',
        'admin.settings.base.mode.update',
        // Security settings
        'admin.settings.security.password.update',
        'admin.settings.security.login.update',
        'admin.settings.security.session.update',
        'admin.settings.security.two-fa.update',
        'admin.settings.security.captcha.update',
        'admin.settings.security.ip.update',
        'admin.settings.security.extensions.update',
        'admin.settings.security.csp.update',
        'admin.settings.security.notifications.update',
        'admin.settings.security.environment.update',
        'admin.settings.security.integrity.regenerate-baseline',
        'admin.settings.security.integrity.destroy',
        'admin.settings.security.integrity.bulk-delete',
        'admin.settings.security.integrity.acknowledge-extensions',
        // Themes
        'admin.settings.themes.upload',
        'admin.settings.themes.install',
        'admin.settings.themes.uninstall',
        'admin.settings.themes.switch',
        'admin.settings.themes.delete',
        'admin.settings.themes.download-from-source',
        'admin.settings.themes.rollback',
        // Plugins
        'admin.settings.plugins.upload',
        'admin.settings.plugins.install',
        'admin.settings.plugins.uninstall',
        'admin.settings.plugins.enable',
        'admin.settings.plugins.disable',
        'admin.settings.plugins.delete',
        'admin.settings.plugins.download-from-source',
        'admin.settings.plugins.rollback',
        // API keys
        'admin.settings.systems.api.update',
        'admin.settings.systems.api.generate-key',
        'admin.settings.systems.api.regenerate-key',
        // Core updates
        'admin.settings.systems.updates.apply',
        'admin.settings.systems.updates.apply-core',
        'admin.settings.systems.updates.rollback-core',
        // Backups
        'admin.settings.systems.backup.download',
        'admin.settings.systems.backup.restore',
        'admin.settings.systems.backup.destroy',
        'admin.settings.systems.backup.restores.rollback',
        'admin.settings.systems.backup.settings.update',
        // Data, logs and the audit trail
        'admin.settings.systems.database.cleanup',
        'admin.settings.systems.logs.download',
        'admin.settings.systems.logs.clear',
        'admin.settings.systems.logs.audit.export',
        'admin.settings.systems.logs.audit.cleanup',
    ];

    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            ContentSecurityPolicy::class,
            EnsureEmailIsVerified::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        $adminTheme = config('themes.admin_theme', 'admin');
        View::addNamespace('admin', [resource_path("views/{$adminTheme}")]);

        $this->member = $this->makeMember('owner', MemberRole::SUPER_ADMIN);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        unset($_ENV['INSTALLED']);

        parent::tearDown();
    }

    private function makeMember(string $name, MemberRole $role): Member
    {
        return Member::factory()->create([
            'account_name' => $name,
            'email' => $name.'@example.com',
            'password' => Hash::make('correct-password'),
            'email_verified_at' => now(),
            'role' => $role,
            'status' => 1,
        ]);
    }

    /**
     * @return list<string>
     */
    private function routesCarryingTheMiddleware(): array
    {
        $names = [];

        foreach (Route::getRoutes() as $route) {
            $middleware = $route->gatherMiddleware();
            $guarded = in_array('recent_auth', $middleware, true)
                || in_array(RequireRecentAuthentication::class, $middleware, true)
                || collect($middleware)->contains(fn ($m) => is_string($m) && str_starts_with($m, 'recent_auth:'));

            if ($guarded) {
                $names[] = (string) $route->getName();
            }
        }

        sort($names);

        return $names;
    }

    public function test_exactly_the_listed_routes_require_a_recent_confirmation(): void
    {
        $expected = self::PROTECTED_ROUTES;
        sort($expected);

        $this->assertSame($expected, $this->routesCarryingTheMiddleware());
    }

    public function test_the_confirmation_routes_themselves_are_not_guarded(): void
    {
        foreach (['admin.recent-auth.show', 'admin.recent-auth.password', 'admin.recent-auth.passkey.options', 'admin.recent-auth.passkey.verify'] as $name) {
            $this->assertTrue(Route::has($name), $name);
            $this->assertNotContains($name, $this->routesCarryingTheMiddleware());
        }
    }

    public function test_a_page_without_a_recent_confirmation_is_sent_to_confirm_and_comes_back_after_the_password(): void
    {
        $target = route('admin.settings.systems.logs.audit.export');

        $this->actingAs($this->member, 'member')
            ->get($target)
            ->assertRedirect(route('admin.recent-auth.show'));

        $this->assertDatabaseHas('audit_logs', ['action' => AuditLog::ACTION_STEP_UP_AUTH_REQUIRED]);

        $this->get(route('admin.recent-auth.show'))
            ->assertOk()
            ->assertSee(__('admin/recent-auth.title'));

        $this->post(route('admin.recent-auth.password'), ['password' => 'correct-password'])
            ->assertRedirect($target)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('audit_logs', ['action' => AuditLog::ACTION_STEP_UP_AUTH_COMPLETED]);

        $this->get($target)->assertStatus(200);
    }

    public function test_a_refused_form_comes_back_to_its_page_and_then_goes_through(): void
    {
        $formPage = route('admin.profile.password');

        $this->actingAs($this->member, 'member')
            ->from($formPage)
            ->post(route('admin.profile.password.update'), [])
            ->assertRedirect(route('admin.recent-auth.show'));

        $this->post(route('admin.recent-auth.password'), ['password' => 'correct-password'])
            ->assertRedirect($formPage)
            ->assertSessionHas('success', __('admin/recent-auth.confirmed_resubmit', ['minutes' => 15]));

        // Now the request reaches the controller: an empty form fails its
        // validation instead of being sent to confirm again.
        $response = $this->from($formPage)->post(route('admin.profile.password.update'), []);
        $this->assertNotSame(route('admin.recent-auth.show'), $response->headers->get('Location'));
    }

    public function test_a_script_request_gets_423_with_the_confirmation_url(): void
    {
        $this->actingAs($this->member, 'member')
            ->deleteJson(route('admin.profile.passkey.revoke-all'))
            ->assertStatus(423)
            ->assertHeader(RequireRecentAuthentication::HEADER, route('admin.recent-auth.show'))
            ->assertJson(['redirect' => route('admin.recent-auth.show')]);
    }

    public function test_an_intended_url_on_another_site_is_not_followed(): void
    {
        $this->actingAs($this->member, 'member')
            ->withHeader('Referer', 'https://elsewhere.example/phish')
            ->post(route('admin.profile.password.update'), []);

        $this->post(route('admin.recent-auth.password'), ['password' => 'correct-password'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_the_confirmation_expires(): void
    {
        $target = route('admin.settings.systems.logs.audit.export');
        $this->actingAs($this->member, 'member');
        $this->post(route('admin.recent-auth.password'), ['password' => 'correct-password']);

        $this->travel(14)->minutes();
        $this->get($target)->assertStatus(200);

        $this->travel(2)->minutes();
        $this->get($target)->assertRedirect(route('admin.recent-auth.show'));
    }

    public function test_the_window_follows_the_configuration(): void
    {
        config(['security.recent_auth.minutes' => 5]);
        $target = route('admin.settings.systems.logs.audit.export');
        $this->actingAs($this->member, 'member');
        $this->post(route('admin.recent-auth.password'), ['password' => 'correct-password']);

        $this->travel(6)->minutes();
        $this->get($target)->assertRedirect(route('admin.recent-auth.show'));
    }

    public function test_a_confirmation_does_not_carry_over_to_another_member(): void
    {
        $this->actingAs($this->member, 'member');
        app(RecentAuthentication::class)->confirm($this->member->getKey());

        $other = $this->makeMember('other', MemberRole::SUPER_ADMIN);
        $this->actingAs($other, 'member')
            ->get(route('admin.settings.systems.logs.audit.export'))
            ->assertRedirect(route('admin.recent-auth.show'));
    }

    public function test_a_wrong_password_is_refused_and_recorded(): void
    {
        $this->actingAs($this->member, 'member')
            ->from(route('admin.recent-auth.show'))
            ->post(route('admin.recent-auth.password'), ['password' => 'wrong'])
            ->assertRedirect(route('admin.recent-auth.show'))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseHas('audit_logs', ['action' => AuditLog::ACTION_STEP_UP_AUTH_FAILED]);
        $this->assertFalse(app(RecentAuthentication::class)->isRecent($this->member->getKey()));
        $this->assertAuthenticatedAs($this->member, 'member');
    }

    public function test_too_many_wrong_passwords_end_the_session(): void
    {
        config(['security.recent_auth.max_attempts' => 3]);
        $this->actingAs($this->member, 'member');

        $this->post(route('admin.recent-auth.password'), ['password' => 'wrong']);
        $this->post(route('admin.recent-auth.password'), ['password' => 'wrong']);
        $this->assertAuthenticatedAs($this->member, 'member');

        $this->post(route('admin.recent-auth.password'), ['password' => 'wrong'])
            ->assertRedirect(route('admin.login'));

        $this->assertGuest('member');
    }

    public function test_a_correct_password_resets_the_failure_count(): void
    {
        config(['security.recent_auth.max_attempts' => 2]);
        $this->actingAs($this->member, 'member');

        $this->post(route('admin.recent-auth.password'), ['password' => 'wrong']);
        $this->post(route('admin.recent-auth.password'), ['password' => 'correct-password']);
        $this->post(route('admin.recent-auth.password'), ['password' => 'wrong']);

        $this->assertAuthenticatedAs($this->member, 'member');
    }

    public function test_a_passkey_confirms(): void
    {
        $this->mock(TwoFaPasskeyService::class, function (MockInterface $mock) {
            $mock->shouldReceive('isAvailable')->andReturnTrue();
            $mock->shouldReceive('hasCredentials')->andReturnTrue();
            $mock->shouldReceive('generateLoginChallenge')->andReturn(['id' => 'x', 'publicKey' => ['challenge' => 'abc']]);
            $mock->shouldReceive('verifyLoginChallenge')
                ->withArgs(fn ($member, $data) => $member->is($this->member) && $data['id'] === 'credential')
                ->andReturnTrue();
        });

        $target = route('admin.settings.systems.logs.audit.export');
        $this->actingAs($this->member, 'member')->get($target);

        $this->get(route('admin.recent-auth.show'))->assertSee(__('admin/recent-auth.use_passkey'));

        $this->postJson(route('admin.recent-auth.passkey.options'))
            ->assertOk()
            ->assertJson(['success' => true, 'challenge' => ['challenge' => 'abc']]);

        $this->postJson(route('admin.recent-auth.passkey.verify'), ['id' => 'credential'])
            ->assertOk()
            ->assertJson(['success' => true, 'redirect' => $target]);

        $this->assertTrue(app(RecentAuthentication::class)->isRecent($this->member->getKey()));
        $this->assertDatabaseHas('audit_logs', ['action' => AuditLog::ACTION_STEP_UP_AUTH_COMPLETED]);
    }

    public function test_a_passkey_that_does_not_verify_does_not_confirm(): void
    {
        $this->mock(TwoFaPasskeyService::class, function (MockInterface $mock) {
            $mock->shouldReceive('isAvailable')->andReturnTrue();
            $mock->shouldReceive('hasCredentials')->andReturnTrue();
            $mock->shouldReceive('verifyLoginChallenge')->andReturnFalse();
        });

        $this->actingAs($this->member, 'member')
            ->postJson(route('admin.recent-auth.passkey.verify'), ['id' => 'credential'])
            ->assertStatus(422);

        $this->assertFalse(app(RecentAuthentication::class)->isRecent($this->member->getKey()));
        $this->assertDatabaseHas('audit_logs', ['action' => AuditLog::ACTION_STEP_UP_AUTH_FAILED]);
    }

    public function test_a_member_without_a_passkey_cannot_use_the_passkey_endpoints(): void
    {
        $this->mock(TwoFaPasskeyService::class, function (MockInterface $mock) {
            $mock->shouldReceive('isAvailable')->andReturnTrue();
            $mock->shouldReceive('hasCredentials')->andReturnFalse();
            $mock->shouldNotReceive('verifyLoginChallenge');
        });

        $this->actingAs($this->member, 'member');

        $this->get(route('admin.recent-auth.show'))->assertDontSee(__('admin/recent-auth.use_passkey'));
        $this->postJson(route('admin.recent-auth.passkey.options'))->assertStatus(422);
        $this->postJson(route('admin.recent-auth.passkey.verify'), ['id' => 'x'])->assertStatus(422);
        $this->assertFalse(app(RecentAuthentication::class)->isRecent($this->member->getKey()));
    }

    public function test_a_guest_is_sent_to_sign_in_not_to_confirm(): void
    {
        $this->get(route('admin.settings.systems.logs.audit.export'))
            ->assertRedirect(route('admin.login'));

        $this->get(route('admin.recent-auth.show'))
            ->assertRedirect(route('admin.login'));
    }
}
