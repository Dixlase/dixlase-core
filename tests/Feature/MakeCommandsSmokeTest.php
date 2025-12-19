<?php

/**
 * This file is part of Dixlase.
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
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * さまざまなファイル作成コマンドを呼び出し、
 * 正常終了（エラーが起きない）かどうかだけチェックするスモークテスト。
 */
class MakeCommandsSmokeTest extends TestCase
{
    /**
     * テストで生成されたファイル/ディレクトリのパスを記録
     */
    protected array $generatedPaths = [];

    /**
     * テスト終了後に生成されたファイルをクリーンアップ
     */
    protected function tearDown(): void
    {
        foreach ($this->generatedPaths as $path) {
            if (File::isDirectory($path)) {
                File::deleteDirectory($path);
            } elseif (File::exists($path)) {
                File::delete($path);
            }
        }

        parent::tearDown();
    }

    /**
     * 生成されるファイルパスを記録
     */
    protected function trackGeneratedFile(string $path): void
    {
        $this->generatedPaths[] = $path;
    }

    public function test_plugin_make_controller()
    {
        $this->trackGeneratedFile(base_path('plugins/MyPlugin'));

        $exitCode = Artisan::call('make:plugin:controller', [
            'plugin' => 'MyPlugin',
            'name'   => 'MyPluginController',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:plugin:controller ended with an error.');
    }

    public function test_custom_make_controller()
    {
        $this->trackGeneratedFile(base_path('custom/app/Http/Controllers/MyCustomController.php'));

        $exitCode = Artisan::call('make:custom:controller', [
            'name'   => 'MyCustomController',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:custom:controller ended with an error.');
    }

    public function test_plugin_make_factory()
    {
        $this->trackGeneratedFile(base_path('plugins/MyPlugin'));

        $exitCode = Artisan::call('make:plugin:factory', [
            'plugin' => 'MyPlugin',
            'name'   => 'MyPluginFactory',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:plugin:factory ended with an error.');
    }

    public function test_custom_make_factory()
    {
        $this->trackGeneratedFile(base_path('custom/database/factories/MyCustomFactory.php'));

        $exitCode = Artisan::call('make:custom:factory', [
            'name'   => 'MyCustomFactory',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:custom:factory ended with an error.');
    }

    public function test_custom_make_event()
    {
        $this->trackGeneratedFile(base_path('custom/app/Events/MyCustomEvent.php'));

        $exitCode = Artisan::call('make:custom:event', [
            'name'   => 'MyCustomEvent',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:custom:event ended with an error.');
    }

    public function test_plugin_make_event()
    {
        $this->trackGeneratedFile(base_path('plugins/MyPlugin'));

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
        $this->trackGeneratedFile(app_path('Jobs'));

        $exitCode = Artisan::call('make:job', [
            'name' => 'MyTestJob',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:job ended with an error.');
    }

    public function test_make_listener_no_error()
    {
        $this->trackGeneratedFile(app_path('Listeners/MyTestListener.php'));

        $exitCode = Artisan::call('make:listener', [
            'name' => 'MyTestListener',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:listener ended with an error.');
    }

    public function test_make_middleware_no_error()
    {
        $this->trackGeneratedFile(app_path('Http/Middleware/MyTestMiddleware.php'));

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
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:migration ended with an error.');

        // マイグレーションファイルはタイムスタンプ付きなのでパターンで削除
        $files = glob(database_path('migrations/*_create_test_table.php'));
        foreach ($files as $file) {
            $this->trackGeneratedFile($file);
        }
    }

    public function test_make_model_no_error()
    {
        $this->trackGeneratedFile(app_path('Models/MyTestModel.php'));

        $exitCode = Artisan::call('make:model', [
            'name' => 'MyTestModel',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:model ended with an error.');
    }

    public function test_make_notification_no_error()
    {
        $this->trackGeneratedFile(app_path('Notifications/MyTestNotification.php'));

        $exitCode = Artisan::call('make:notification', [
            'name' => 'MyTestNotification',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:notification ended with an error.');
    }

    public function test_make_policy_no_error()
    {
        $this->trackGeneratedFile(app_path('Policies/MyTestPolicy.php'));

        $exitCode = Artisan::call('make:policy', [
            'name' => 'MyTestPolicy',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:policy ended with an error.');
    }

    public function test_make_request_no_error()
    {
        $this->trackGeneratedFile(app_path('Http/Requests/MyTestRequest.php'));

        $exitCode = Artisan::call('make:request', [
            'name' => 'MyTestRequest',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:request ended with an error.');
    }

    public function test_make_seeder_no_error()
    {
        $this->trackGeneratedFile(database_path('seeders/MyTestSeeder.php'));

        $exitCode = Artisan::call('make:seeder', [
            'name' => 'MyTestSeeder',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:seeder ended with an error.');
    }

    public function test_make_provider_no_error()
    {
        $this->trackGeneratedFile(app_path('Providers/MyTestServiceProvider.php'));

        $exitCode = Artisan::call('make:provider', [
            'name' => 'MyTestServiceProvider',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:provider ended with an error.');
    }

    public function test_make_test_no_error()
    {
        $this->trackGeneratedFile(base_path('tests/Feature/MyTestClass.php'));

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
        $this->trackGeneratedFile(app_path('Traits/MyTestTrait.php'));

        $exitCode = Artisan::call('make:trait', [
            'name' => 'MyTestTrait',
            '--force' => true,
        ]);
        $this->assertEquals(0, $exitCode, 'make:trait ended with an error.');
    }
}
