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

declare(strict_types=1);

namespace Tests\Feature\Admin\Members;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMenuAccess;
use App\Http\Middleware\CheckMenuEdit;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Regression coverage for the member list search / filter query.
 *
 * Production (dixlase.org) returned a 500 on any keyword search because the
 * query referenced a `name` column that dls_members does not have, and a
 * submitted "all" status ('' from the <select>) was applied verbatim as
 * `status = ''`, which would have hidden every member even once the column
 * was fixed.
 */
class AdminMemberSearchTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            CheckMenuAccess::class,
            CheckMenuEdit::class,
            EnsureEmailIsVerified::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        $adminTheme = config('themes.admin_theme', 'admin');
        $customFilesDir = base_path(config('custom.custom_files_dir', 'custom'));
        View::addNamespace('admin', [
            base_path("{$customFilesDir}/resources/views/{$adminTheme}"),
            resource_path("views/{$adminTheme}"),
        ]);

        SiteSetting::setValue('site_name', 'Test Site');

        $this->admin = $this->member([
            'account_name' => 'testadmin',
            'display_name' => 'Test Admin',
            'email' => 'admin@example.com',
            'role' => MemberRole::SUPER_ADMIN,
        ]);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_keyword_matches_display_name_and_account_name(): void
    {
        $this->member([
            'account_name' => 'kassy',
            'display_name' => 'かっしー',
            'email' => 'kassy@example.com',
        ]);
        $this->member([
            'account_name' => 'someone',
            'display_name' => 'Someone Else',
            'email' => 'someone@example.com',
        ]);

        // Japanese display name — the exact production repro.
        $this->search(['search' => 'かっしー', 'status' => ''])
            ->assertOk()
            ->assertSee('kassy@example.com')
            ->assertDontSee('someone@example.com');

        // Login handle (account_name) is searchable too.
        $this->search(['search' => 'someo', 'status' => ''])
            ->assertOk()
            ->assertSee('someone@example.com')
            ->assertDontSee('kassy@example.com');
    }

    public function test_status_all_returns_active_and_inactive_members(): void
    {
        $this->member([
            'account_name' => 'onduty',
            'display_name' => 'On Duty',
            'email' => 'onduty@example.com',
        ]);
        $this->member([
            'account_name' => 'retired',
            'display_name' => 'Retired',
            'email' => 'retired@example.com',
            'status' => MemberStatus::Inactive,
        ]);

        // '' is what the "all" option submits: it must not become status = ''.
        $this->search(['status' => ''])
            ->assertOk()
            ->assertSee('onduty@example.com')
            ->assertSee('retired@example.com');

        // A concrete status still filters.
        $this->search(['status' => '0'])
            ->assertOk()
            ->assertSee('retired@example.com')
            ->assertDontSee('onduty@example.com');

        // A keyword combined with "all" must span both statuses.
        $this->search(['search' => 'example.com', 'status' => ''])
            ->assertOk()
            ->assertSee('onduty@example.com')
            ->assertSee('retired@example.com');
    }

    public function test_numeric_keyword_matches_the_member_id(): void
    {
        $target = $this->member([
            'account_name' => 'target',
            'display_name' => 'Target Person',
            'email' => 'target@example.org',
        ]);
        $this->member([
            'account_name' => 'bystander',
            'display_name' => 'Bystander',
            'email' => 'bystander@example.org',
        ]);

        $this->search(['search' => (string) $target->id, 'status' => ''])
            ->assertOk()
            ->assertSee('target@example.org')
            ->assertDontSee('bystander@example.org');
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function search(array $query): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin, 'member')
            ->get(route('admin.members.index', $query));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function member(array $attributes): Member
    {
        return Member::create(array_merge([
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::ADMIN,
            'status' => MemberStatus::Active,
        ], $attributes));
    }
}
