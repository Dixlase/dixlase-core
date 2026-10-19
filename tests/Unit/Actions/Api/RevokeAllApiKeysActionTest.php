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

namespace Tests\Unit\Actions\Api;

use App\Actions\Api\GenerateApiKeyAction;
use App\Actions\Api\RevokeAllApiKeysAction;
use App\Actors\MemberActor;
use App\Enums\MemberRole;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMenuAccess;
use App\Http\Middleware\CheckMenuEdit;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\ApiKey;
use App\Models\AuditLog;
use App\Models\Member;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Core #674: every API key of the site can be revoked in one step for
 * incident response, without touching network keys shared with other sites.
 */
class RevokeAllApiKeysActionTest extends TestCase
{
    use RefreshDatabase;

    private Member $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = Member::factory()->create([
            'account_name' => 'keyadmin',
            'display_name' => 'Key Admin',
            'email' => 'keyadmin@example.test',
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
        ]);
    }

    public function test_every_site_key_is_deleted_and_refused_at_once(): void
    {
        $first = $this->key('Deploy key');
        $second = $this->key('Backup key');

        $result = (new RevokeAllApiKeysAction())->execute(new MemberActor($this->superAdmin), []);

        $this->assertSame(2, $result->metadata['count']);
        $this->assertSame(0, ApiKey::count());
        $this->assertNull(ApiKey::validate($first['plain']));
        $this->assertNull(ApiKey::validate($second['plain']));
    }

    public function test_network_keys_are_left_alone(): void
    {
        $site = $this->key('Site key');
        $network = $this->key('Network key');
        DB::table('api_keys')->where('id', $network['model']->id)->update(['site_id' => null]);

        (new RevokeAllApiKeysAction())->execute(new MemberActor($this->superAdmin), []);

        $this->assertDatabaseMissing('api_keys', ['id' => $site['model']->id]);
        $this->assertDatabaseHas('api_keys', ['id' => $network['model']->id]);
    }

    public function test_one_audit_row_names_the_keys_without_key_material(): void
    {
        $key = $this->key('Deploy key');

        (new RevokeAllApiKeysAction())->execute(new MemberActor($this->superAdmin), []);

        $row = AuditLog::where('action', AuditLog::ACTION_API_KEYS_REVOKED_ALL)->sole();
        $this->assertSame($this->superAdmin->id, $row->actor_id);
        $this->assertSame(1, $row->context['count']);
        $this->assertSame([['id' => $key['model']->id, 'name' => 'Deploy key']], $row->context['keys']);

        $serialised = json_encode($row->toArray());
        $this->assertStringNotContainsString($key['plain'], $serialised);
        $this->assertStringNotContainsString($key['model']->key_hash, $serialised);
    }

    public function test_an_editor_cannot_revoke_every_key(): void
    {
        $this->key('Deploy key');
        $editor = Member::factory()->create([
            'account_name' => 'editor',
            'email' => 'editor@example.test',
            'role' => MemberRole::EDITOR,
        ]);

        $this->expectException(AuthorizationException::class);

        (new RevokeAllApiKeysAction())->execute(new MemberActor($editor), []);
    }

    public function test_the_admin_route_revokes_every_key(): void
    {
        // The API screen is hidden in simple mode, the default, and the menu
        // middleware refuses its forms there; the menu gate has its own tests.
        $this->withoutMiddleware([
            CheckInstallationReady::class,
            CheckMenuAccess::class,
            CheckMenuEdit::class,
            ContentSecurityPolicy::class,
            EnsureEmailIsVerified::class,
        ]);
        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        try {
            $this->key('Deploy key');

            $this->actingAs($this->superAdmin, 'member')
                ->delete(route('admin.settings.systems.api.revoke-all'))
                ->assertRedirect(route('admin.settings.systems.api'))
                ->assertSessionHas('success');

            $this->assertSame(0, ApiKey::count());
        } finally {
            putenv('INSTALLED=false');
            unset($_ENV['INSTALLED']);
        }
    }

    /**
     * @return array{model: ApiKey, plain: string}
     */
    private function key(string $name): array
    {
        $result = (new GenerateApiKeyAction())->execute(new MemberActor($this->superAdmin), [
            'name' => $name,
            'environment' => ApiKey::ENV_TEST,
            'scopes' => ['content:read'],
            'rate_limit' => 120,
            'allowed_ips' => null,
            'expires_at' => null,
            'description' => null,
        ]);

        return ['model' => $result->model, 'plain' => $result->metadata['plain_key']];
    }
}
