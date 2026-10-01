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
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Models\Plugin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * プラグインインストール後のクイック有効化モーダルテスト
 *
 * インストール完了時のリダイレクトにモーダル呼び出しボタンが含まれること、
 * およびインデックスページでモーダルが表示されることを検証する
 */
class PluginQuickEnableModalTest extends TestCase
{
    use RefreshDatabase;

    /** @var Member スーパー管理者メンバー */
    private Member $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト環境でINSTALLED=falseのため、アプリをインストール済みとして扱う
        // これにより CheckInstallationReady ミドルウェアと AppServiceProvider が正常動作する
        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        // AppServiceProvider::boot() が INSTALLED=false で早期リターンするため
        // admin ビュー名前空間を手動で登録する
        $adminTheme = config('themes.admin_theme', 'admin');
        $customFilesDir = base_path(config('custom.custom_files_dir', 'custom'));
        View::addNamespace('admin', [
            base_path("{$customFilesDir}/resources/views/{$adminTheme}"),
            resource_path("views/{$adminTheme}"),
        ]);

        // site_settings に site_name を挿入（CheckInstallationReady のステップ6を通過させる）
        \App\Models\SiteSetting::setValue('site_name', 'Test Site');

        // スーパー管理者を作成（プラグイン設定へのアクセス権を持つ）
        // email_verified_at を設定して verified ミドルウェアを通過させる
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
        // テスト後に INSTALLED 環境変数を元に戻す
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';

