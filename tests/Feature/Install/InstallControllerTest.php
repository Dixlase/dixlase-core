<?php

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2024 exc-D inc.
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
    public function test_install_page_is_displayed()
    {
        // /install にアクセスし、ステータスコード200が返るかを確認
        $response = $this->get('/install');
        $response->assertStatus(200);
        $response->assertViewIs('install'); // ビューが 'install' であることを確認
    }

    /**
     * インストール処理が成功するかをテスト
     */
    public function test_install_process_works_correctly()
    {
        // テストデータを準備
        $data = [
            'site_name' => 'テストサイト',
            'admin_email' => 'admin@example.com',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'db_host' => '127.0.0.1',
            'db_database' => 'test_database',
            'db_username' => 'test_user',
            'db_password' => 'test_password',
            'mail_host' => 'smtp.example.com',
            'mail_port' => 587,
            'mail_username' => 'user@example.com',
            'mail_password' => 'mailpassword',
        ];

        // /install にPOSTリクエストを送信し、リダイレクトされるか確認
        $response = $this->post('/install', $data);
        $response->assertRedirect('/'); // インストール完了後、トップページにリダイレクトされるか確認

        // .envファイルに INSTALLED=true が追加されているか確認
        $this->assertTrue(strpos(file_get_contents(base_path('.env')), 'INSTALLED=true') !== false);
    }

    /**
     * インストール処理でバリデーションエラーが発生するかをテスト
     */
    public function test_install_process_shows_validation_errors()
    {
        // 不完全なデータを準備（バリデーションエラーを引き起こすため）
        $data = [
            'site_name' => 'テストサイト',
            // 'admin_email' が欠けている
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
        ];

        // /install にPOSTリクエストを送信
        $response = $this->post('/install', $data);
        $response->assertStatus(302); // リダイレクト（バリデーションエラーの場合は302）
        $response->assertSessionHasErrors(['admin_email']); // バリデーションエラーがセッションに含まれているか確認
    }
}
