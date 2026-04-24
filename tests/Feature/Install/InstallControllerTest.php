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

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Tests\Feature\Install;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * インストール画面が表示されることをテスト
     */
    public function test_install_page_is_displayed(): void
    {
        $response = $this->get('/install');
        $response->assertStatus(200);
        $response->assertViewIs('install.index');
    }

    /**
     * モード選択画面が表示されることをテスト
     */
    public function test_mode_selection_page_is_displayed(): void
    {
        $response = $this->get('/install/mode');
        $response->assertStatus(200);
        $response->assertViewIs('install.mode');
    }

    /**
     * かんたんモードを選択するとセッションに保存されリダイレクトされることをテスト
     */
    public function test_simple_mode_selection_stores_to_session(): void
    {
        $response = $this->post('/install/mode', [
            'install_mode' => 0,
        ]);

        $response->assertRedirect(route('install.settings'));
        $response->assertSessionHas('install_data.install_mode', 0);
    }

    /**
     * 詳細モードを選択するとセッションに保存されリダイレクトされることをテスト
     */
    public function test_advanced_mode_selection_stores_to_session(): void
    {
        $response = $this->post('/install/mode', [
            'install_mode' => 1,
        ]);

        $response->assertRedirect(route('install.settings'));
        $response->assertSessionHas('install_data.install_mode', 1);
    }

    /**
     * モード選択でバリデーションエラーが発生することをテスト
     */
    public function test_mode_selection_validates_input(): void
    {
        $response = $this->post('/install/mode', [
            'install_mode' => 99,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['install_mode']);
    }

    /**
     * かんたんモード時の環境設定ではapp_urlとapp_timezoneのみ必須であることをテスト
     */
    public function test_simple_mode_environment_requires_minimal_fields(): void
    {
        // かんたんモードをセッションに設定
        $this->withSession([
            'install_data' => [
                'install_mode' => 0,
                'site_name' => 'Test Site',
                'admin_account_name' => 'admin',
                'admin_email' => 'admin@example.com',
                'admin_password' => 'encrypted_password',
                'app_locale' => 'ja',
            ],
        ]);

        $response = $this->post('/install/environment', [
            'app_url' => 'example.com',
            'app_timezone' => 'Asia/Tokyo',
        ]);

        $response->assertRedirect(route('install.database'));
    }

    /**
     * 詳細モード時の環境設定ではすべてのフィールドが必須であることをテスト
     */
    public function test_advanced_mode_environment_requires_all_fields(): void
    {
        // 詳細モードをセッションに設定
        $this->withSession([
            'install_data' => [
                'install_mode' => 1,
                'site_name' => 'Test Site',
                'admin_account_name' => 'admin',
                'admin_email' => 'admin@example.com',
                'admin_password' => 'encrypted_password',
                'app_locale' => 'ja',
            ],
        ]);

        // app_envとadmin_url_prefix/suffixが欠けているのでバリデーションエラー
        $response = $this->post('/install/environment', [
            'app_url' => 'example.com',
            'app_timezone' => 'Asia/Tokyo',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['app_env', 'admin_url_prefix', 'admin_url_suffix']);
    }

    /**
     * モード選択時にランダムな管理画面URLが生成されることをテスト
     * プレフィックスはconfig候補から選択される
     */
    public function test_mode_selection_generates_random_admin_url(): void
    {
        $response = $this->post('/install/mode', [
            'install_mode' => 0,
        ]);

        $adminUrl = session('install_data.admin_url');
        $this->assertNotNull($adminUrl);

        // プレフィックス-サフィックス形式であること
        $this->assertMatchesRegularExpression('/^[a-z]+-[a-z0-9]{4}$/', $adminUrl);

        // プレフィックスがconfig候補に含まれること
        $prefixes = config('admin.url.admin_url_prefixes', ['admin']);
        $parts = explode('-', $adminUrl, 2);
        $this->assertContains($parts[0], $prefixes);
    }

    /**
     * 詳細モードでプレフィックスとサフィックスが正しく結合されることをテスト
     */
    public function test_advanced_mode_combines_prefix_and_suffix(): void
    {
        $this->withSession([
            'install_data' => [
                'install_mode' => 1,
                'site_name' => 'Test Site',
                'admin_account_name' => 'admin',
                'admin_email' => 'admin@example.com',
                'admin_password' => 'encrypted_password',
                'app_locale' => 'ja',
            ],
        ]);

        $response = $this->post('/install/environment', [
            'app_env' => 'production',
            'app_url' => 'example.com',
            'admin_url_prefix' => 'panel',
            'admin_url_suffix' => 'test1234',
            'app_timezone' => 'Asia/Tokyo',
        ]);

        $response->assertRedirect(route('install.database'));
        $this->assertEquals('panel-test1234', session('install_data.admin_url'));
    }

    /**
     * 詳細モードでサフィックスが3文字以下の場合バリデーションエラーになることをテスト
     */
    public function test_advanced_mode_rejects_short_suffix(): void
    {
        $this->withSession([
            'install_data' => [
                'install_mode' => 1,
                'site_name' => 'Test Site',
                'admin_account_name' => 'admin',
                'admin_email' => 'admin@example.com',
                'admin_password' => 'encrypted_password',
                'app_locale' => 'ja',
            ],
        ]);

        $response = $this->post('/install/environment', [
            'app_env' => 'production',
            'app_url' => 'example.com',
            'admin_url_prefix' => 'admin',
            'admin_url_suffix' => 'abc',
            'app_timezone' => 'Asia/Tokyo',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['admin_url_suffix']);
    }

    /**
     * 詳細モードで不正な文字を含むサフィックスが拒否されることをテスト
     */
    public function test_advanced_mode_rejects_invalid_suffix_characters(): void
    {
        $this->withSession([
            'install_data' => [
                'install_mode' => 1,
                'site_name' => 'Test Site',
                'admin_account_name' => 'admin',
                'admin_email' => 'admin@example.com',
                'admin_password' => 'encrypted_password',
                'app_locale' => 'ja',
            ],
        ]);

        $response = $this->post('/install/environment', [
            'app_env' => 'production',
            'app_url' => 'example.com',
            'admin_url_prefix' => 'admin',
            'admin_url_suffix' => 'UPPER_case!',
            'app_timezone' => 'Asia/Tokyo',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['admin_url_suffix']);
    }

    /**
     * 詳細モードで無効なプレフィックスが拒否されることをテスト
     */
    public function test_advanced_mode_rejects_invalid_prefix(): void
    {
        $this->withSession([
            'install_data' => [
                'install_mode' => 1,
                'site_name' => 'Test Site',
                'admin_account_name' => 'admin',
                'admin_email' => 'admin@example.com',
                'admin_password' => 'encrypted_password',
                'app_locale' => 'ja',
            ],
        ]);

        $response = $this->post('/install/environment', [
            'app_env' => 'production',
            'app_url' => 'example.com',
            'admin_url_prefix' => 'hacked',
            'admin_url_suffix' => 'test1234',
            'app_timezone' => 'Asia/Tokyo',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['admin_url_prefix']);
    }
}