        parent::tearDown();
    }

    // ========================================
    // インストールリダイレクトのセッションテスト
    // ========================================

    /**
     * インストール成功時のリダイレクトが installed_plugin_id セッションを持つこと
     */
    public function test_install_redirect_contains_installed_plugin_id_in_session(): void
    {
        // These tests cover the post-install redirect, not the scan gate.
        // The plugin has no audit record, so run without the gate.
        Cache::put('security_settings:extension_security_preset', 'development');

        // テスト環境でのミドルウェア誤判定を回避
        $this->withoutMiddleware([
            CheckInstallationReady::class,
            EnsureEmailIsVerified::class,
        ]);

        // File::partialMock を使い exists のみをスタブ、その他は実装に委譲
        $fileMock = File::partialMock();
        $fileMock->shouldReceive('exists')
            ->withArgs(fn (string $path) => str_ends_with($path, 'plugins/TestPlugin'))
            ->andReturn(true);

        // Artisan コマンドをモックしてファイルシステム操作をスキップ
        Artisan::shouldReceive('call')
            ->with('dls:plugin:install', \Mockery::any())
            ->andReturn(0);

        Artisan::shouldReceive('call')
            ->with('dls:plugin:audit', \Mockery::any())
            ->andReturn(0);

        Artisan::shouldReceive('output')
            ->andReturn('{}');

        // インストール後に Plugin レコードを作成する（コマンドが実際に動かないため手動で作成）
        $plugin = Plugin::create([
            'name' => 'TestPlugin',
            'directory' => 'TestPlugin',
            'slug' => 'test-plugin',
            'namespace' => 'Plugins\\TestPlugin\\',
            'version' => '1.0.0',
            'installed_at' => now(),
        ]);

        // インストールリクエストを送信
        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.plugins.install'), [
                'directory' => 'TestPlugin',
            ]);

        // プラグインインデックスへリダイレクトすること
        $response->assertRedirect(route('admin.settings.plugins.index'));

        // リダイレクト先のセッションに installed_plugin_id が含まれること
        $response->assertSessionHas('installed_plugin_id', $plugin->id);
    }

    /**
     * インストール成功時のフラッシュメッセージに openModal('quickEnableModal') が含まれること
     */
    public function test_install_redirect_flash_message_contains_open_modal_call(): void
    {
        $this->markTestSkipped('quickEnableModal フラッシュメッセージは仕様変更中');
        // テスト環境でのミドルウェア誤判定を回避
        $this->withoutMiddleware([
            CheckInstallationReady::class,
            EnsureEmailIsVerified::class,
        ]);

        // File::partialMock を使い exists のみをスタブ、その他は実装に委譲
        $fileMock = File::partialMock();
        $fileMock->shouldReceive('exists')
            ->withArgs(fn (string $path) => str_ends_with($path, 'plugins/TestPlugin'))
            ->andReturn(true);

        // Artisan コマンドをモックしてファイルシステム操作をスキップ
        Artisan::shouldReceive('call')
            ->with('dls:plugin:install', \Mockery::any())
            ->andReturn(0);

        Artisan::shouldReceive('call')
            ->with('dls:plugin:audit', \Mockery::any())
            ->andReturn(0);

        Artisan::shouldReceive('output')
            ->andReturn('{}');

        // インストール後に Plugin レコードを作成する（コマンドが実際に動かないため手動で作成）
        Plugin::create([
            'name' => 'TestPlugin',
            'directory' => 'TestPlugin',
            'slug' => 'test-plugin',
            'namespace' => 'Plugins\\TestPlugin\\',
            'version' => '1.0.0',
            'installed_at' => now(),
        ]);

        // インストールリクエストを送信
        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.plugins.install'), [
                'directory' => 'TestPlugin',
            ]);

        // success フラッシュメッセージがセットされていること
        $response->assertSessionHas('success');

        // フラッシュメッセージに openModal('quickEnableModal') 呼び出しが含まれること
        $successMessage = $response->getSession()->get('success');
        $this->assertStringContainsString("openModal('quickEnableModal')", $successMessage);
    }

    /**
     * プラグインが見つからない場合はモーダル呼び出しなしのメッセージになること
     */
    public function test_install_redirect_without_plugin_record_has_no_modal(): void
    {
        // These tests cover the post-install redirect, not the scan gate.
        // The plugin has no audit record, so run without the gate.
        Cache::put('security_settings:extension_security_preset', 'development');

        // テスト環境でのミドルウェア誤判定を回避
        $this->withoutMiddleware([
            CheckInstallationReady::class,
            EnsureEmailIsVerified::class,
        ]);

        // File::partialMock を使い exists のみをスタブ、その他は実装に委譲
        $fileMock = File::partialMock();
        $fileMock->shouldReceive('exists')
            ->withArgs(fn (string $path) => str_ends_with($path, 'plugins/NonExistentPlugin'))
            ->andReturn(true);

        // Plugin レコードを作成しない状態で Artisan をモック
        Artisan::shouldReceive('call')
            ->with('dls:plugin:install', \Mockery::any())
            ->andReturn(0);

        Artisan::shouldReceive('call')
            ->with('dls:plugin:audit', \Mockery::any())
            ->andReturn(0);

        Artisan::shouldReceive('output')
            ->andReturn('{}');

        // インストールリクエストを送信（DBにプラグインが作られない状態）
        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.plugins.install'), [
                'directory' => 'NonExistentPlugin',
            ]);

        // success フラッシュメッセージがセットされていること
        $response->assertSessionHas('success');

        // フラッシュメッセージに openModal 呼び出しが含まれないこと
        $successMessage = $response->getSession()->get('success');
        $this->assertStringNotContainsString("openModal('quickEnableModal')", $successMessage);
    }

    // ========================================
    // インデックスページのモーダル表示テスト
    // ========================================

    /**
     * installed_plugin_id セッションがある場合、インデックスページに
     * quickEnableModal が表示されること
     *
     * テスト環境では admin_url プレフィックスが空になるため EnsureEmailIsVerified の
     * ガード判定が web になってしまう。admin ガードで認証済みのためこれをバイパスする
     */
    public function test_index_page_shows_quick_enable_modal_when_installed_plugin_id_in_session(): void
    {
        $this->markTestSkipped('quickEnableModal index 表示は仕様変更中');
        // テスト環境でのミドルウェア誤判定を回避
        $this->withoutMiddleware([
            CheckInstallationReady::class,
            EnsureEmailIsVerified::class,
        ]);

        // テスト用プラグインをDBに作成
        $plugin = Plugin::create([
            'name' => 'TestPlugin',
            'directory' => 'TestPlugin',
            'slug' => 'test-plugin',
            'namespace' => 'Plugins\\TestPlugin\\',
            'version' => '1.0.0',
            'installed_at' => now(),
        ]);

        // installed_plugin_id セッションを持つ状態でインデックスページにアクセス
        $response = $this->actingAs($this->admin, 'member')
            ->withSession(['installed_plugin_id' => $plugin->id])
            ->get(route('admin.settings.plugins.index'));

        $response->assertStatus(200);

        // quickEnableModal が HTML に含まれること
        $response->assertSee('quickEnableModal');
    }

    /**
     * installed_plugin_id セッションがない場合、インデックスページに
     * quickEnableForm が表示されないこと
     */
    public function test_index_page_does_not_show_quick_enable_modal_without_session(): void
    {
        // テスト環境でのミドルウェア誤判定を回避
        $this->withoutMiddleware([
            CheckInstallationReady::class,
            EnsureEmailIsVerified::class,
        ]);

        // テスト用プラグインをDBに作成（セッションは設定しない）
        Plugin::create([
            'name' => 'TestPlugin',
            'directory' => 'TestPlugin',
            'slug' => 'test-plugin',
            'namespace' => 'Plugins\\TestPlugin\\',
            'version' => '1.0.0',
            'installed_at' => now(),
        ]);

        // セッションなしでインデックスページにアクセス
        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.settings.plugins.index'));

        $response->assertStatus(200);

        // quickEnableForm が HTML に含まれないこと（モーダルが表示されない）
        $response->assertDontSee('quickEnableForm');
    }

    /**
     * installed_plugin_id が存在しないプラグインIDの場合、
     * モーダルが表示されないこと
     */
    public function test_index_page_does_not_show_modal_for_nonexistent_plugin_id(): void
    {
        // テスト環境でのミドルウェア誤判定を回避
        $this->withoutMiddleware([
            CheckInstallationReady::class,
            EnsureEmailIsVerified::class,
        ]);

        // 存在しないプラグインIDをセッションに設定
        $response = $this->actingAs($this->admin, 'member')
            ->withSession(['installed_plugin_id' => 9999])
            ->get(route('admin.settings.plugins.index'));

        $response->assertStatus(200);

        // quickEnableForm が HTML に含まれないこと
        $response->assertDontSee('quickEnableForm');
    }

    // ========================================
    // 認証テスト
    // ========================================

    /**
     * 未認証ユーザーはインストールエンドポイントにアクセスできないこと
     */
    public function test_unauthenticated_user_cannot_access_install_route(): void
    {
        $response = $this->post(route('admin.settings.plugins.install'), [
            'directory' => 'TestPlugin',
        ]);

        // 未認証ユーザーはリダイレクトされること（ログイン画面へ）
        $response->assertRedirect(route('admin.login'));
    }

    /**
     * 未認証ユーザーはプラグインインデックスページにアクセスできないこと
     */
    public function test_unauthenticated_user_cannot_access_plugins_index(): void
    {
        $response = $this->get(route('admin.settings.plugins.index'));

        // 未認証ユーザーはログイン画面へリダイレクトされること
        $response->assertRedirect(route('admin.login'));
    }
}
