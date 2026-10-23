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

use App\DTO\Plugin\HealthScoreResult;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Enums\PluginEnableAction;
use App\Enums\PluginHealthStatus;
use App\Http\Controllers\Admin\Settings\AdminPluginsSettingsController;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Models\Plugin;
use App\Models\PluginAudit;
use App\Services\Extension\ExtensionRefusalReport;
use App\Services\Plugin\PluginHealthScorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

/**
 * プラグインインストール時のセキュリティチェックテスト
 *
 * 2段階モーダルフローのサーバーサイド防御として、
 * スキャン必須モードでの事前チェックが正しく機能することを検証する
 */
class PluginInstallSecurityTest extends TestCase
{
    use RefreshDatabase;

    /** @var Member スーパー管理者メンバー */
    private Member $admin;

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

        \App\Models\SiteSetting::setValue('site_name', 'Test Site');

        $this->admin = Member::create([
            'account_name' => 'testadmin',
            'display_name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';

        parent::tearDown();
    }

    // ========================================
    // isScanRequired() のテスト
    // ========================================

    /**
     * Strict モードではスキャンが必須であること
     */
    public function test_scan_required_in_strict_mode(): void
    {
        Cache::put('security_settings:'.'extension_security_preset', 'strict');

        $this->assertTrue(AdminPluginsSettingsController::isScanRequired());
    }

    /**
     * Balanced モードではスキャンが必須であること
     */
    public function test_scan_required_in_balanced_mode(): void
    {
        Cache::put('security_settings:'.'extension_security_preset', 'balanced');

        $this->assertTrue(AdminPluginsSettingsController::isScanRequired());
    }

    /**
     * Development モードではスキャンが不要であること
     */
    public function test_scan_not_required_in_development_mode(): void
    {
        Cache::put('security_settings:'.'extension_security_preset', 'development');

        $this->assertFalse(AdminPluginsSettingsController::isScanRequired());
    }

    /**
     * Custom モードで require_signature が有効ならスキャンが必須であること
     */
    public function test_scan_required_in_custom_mode_with_require_signature(): void
    {
        Cache::put('security_settings:'.'extension_security_preset', 'custom');
        Cache::put('security_settings:'.'extension_require_signature', true);
        Cache::put('security_settings:'.'extension_require_permission_definition', false);
        Cache::put('security_settings:'.'extension_permission_mismatch_action', 'warn');

        $this->assertTrue(AdminPluginsSettingsController::isScanRequired());
    }

    /**
     * Custom モードで制約条件がすべてオフならスキャンが不要であること
     */
    public function test_scan_not_required_in_custom_mode_without_constraints(): void
    {
        Cache::put('security_settings:'.'extension_security_preset', 'custom');
        Cache::put('security_settings:'.'extension_require_signature', false);
        Cache::put('security_settings:'.'extension_require_permission_definition', false);
        Cache::put('security_settings:'.'extension_permission_mismatch_action', 'warn');

        $this->assertFalse(AdminPluginsSettingsController::isScanRequired());
    }

    // ========================================
    // インストール時のセキュリティチェックテスト
    // ========================================

    /**
     * スキャン必須モードで未スキャンプラグインのインストールが拒否されること
     */
    public function test_install_rejected_when_scan_required_and_not_scanned(): void
    {
        $this->withoutMiddleware([
            CheckInstallationReady::class,
            EnsureEmailIsVerified::class,
        ]);

        // Balanced モード（スキャン必須）
        Cache::put('security_settings:'.'extension_security_preset', 'balanced');

        // plugin.json が存在するが監査レコードがない状態をモック
        $fileMock = File::partialMock();
        $fileMock->shouldReceive('exists')
            ->withArgs(fn (string $path) => str_ends_with($path, 'plugins/TestPlugin'))
            ->andReturn(true);
        $fileMock->shouldReceive('exists')
            ->withArgs(fn (string $path) => str_ends_with($path, 'plugin.json'))
            ->andReturn(true);
        $fileMock->shouldReceive('get')
            ->withArgs(fn (string $path) => str_ends_with($path, 'plugin.json'))
            ->andReturn(json_encode(['slug' => 'test-plugin', 'name' => 'TestPlugin']));

        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.plugins.install'), [
                'directory' => 'TestPlugin',
            ]);

