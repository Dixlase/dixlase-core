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

namespace Tests\Feature\Security;

use App\Enums\ContentEditorType;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Helpers\ThemeHelper;
use App\Http\Controllers\Admin\Auth\AdminNewPasswordController;
use App\Http\Controllers\Admin\Settings\AdminPluginsSettingsController;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMenuAccess;
use App\Http\Middleware\CheckMenuEdit;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Models\Plugin;
use App\Models\SiteSetting;
use App\Notifications\AdminResetPasswordNotification;
use App\Notifications\MemberVerifyEmailNotification;
use App\Services\ContentPreviewService;
use App\Services\PermissionRegistry;
use App\Support\ComposerLocalManifest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Regression tests for the 2026-09-28 security re-review (R2-1 to R2-7).
 */
class SecurityReviewRound2Test extends TestCase
{
    use RefreshDatabase;

    private string $sandbox = '';

    protected function setUp(): void
    {
        parent::setUp();

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        $adminTheme = config('themes.admin_theme', 'admin');
        $customFilesDir = base_path(config('custom.custom_files_dir', 'custom'));
        View::addNamespace('admin', [
            base_path("{$customFilesDir}/resources/views/{$adminTheme}"),
            resource_path("views/{$adminTheme}"),
        ]);

        SiteSetting::setValue('site_name', 'Test Site');
    }

    protected function tearDown(): void
    {
        if ($this->sandbox !== '' && File::isDirectory($this->sandbox)) {
            File::deleteDirectory($this->sandbox);
        }

        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';

        parent::tearDown();
    }

    // R2-1: raw HTML in Markdown can be switched off per caller

    public function test_markdown_escapes_raw_html_when_not_allowed(): void
    {
        $service = app(ContentPreviewService::class);
        $markdown = "Hello <img src=x onerror=alert(1)>\n\n[a](javascript:alert(1))";

        $html = $service->render($markdown, ContentEditorType::MARKDOWN, false);

        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('href="javascript:', $html);

        $fromSlug = $service->renderFromSlug($markdown, 'markdown', false, false);
        $this->assertStringNotContainsString('<img', $fromSlug);
    }

    public function test_markdown_keeps_raw_html_by_default(): void
    {
        $html = app(ContentPreviewService::class)->render('<span class="x">hi</span>', ContentEditorType::MARKDOWN);

        $this->assertStringContainsString('<span class="x">hi</span>', $html);
    }

    // R2-2: the member delete confirmation escapes the display name

    public function test_member_delete_modal_escapes_display_name(): void
    {
        $this->withoutMiddleware([
            CheckInstallationReady::class,
            CheckMenuAccess::class,
            CheckMenuEdit::class,
            EnsureEmailIsVerified::class,
        ]);

        $admin = $this->member(['account_name' => 'admin', 'email' => 'admin@example.com', 'role' => MemberRole::SUPER_ADMIN]);
        $this->member(['account_name' => 'victim', 'display_name' => '<b>bold-name</b>', 'email' => 'victim@example.com']);

        $this->actingAs($admin, 'member')
            ->get(route('admin.members.index'))
            ->assertOk()
            ->assertSee('&lt;b&gt;bold-name&lt;/b&gt;', false)
            ->assertDontSee('<b>bold-name</b>', false);
    }

    // R2-3: only installed plugins have their config files loaded

    public function test_installed_plugin_directories_skip_uninstalled_and_pending(): void
    {
        $root = $this->sandboxPath('plugins');
        foreach (['Installed', 'NotInstalled', 'Pending', 'Installed.stale.20260101-000000'] as $dir) {
            File::ensureDirectoryExists("{$root}/{$dir}/config/admin");
            File::put("{$root}/{$dir}/config/admin/roles.php", '<?php return [];');
        }
        ComposerLocalManifest::markPendingInstall("{$root}/Pending");

        foreach (['Installed', 'Pending', 'Installed.stale.20260101-000000', 'Missing'] as $dir) {
            $this->plugin($dir, strtolower(str_replace('.', '-', $dir)));
        }

        $this->assertSame(['Installed'], PermissionRegistry::installedPluginDirectories($root));
    }

    // R2-4: a manifest without `slug` no longer skips the pre-install scan gate

    public function test_slug_falls_back_to_the_install_command_rule(): void
    {
        $controller = new class extends AdminPluginsSettingsController
        {
            public function __construct() {}

            public function slugFor(string $dir): string
            {
                return $this->pluginSlugForDirectory($dir);
            }

            public function owner(string $slug, string $except): ?string
            {
                return $this->directoryOwningSlug($slug, $except);
            }
        };

        $this->assertSame('no-such-plugin-r2', $controller->slugFor('NoSuchPluginR2'));

        $this->plugin('VictimPluginR2', 'victim-r2');
        $this->assertSame('VictimPluginR2', $controller->owner('victim-r2', 'AttackerPluginR2'));
        $this->assertNull($controller->owner('victim-r2', 'VictimPluginR2'));
        $this->assertNull($controller->owner('free-slug-r2', 'AttackerPluginR2'));
    }

