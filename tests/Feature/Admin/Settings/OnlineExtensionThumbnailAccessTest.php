<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace Tests\Feature\Admin\Settings;

use App\Enums\MemberRole;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\ExtensionSource;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * The online extension thumbnail answers to the gate of the page it serves.
 *
 * Both thumbnail routes sit outside the check.menu.access groups. For the
 * local one that is deliberate: it returns a file already on disk, and the
 * lists that reference it are opened by several roles. The online one is
 * different -- it reaches a configured source over the network on behalf of
 * the SUPER_ADMIN "Add plugin" / "Add theme" pages -- so it checks the same
 * key those pages use. The key depends on {type}, so the check is in the
 * controller rather than on the route (#855).
 */
class OnlineExtensionThumbnailAccessTest extends TestCase
{
    use RefreshDatabase;

    private ExtensionSource $source;

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

        // Nothing in these tests should reach the network: a refusal happens
        // before the fetch, and the one request that passes the gate is
        // answered by the fake.
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response('', 404)]);

        $this->source = ExtensionSource::create([
            'name' => 'test source',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'owner' => 'example',
            'is_enabled' => true,
            'priority' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        unset($_ENV['INSTALLED']);

        parent::tearDown();
    }

    private function member(MemberRole $role, int $id): Member
    {
        return Member::factory()->create([
            'id' => $id,
            'account_name' => 'member'.$id,
            'email' => 'member'.$id.'@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => $role,
            'status' => 1,
        ]);
    }

    private function onlineUrl(string $type): string
    {
        return route('admin.settings.extension-thumbnail-online', [
            'source' => $this->source->id,
            'type' => $type,
            'slug' => 'example-plugin',
        ]);
    }

    public function test_a_member_without_extension_access_is_refused(): void
    {
        foreach ([MemberRole::ADMIN, MemberRole::EDITOR] as $i => $role) {
            $member = $this->member($role, 10 + $i);

            foreach (['plugin', 'theme'] as $type) {
                $this->actingAs($member)
                    ->get($this->onlineUrl($type))
                    ->assertForbidden();
            }
        }

        Http::assertNothingSent();
    }

    public function test_a_super_admin_is_not_refused(): void
    {
        $owner = $this->member(MemberRole::SUPER_ADMIN, 1);

        foreach (['plugin', 'theme'] as $type) {
            $response = $this->actingAs($owner)->get($this->onlineUrl($type));

            // The source answers 404 here, which the endpoint turns into the
            // default thumbnail rather than an error -- the point is only
            // that the gate let the request through.
            $this->assertNotSame(403, $response->getStatusCode(), $type);
        }
    }

    public function test_a_guest_is_sent_to_the_login_screen(): void
    {
        $this->get($this->onlineUrl('plugin'))->assertRedirect();
    }

    public function test_the_local_thumbnail_stays_ungated(): void
    {
        $member = $this->member(MemberRole::EDITOR, 20);

        $response = $this->actingAs($member)->get(route('admin.settings.extension-thumbnail', [
            'type' => 'plugins',
            'directory' => 'NoSuchExtension',
        ]));

        // It serves a file from disk, so a missing extension is a 404 (or the
        // fallback image) -- never a 403, which would hide the cards of an
        // installed extension from the roles that may list them.
        $this->assertNotSame(403, $response->getStatusCode());
    }
}