        // エラーでリダイレクトされること
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /**
     * スキャン必須モードでスキャン済みプラグインのインストールが許可されること
     */
    public function test_install_allowed_when_scan_required_and_scanned(): void
    {
        $this->withoutMiddleware([
            CheckInstallationReady::class,
            EnsureEmailIsVerified::class,
        ]);

        // Balanced モード（スキャン必須）
        Cache::put('security_settings:'.'extension_security_preset', 'balanced');

        // 監査レコードを作成
        PluginAudit::create([
            'plugin_slug' => 'test-plugin',
            'has_mismatches' => false,
            'matches_count' => 5,
            'total_checked' => 5,
            'risk_level' => 'low',
            'health_score' => 90,
            'health_status' => 'healthy',
            'audited_at' => now(),
        ]);

        // HealthScorer をモックして Allowed を返す
        $healthScorer = Mockery::mock(PluginHealthScorer::class);
        $healthScorer->shouldReceive('calculate')
            ->with('test-plugin')
            ->andReturn(new HealthScoreResult(90, PluginHealthStatus::Healthy));
        $healthScorer->shouldReceive('determineEnableAction')
            ->andReturn(PluginEnableAction::Allowed);
        $this->app->instance(PluginHealthScorer::class, $healthScorer);

        // ファイルシステムをモック
        $fileMock = File::partialMock();
        $fileMock->shouldReceive('exists')
            ->withArgs(fn (string $path) => str_ends_with($path, 'plugins/TestPlugin'))
            ->andReturn(true);
        $fileMock->shouldReceive('exists')
            ->withArgs(fn (string $path) => str_ends_with($path, 'plugin.json'))
            ->andReturn(true);
        $fileMock->shouldReceive('get')
            ->withArgs(fn (string $path) => str_ends_with($path, 'plugin.json'))
            ->andReturn(json_encode(['slug' => 'test-plugin', 'name' => 'TestPlugin']));

        // Artisan コマンドをモック
        Artisan::shouldReceive('call')
            ->with('dls:plugin:install', Mockery::any())
            ->andReturn(0);
        Artisan::shouldReceive('call')
            ->with('dls:plugin:audit', Mockery::any())
            ->andReturn(0);
        Artisan::shouldReceive('output')
            ->andReturn('{}');

        // インストール後に Plugin レコードを作成する
        Plugin::create([
            'name' => 'TestPlugin',
            'directory' => 'TestPlugin',
            'slug' => 'test-plugin',
            'namespace' => 'Plugins\\TestPlugin\\',
            'version' => '1.0.0',
            'installed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.plugins.install'), [
                'directory' => 'TestPlugin',
            ]);

        // 成功でリダイレクトされること
        $response->assertRedirect(route('admin.settings.plugins.index'));
        $response->assertSessionHas('success');
    }

