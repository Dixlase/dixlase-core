<?php

namespace Tests\Feature\Admin\Settings;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Models\Plugin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class TmpSessionDebugTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_value_is_readable_in_controller(): void
    {
        $this->withoutMiddleware([CheckInstallationReady::class, EnsureEmailIsVerified::class]);
        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
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
        echo "\nPlugin ID: ".$plugin->id.' (type: '.gettype($plugin->id).")\n";

        // Test what session() returns before the request
        echo 'Session before request: '.var_export(session('installed_plugin_id'), true)."\n";

        // Set up the session manually and test
        session(['installed_plugin_id' => $plugin->id]);
        echo 'Session after manual set: '.var_export(session('installed_plugin_id'), true)."\n";

        $response = $this->actingAs($admin, 'member')
            ->withSession(['installed_plugin_id' => $plugin->id])
            ->get(route('admin.settings.plugins.index'));

        // Check if the controller rendered with session data
        // Look for the plugin name in context that would indicate the card was found
        $content = $response->getContent();

        // The modal form route would be like /settings/plugins/enable/1
        $hasEnableRoute = str_contains($content, 'plugins/enable/1');
        echo 'Has enable route for plugin 1: '.($hasEnableRoute ? 'YES' : 'NO')."\n";

        // The quickEnableForm id=quickEnableForm would be in the modal
        $hasForm = str_contains($content, 'quickEnableForm');
        echo 'Has quickEnableForm: '.($hasForm ? 'YES' : 'NO')."\n";

        // Check if @if($installedPluginCard) evaluated to false
        // by looking for any sign of the modal section
        $hasInstallSection = str_contains($content, 'インストール直後') || str_contains($content, 'install_success');
        echo 'Has install section: '.($hasInstallSection ? 'YES' : 'NO')."\n";

        $this->assertTrue(true);
    }
}
