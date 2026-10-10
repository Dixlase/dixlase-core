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

namespace Tests\Feature\Admin;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMenuAccess;
use App\Http\Middleware\CheckMenuEdit;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Models\Plugin;
use App\Models\SiteSetting;
use App\Services\ExtensionOperationService;
use App\Services\Plugin\PluginHealthScorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * プラグインライフサイクル回帰テスト
 *
 * 公開プラグイン 5 種（Pages / Menus / Legal / Inquiry / OfficialDocs）と
 * 署名鍵を保持する 2 種（Authority / Signer）を対象に、enable / disable /
 * re-enable / uninstall フローでデータ保持・状態遷移・後続フローへの影響を検証する。
 *
 * 実 Artisan コマンドはテスト環境のファイルシステム前提を満たさないことが
 * 多いためモック化し、コントローラ → コマンド呼び出しの引数と DB 副作用に
 * フォーカスして回帰検知する。
 */
class PluginLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    /**
     * 公開 5 プラグインの基本メタデータ
     *
     * 各プラグインを単一の連想配列としてラップして返す。
     * PHPUnit はトップレベルの連想配列キーをテストメソッドの名前付き引数とみなすため、
     * 実データ自体は内側の配列に格納し、テストメソッドは単一引数で受け取る。
     *
     * @return array<string, array{0: array{name: string, slug: string, directory: string, namespace: string}}>
     */
    public static function publicPluginProvider(): array
    {
        return [
            'Pages' => [[
                'name' => 'Dixlase Pages',
                'slug' => 'dixlase-pages',
                'directory' => 'DixlasePages',
                'namespace' => 'Plugins\\DixlasePages',
            ]],
            'Menus' => [[
                'name' => 'Dixlase Menus',
                'slug' => 'dixlase-menus',
                'directory' => 'DixlaseMenus',
                'namespace' => 'Plugins\\DixlaseMenus',
            ]],
            'Legal' => [[
                'name' => 'Dixlase Legal',
                'slug' => 'dixlase-legal',
                'directory' => 'DixlaseLegal',
                'namespace' => 'Plugins\\DixlaseLegal',
            ]],
            'Inquiry' => [[
                'name' => 'Dixlase Inquiry',
                'slug' => 'dixlase-inquiry',
                'directory' => 'DixlaseInquiry',
                'namespace' => 'Plugins\\DixlaseInquiry',
            ]],
            'OfficialDocs' => [[
                'name' => 'Dixlase Official Docs',
                'slug' => 'dixlase-official-docs',
                'directory' => 'DixlaseOfficialDocs',
                'namespace' => 'Plugins\\DixlaseOfficialDocs',
            ]],
        ];
    }

    /**
     * 署名鍵を保持するプラグインのメタデータ
     *
     * @return array<string, array{0: array{name: string, slug: string, directory: string, namespace: string}}>
     */
    public static function signingPluginProvider(): array
    {
        return [
            'Authority' => [[
                'name' => 'Dixlase Authority',
                'slug' => 'dixlase-authority',
                'directory' => 'DixlaseAuthority',
                'namespace' => 'Plugins\\DixlaseAuthority',
            ]],
            'Signer' => [[
                'name' => 'DixlaseSigner',
                'slug' => 'dixlase-signer',
                'directory' => 'DixlaseSigner',
                'namespace' => 'Plugins\\DixlaseSigner',
            ]],
        ];
    }

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

        $this->admin = Member::create([
            'account_name' => 'lifecycleadmin',
            'display_name' => 'Lifecycle Admin',
            'email' => 'lifecycle@example.com',
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
        parent::tearDown();
    }

    /**
     * Plugin DB レコードを生成
     *
     * @param  array{name: string, slug: string, directory: string, namespace: string}  $meta
     */
    private function createPluginRecord(array $meta, bool $enabled = false): Plugin
    {
        return Plugin::create([
            'name' => $meta['name'],
            'package_name' => 'dixlase/'.$meta['slug'],
            'directory' => $meta['directory'],
            'namespace' => $meta['namespace'].'\\',
            'slug' => $meta['slug'],
            'version' => '1.0.0',
            'author' => 'exc-D inc.',
            'license' => 'GPL-3.0',
            'description' => 'Test plugin record',
            'installed_at' => now(),
            'enabled_at' => $enabled ? now() : null,
        ]);
    }

    /**
     * ExtensionOperationService をモックして通知の副作用を切り離す
     */
    private function mockExtensionOperationService(): void
    {
        $mock = Mockery::mock(ExtensionOperationService::class);
        $mock->shouldReceive('recordOperation')->andReturnNull();
        $this->app->instance(ExtensionOperationService::class, $mock);
    }

    /**
     * PluginHealthScorer をモックして有効化判定を pass にする
     */
    private function mockHealthScorer(): void
    {
        $scorer = Mockery::mock(PluginHealthScorer::class);
        $scorer->shouldReceive('needsRescan')->andReturn(false);
        $scorer->shouldReceive('calculate')->andReturn(
            new \App\DTO\Plugin\HealthScoreResult(
                score: 100,
                status: \App\Enums\PluginHealthStatus::Healthy,
                issues: [],
                hasCriticalIssue: false,
            )
        );
        $scorer->shouldReceive('determineEnableAction')->andReturn(
            \App\Enums\PluginEnableAction::Allowed
        );
        $this->app->instance(PluginHealthScorer::class, $scorer);
    }

    /**
     * 5 公開プラグインに対し disable → re-enable で DB レコードのコア属性が保持される
     */
    #[DataProvider('publicPluginProvider')]
    public function test_public_plugin_disable_then_reenable_preserves_record(array $meta): void
    {
        $this->mockExtensionOperationService();
        $this->mockHealthScorer();

        $plugin = $this->createPluginRecord($meta, enabled: true);
        $originalId = $plugin->id;
        $originalVersion = $plugin->version;

        // disable コマンドが呼ばれたら enabled_at を null に
        Artisan::shouldReceive('call')
            ->with('dls:plugin:disable', ['pluginName' => $meta['name']])
            ->once()
            ->andReturnUsing(function ($cmd, $opts) use ($meta) {
                Plugin::where('name', $meta['name'])->update(['enabled_at' => null]);

                return 0;
            });

        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.plugins.disable', $plugin->id));

        $response->assertRedirect();

        $disabled = Plugin::find($originalId);
        $this->assertNotNull($disabled, 'disable 後にレコードが消失した');
        $this->assertNull($disabled->enabled_at, 'enabled_at が null にならなかった');
        $this->assertSame($meta['slug'], $disabled->slug, 'slug が変更された');
        $this->assertSame($originalVersion, $disabled->version, 'version が変更された');

        // 再有効化コマンドのモック
        Artisan::shouldReceive('call')
            ->with('dls:plugin:audit', Mockery::any())
            ->andReturn(0);
        Artisan::shouldReceive('output')->andReturn('{}');
        Artisan::shouldReceive('call')
            ->with('dls:plugin:enable', ['pluginName' => $meta['name'], '--force' => true])
            ->once()
            ->andReturnUsing(function ($cmd, $opts) use ($meta) {
                Plugin::where('name', $meta['name'])->update(['enabled_at' => now()]);

                return 0;
            });

        // PluginPermissionService::getSummary もモックが必要
        $permissionService = Mockery::mock(\App\Services\Plugin\PluginPermissionService::class);
        $permissionService->shouldReceive('getSummary')->andReturn(['risk_level' => 'low']);
        $this->app->instance(\App\Services\Plugin\PluginPermissionService::class, $permissionService);

        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.plugins.enable', $plugin->id));

        $response->assertRedirect();

        $reenabled = Plugin::find($originalId);
        $this->assertNotNull($reenabled->enabled_at, '再有効化で enabled_at が設定されない');
        $this->assertSame($originalId, $reenabled->id, 'id が変わってしまった');
        $this->assertSame($meta['slug'], $reenabled->slug);
    }

    /**
     * 有効化中のプラグインは uninstall を拒否する（disable 必須）
     */
    #[DataProvider('publicPluginProvider')]
    public function test_uninstall_rejected_when_plugin_still_enabled(array $meta): void
    {
        $this->mockExtensionOperationService();
        $plugin = $this->createPluginRecord($meta, enabled: true);

        // Artisan コマンドは呼ばれてはいけない
        Artisan::shouldReceive('call')->never();

        $response = $this->actingAs($this->admin, 'member')
            ->from(route('admin.settings.plugins.show', $plugin->slug))
            ->post(route('admin.settings.plugins.uninstall', $plugin->id));

        $response->assertSessionHas('error');
        $this->assertNotNull(
            Plugin::find($plugin->id),
            '有効化中の uninstall でレコードが消えた'
        );
    }

    /**
     * 5 公開プラグインの uninstall で Artisan コマンドが呼ばれ、レコードが消える
     */
    #[DataProvider('publicPluginProvider')]
    public function test_uninstall_removes_record_and_routes_become_unavailable(array $meta): void
    {
        $this->mockExtensionOperationService();

        $plugin = $this->createPluginRecord($meta, enabled: false);
        $originalId = $plugin->id;

        // 実際の uninstall コマンドを模倣して DB レコードを削除
        Artisan::shouldReceive('call')
            ->with('dls:plugin:uninstall', Mockery::on(function ($options) use ($meta) {
                return ($options['pluginName'] ?? null) === $meta['name']
                    && ($options['--force'] ?? null) === true;
            }))
            ->once()
            ->andReturnUsing(function ($cmd, $opts) use ($meta) {
                Plugin::where('name', $meta['name'])->delete();

                return 0;
            });

        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.plugins.uninstall', $plugin->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertNull(
            Plugin::find($originalId),
            'uninstall 後に Plugin レコードが残っている'
        );

        // 後続の uninstall リクエストはルートモデルバインディングで 404
        $response2 = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.plugins.uninstall', $originalId));
        $response2->assertNotFound();
    }

    /**
     * own_tables: true のプラグインで remove_db_data フラグが --rollback 付きで伝播する
     */
    #[DataProvider('publicPluginProvider')]
    public function test_uninstall_with_remove_db_data_passes_rollback_option(array $meta): void
    {
        $this->mockExtensionOperationService();
        $plugin = $this->createPluginRecord($meta, enabled: false);

        $capturedOptions = [];
        Artisan::shouldReceive('call')
            ->with('dls:plugin:uninstall', Mockery::on(function ($options) use (&$capturedOptions) {
                $capturedOptions = $options;

                return true;
            }))
            ->once()
            ->andReturnUsing(function ($cmd, $opts) use ($meta) {
                Plugin::where('name', $meta['name'])->delete();

                return 0;
            });

        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.plugins.uninstall', $plugin->id), [
                'remove_db_data' => '1',
            ]);

        $response->assertRedirect();
        $this->assertArrayHasKey('--rollback', $capturedOptions, '--rollback オプションが渡されていない');
        $this->assertTrue(
            $capturedOptions['--rollback'],
            'remove_db_data チェック時に --rollback が true で伝播していない（DB データが drop されない回帰）'
        );
    }

    /**
     * 署名鍵プラグイン（Authority / Signer）は disable してもレコードと version が保持される
     *
     * これらは uninstall フロー想定外（CLI 専用）だが、enable/disable で
     * authority_key_id 等の列が破壊されないことが必須。
     */
    #[DataProvider('signingPluginProvider')]
    public function test_signing_plugin_disable_preserves_authority_metadata(array $meta): void
    {
        $this->mockExtensionOperationService();
        $this->mockHealthScorer();

        $plugin = Plugin::create([
            'name' => $meta['name'],
            'package_name' => 'dixlase/'.$meta['slug'],
            'directory' => $meta['directory'],
            'namespace' => $meta['namespace'].'\\',
            'slug' => $meta['slug'],
            'version' => '1.0.0',
            'author' => 'exc-D inc.',
            'license' => 'GPL-3.0',
            'authority_key_id' => 'dixlase-authority-2026',
            'author_id' => 'exc-d-inc',
            'installed_at' => now(),
            'enabled_at' => now(),
        ]);

        Artisan::shouldReceive('call')
            ->with('dls:plugin:disable', ['pluginName' => $meta['name']])
            ->once()
            ->andReturnUsing(function ($cmd, $opts) use ($meta) {
                Plugin::where('name', $meta['name'])->update(['enabled_at' => null]);

                return 0;
            });

        $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.plugins.disable', $plugin->id))
            ->assertRedirect();

        $disabled = Plugin::find($plugin->id);
        $this->assertNotNull($disabled);
        $this->assertSame(
            'dixlase-authority-2026',
            $disabled->authority_key_id,
            'authority_key_id が disable 操作で消失した（鍵情報破壊の回帰）'
        );
        $this->assertSame('exc-d-inc', $disabled->author_id);
        $this->assertSame('1.0.0', $disabled->version);
    }
}