    public function test_install_without_manifest_slug_is_rejected_when_not_scanned(): void
    {
        $this->withoutMiddleware([
            CheckInstallationReady::class,
            EnsureEmailIsVerified::class,
        ]);
        Cache::put('security_settings:extension_security_preset', 'balanced');

        $fileMock = File::partialMock();
        $fileMock->shouldReceive('exists')
            ->withArgs(fn (string $path) => str_ends_with($path, 'plugins/SluglessPlugin'))
            ->andReturn(true);
        $fileMock->shouldReceive('exists')
            ->withArgs(fn (string $path) => str_ends_with($path, 'SluglessPlugin/plugin.json'))
            ->andReturn(true);
        $fileMock->shouldReceive('get')
            ->withArgs(fn (string $path) => str_ends_with($path, 'SluglessPlugin/plugin.json'))
            ->andReturn(json_encode(['name' => 'SluglessPlugin']));

        $admin = $this->member(['account_name' => 'admin', 'email' => 'admin@example.com', 'role' => MemberRole::SUPER_ADMIN]);

        $this->actingAs($admin, 'member')
            ->post(route('admin.settings.plugins.install'), ['directory' => 'SluglessPlugin'])
            ->assertRedirect()
            ->assertSessionHas('error', __('admin/settings/plugins/index.two_stage.install_blocked'));

        $this->assertDatabaseMissing('plugins', ['directory' => 'SluglessPlugin']);
    }

    // R2-5: only the confirmation mail goes to the unconfirmed address

    public function test_mail_routes_to_pending_email_only_for_verification(): void
    {
        $member = $this->member(['account_name' => 'mover', 'email' => 'old@example.com', 'pending_email' => 'new@example.com']);

        $this->assertSame('old@example.com', $member->routeNotificationForMail(new AdminResetPasswordNotification('token')));
        $this->assertSame('old@example.com', $member->routeNotificationForMail());
        $this->assertSame('new@example.com', $member->routeNotificationForMail(new MemberVerifyEmailNotification('email_change')));
    }

    // R2-5 / R2-6: a reset drops the pending address and ends every session

    public function test_password_reset_clears_pending_email_and_sessions(): void
    {
        $member = $this->member(['account_name' => 'resetter', 'email' => 'old@example.com', 'pending_email' => 'new@example.com']);
        DB::table('members_sessions')->insert([
            'id' => 'other-session',
            'member_id' => $member->id,
            'payload' => '',
            'last_activity' => time(),
        ]);

        $controller = (new \ReflectionClass(AdminNewPasswordController::class))->newInstanceWithoutConstructor();
        (new \ReflectionMethod($controller, 'performPasswordReset'))->invoke($controller, $member, 'N3w-Passw0rd!');

        $member->refresh();
        $this->assertNull($member->pending_email);
        $this->assertTrue(Hash::check('N3w-Passw0rd!', $member->password));
        $this->assertSame(0, DB::table('members_sessions')->where('member_id', $member->id)->count());
    }

    // R2-7: theme admin routes are registered once, by the gated provider

    public function test_admin_routes_no_longer_include_theme_routes_without_the_gate(): void
    {
        $this->assertFalse(method_exists(ThemeHelper::class, 'loadEnabledThemeAdminRoutes'));
        $this->assertStringNotContainsString(
            'loadEnabledThemeAdminRoutes()',
            File::get(base_path('routes/admin.php')),
        );
        $this->assertStringContainsString(
            "'check.menu.access:settings.themes.settings'",
            File::get(app_path('Providers/ThemeServiceProvider.php')),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function member(array $attributes): Member
    {
        return Member::create(array_merge([
            'display_name' => $attributes['account_name'] ?? 'member',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::ADMIN,
            'status' => MemberStatus::Active,
        ], $attributes));
    }

    private function plugin(string $directory, string $slug): Plugin
    {
        return Plugin::create([
            'name' => $directory,
            'directory' => $directory,
            'slug' => $slug,
            'namespace' => 'Plugins\\'.$directory,
            'version' => '1.0.0',
        ]);
    }

    private function sandboxPath(string $child): string
    {
        $this->sandbox = storage_path('framework/testing/security-r2-'.uniqid());
        File::ensureDirectoryExists($this->sandbox.'/'.$child);

        return $this->sandbox.'/'.$child;
    }
}