    /**
     * スキャン必須モードでブロック状態のプラグインのインストールが拒否されること
     */
    public function test_install_rejected_when_scanned_but_blocked(): void
    {
        $this->withoutMiddleware([
            CheckInstallationReady::class,
            EnsureEmailIsVerified::class,
        ]);

        // Strict モード（スキャン必須）
        Cache::put('security_settings:'.'extension_security_preset', 'strict');

        // 監査レコードを作成（リスクが高い）
        PluginAudit::create([
            'plugin_slug' => 'test-plugin',
            'has_mismatches' => true,
            'matches_count' => 3,
            'total_checked' => 10,
            'risk_level' => 'high',
            'health_score' => 20,
            'health_status' => 'critical',
            'audited_at' => now(),
        ]);

        // HealthScorer をモックして Blocked を返す
        $healthScorer = Mockery::mock(PluginHealthScorer::class);
        $healthScorer->shouldReceive('calculate')
            ->with('test-plugin')
            ->andReturn(new HealthScoreResult(20, PluginHealthStatus::NeedsAttention, [], true));
        $healthScorer->shouldReceive('determineEnableAction')
            ->andReturn(PluginEnableAction::Blocked);
        $this->app->instance(PluginHealthScorer::class, $healthScorer);

        // ファイルシステムをモック
        $fileMock = File::partialMock();
        $fileMock->shouldReceive('exists')
            ->withArgs(fn (string $path) => str_ends_with($path, 'plugins/TestPlugin'))
            ->andReturn(true);
        $fileMock->shouldReceive('exists')
            ->withArgs(fn (string $path) => str_ends_with($path, 'plugin.json'))
            ->andReturn(true);
        $fileMock->shouldReceive('get')
            ->withArgs(fn (string $path) => str_ends_with($path, 'plugin.json'))
            ->andReturn(json_encode(['slug' => 'test-plugin', 'name' => 'TestPlugin']));

        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.plugins.install'), [
                'directory' => 'TestPlugin',
            ]);

        // エラーでリダイレクトされること
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /**
     * Development モードではスキャンなしでもインストールが許可されること
     */
    public function test_install_allowed_in_development_mode_without_scan(): void
    {
        $this->withoutMiddleware([
            CheckInstallationReady::class,
            EnsureEmailIsVerified::class,
        ]);

        // Development モード（スキャン不要）
        Cache::put('security_settings:'.'extension_security_preset', 'development');

        // ファイルシステムをモック
        $fileMock = File::partialMock();
        $fileMock->shouldReceive('exists')
            ->withArgs(fn (string $path) => str_ends_with($path, 'plugins/TestPlugin'))
            ->andReturn(true);

        // Artisan コマンドをモック
        Artisan::shouldReceive('call')
            ->with('dls:plugin:install', Mockery::any())
            ->andReturn(0);
        Artisan::shouldReceive('call')
            ->with('dls:plugin:audit', Mockery::any())
            ->andReturn(0);
        Artisan::shouldReceive('output')
            ->andReturn('{}');

        // インストール後に Plugin レコードを作成する
        Plugin::create([
            'name' => 'TestPlugin',
            'directory' => 'TestPlugin',
            'slug' => 'test-plugin',
            'namespace' => 'Plugins\\TestPlugin\\',
            'version' => '1.0.0',
            'installed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.plugins.install'), [
                'directory' => 'TestPlugin',
            ]);

        // 成功でリダイレクトされること
        $response->assertRedirect(route('admin.settings.plugins.index'));
        $response->assertSessionHas('success');
    }

    public function test_a_refused_install_shows_the_recorded_reason_not_the_console_output(): void
    {
        $response = $this->refusedPluginInstall(function () {
            app(ExtensionRefusalReport::class)->record('TestPlugin needs Dixlase ^9.0, but this site runs 0.1.19.');

            return 1;
        });

        $response->assertSessionHas(
            'error',
            __('admin/settings/plugins/index.messages.install_failed', ['error' => 'TestPlugin needs Dixlase ^9.0, but this site runs 0.1.19.']),
        );
        Log::shouldHaveReceived('error')->withArgs(fn (string $message, array $context) => $message === 'Plugin installation command failed'
            && $context['exit_code'] === 1
            && $context['output'] === '{}');
    }

    public function test_a_failed_install_without_a_recorded_reason_shows_the_plain_message(): void
    {
        $this->refusedPluginInstall(fn () => 1)
            ->assertSessionHas('error', __('admin/settings/plugins/index.messages.install_failed_no_reason'));
    }

    /**
     * Post an install whose command fails as $command does, with a console
     * buffer ({}) that must not reach the flash message.
     */
    private function refusedPluginInstall(\Closure $command): TestResponse
    {
        $this->withoutMiddleware([
            CheckInstallationReady::class,
            EnsureEmailIsVerified::class,
        ]);
        Cache::put('security_settings:'.'extension_security_preset', 'development');

        $fileMock = File::partialMock();
        $fileMock->shouldReceive('exists')
            ->withArgs(fn (string $path) => str_ends_with($path, 'plugins/TestPlugin'))
            ->andReturn(true);

        Artisan::shouldReceive('call')
            ->with('dls:plugin:install', Mockery::any())
            ->once()
            ->andReturnUsing($command);
        Artisan::shouldReceive('output')
            ->andReturn('{}');
        Log::spy();

        return $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.plugins.install'), [
                'directory' => 'TestPlugin',
            ]);
    }

    // ========================================
    // 監査レスポンスのテスト
    // ========================================

    /**
     * 監査レスポンスに enableAction が含まれること
     */
    public function test_audit_response_contains_enable_action(): void
    {
        $this->withoutMiddleware([
            CheckInstallationReady::class,
            EnsureEmailIsVerified::class,
        ]);

        // プラグインを作成
        Plugin::create([
            'name' => 'TestPlugin',
            'directory' => 'TestPlugin',
            'slug' => 'test-plugin',
            'namespace' => 'Plugins\\TestPlugin\\',
            'version' => '1.0.0',
            'installed_at' => now(),
        ]);

        // Artisan コマンドをモック
        Artisan::shouldReceive('call')
            ->with('dls:plugin:audit', Mockery::any())
            ->andReturn(0);
        Artisan::shouldReceive('output')
            ->andReturn(json_encode([
                'has_mismatches' => false,
                'mismatches' => [],
                'matches_count' => 5,
                'total_checked' => 5,
                'risk_level' => 'low',
            ]));

        // HealthScorer をモックして Allowed を返す
        $healthScorer = Mockery::mock(PluginHealthScorer::class);
        $healthScorer->shouldReceive('calculate')
            ->with('test-plugin')
            ->andReturn(new HealthScoreResult(90, PluginHealthStatus::Healthy));
        $healthScorer->shouldReceive('determineEnableAction')
            ->andReturn(PluginEnableAction::Allowed);
        $this->app->instance(PluginHealthScorer::class, $healthScorer);

        $response = $this->actingAs($this->admin, 'member')
            ->postJson(route('admin.settings.plugins.audit'), [
                'slug' => 'test-plugin',
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'audit',
            'enableAction',
            'enableActionLabel',
            'installAllowed',
        ]);
        $response->assertJson([
            'success' => true,
            'enableAction' => 'allowed',
            'installAllowed' => true,
        ]);
    }
}
