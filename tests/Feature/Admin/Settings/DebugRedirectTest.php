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

namespace Tests\Feature\Admin\Settings;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Models\Plugin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class DebugRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_card_lookup(): void
    {
        $this->withoutMiddleware(EnsureEmailIsVerified::class);

        // Register admin namespace
        $adminTheme = config('themes.admin_theme', 'admin');
        $customFilesDir = base_path(config('custom.custom_files_dir', 'custom'));
        View::addNamespace('admin', [
            base_path("{$customFilesDir}/resources/views/{$adminTheme}"),
            resource_path("views/{$adminTheme}"),
        ]);

        \App\Models\BaseSetting::setValue('site_name', 'Test Site');

        $admin = Member::create([
            'account_name' => 'testadmin',
            'display_name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);

        $plugin = Plugin::create([
            'name' => 'TestPlugin',
            'directory' => 'TestPlugin',
            'slug' => 'test-plugin',
            'namespace' => 'Plugins\\TestPlugin\\',
            'version' => '1.0.0',
            'installed_at' => now(),
        ]);

        echo "\n\nPlugin ID: ".$plugin->id.' (type: '.gettype($plugin->id).")\n";

        $response = $this->actingAs($admin, 'member')
            ->withSession(['installed_plugin_id' => $plugin->id])
            ->get(route('admin.settings.plugins.index'));

        $status = $response->getStatusCode();
        $location = $response->headers->get('Location', 'none');
        $hasModal = str_contains($response->getContent(), 'quickEnableModal');

        echo "STATUS: $status\n";
        echo "LOCATION: $location\n";
        echo 'Has quickEnableModal: '.($hasModal ? 'YES' : 'NO')."\n\n";

        $this->assertTrue(true);
    }
}
