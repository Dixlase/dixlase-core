<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * さまざまなファイル作成コマンドを呼び出し、
 * 正常終了（エラーが起きない）かどうかだけチェックするスモークテスト。
 */
class MakeCommandsSmokeTest extends TestCase
{
    public function test_plugin_make_controller()
    {
        $exitCode = Artisan::call('make:plugin:controller', [
            'plugin' => 'MyPlugin',
            'name'   => 'MyPluginController',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:plugin:controller ended with an error.');
    }

    public function test_custom_make_controller()
    {
        $exitCode = Artisan::call('make:custom:controller', [
            'name'   => 'MyCustomController',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:custom:controller ended with an error.');
    }

    public function test_plugin_make_factory()
    {
        $exitCode = Artisan::call('make:plugin:factory', [
            'plugin' => 'MyPlugin',
            'name'   => 'MyPluginFactory',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:plugin:factory ended with an error.');
    }

    public function test_custom_make_factory()
    {
        $exitCode = Artisan::call('make:custom:factory', [
            'name'   => 'MyCustomFactory',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:custom:factory ended with an error.');
    }

    public function test_custom_make_event()
    {
        $exitCode = Artisan::call('make:custom:event', [
            'name'   => 'MyCustomEvent',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:custom:event ended with an error.');
    }

    public function test_plugin_make_event()
    {
        $exitCode = Artisan::call('make:plugin:event', [
            'plugin' => 'MyPlugin',
            'name'   => 'MyPluginEvent',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:plugin:event ended with an error.');
    }


    // 以下、Laravel標準のファイル作成コマンド例:
    // ---------------------------------------------------

    public function test_make_job_no_error()
    {
        // e.g. "php artisan make:job MyTestJob --force"
        // (force は無いが例示)
        $exitCode = Artisan::call('make:job', [
            'name' => 'MyTestJob',
            // Laravel標準 "make:job" には --force がある (v10以降)
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:job ended with an error.');
    }

    public function test_make_listener_no_error()
    {
        $exitCode = Artisan::call('make:listener', [
            'name' => 'MyTestListener',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:listener ended with an error.');
    }

    public function test_make_middleware_no_error()
    {
        $exitCode = Artisan::call('make:middleware', [
            'name' => 'MyTestMiddleware',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:middleware ended with an error.');
    }

    public function test_make_migration_no_error()
    {
        $exitCode = Artisan::call('make:migration', [
            'name' => 'create_test_table',
            '--force' => true, // v10+ には --forceある
        ]);
        $this->assertEquals(0, $exitCode, 'make:migration ended with an error.');
    }

    public function test_make_model_no_error()
    {
        $exitCode = Artisan::call('make:model', [
            'name' => 'MyTestModel',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:model ended with an error.');
    }

    public function test_make_notification_no_error()
    {
        $exitCode = Artisan::call('make:notification', [
            'name' => 'MyTestNotification',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:notification ended with an error.');
    }

    public function test_make_policy_no_error()
    {
        $exitCode = Artisan::call('make:policy', [
            'name' => 'MyTestPolicy',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:policy ended with an error.');
    }

    public function test_make_request_no_error()
    {
        $exitCode = Artisan::call('make:request', [
            'name' => 'MyTestRequest',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:request ended with an error.');
    }

    public function test_make_seeder_no_error()
    {
        $exitCode = Artisan::call('make:seeder', [
            'name' => 'MyTestSeeder',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:seeder ended with an error.');
    }

    public function test_make_provider_no_error()
    {
        $exitCode = Artisan::call('make:provider', [
            'name' => 'MyTestServiceProvider',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:provider ended with an error.');
    }

    public function test_make_test_no_error()
    {
        $exitCode = Artisan::call('make:test', [
            'name' => 'MyTestClass',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:test ended with an error.');
    }

    /**
     * Laravelにはデフォルトで "make:trait" は無いが、
     * 独自に "make:trait" コマンドを実装している場合の例
     */
    public function test_make_trait_no_error()
    {
        // 独自コマンドを仮定
        $exitCode = Artisan::call('make:trait', [
            'name' => 'MyTestTrait',
            '--force' => true,
        ]);
        // Laravel標準には無いので、コマンド自体が存在しない場合はエラーになるかもしれません
        $this->assertEquals(0, $exitCode, 'make:trait ended with an error.');
    }
}
