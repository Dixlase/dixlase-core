<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

namespace Tests\Unit\Rules;

use App\Rules\UniqueContentSlug;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * UniqueContentSlug バリデーションルール ユニットテスト
 */
class UniqueContentSlugTest extends TestCase
{
    use RefreshDatabase;

    private string $table = 'test_unique_content_slugs';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create($this->table, function ($table) {
            $table->id();
            $table->string('slug');
            $table->string('category')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists($this->table);

        parent::tearDown();
    }

    /**
     * 重複なしの場合はバリデーションが通る
     */
    public function test_passes_when_no_duplicate(): void
    {
        $rule = UniqueContentSlug::for($this->table);
        $failed = false;

        $rule->validate('slug', 'my-page', function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    /**
     * 同一テーブル内に同じスラッグが存在する場合はエラー
     */
    public function test_fails_when_duplicate_slug_exists(): void
    {
        DB::table($this->table)->insert([
            'slug' => 'my-page',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rule = UniqueContentSlug::for($this->table);
        $failed = false;

        $rule->validate('slug', 'my-page', function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed);
    }

    /**
     * ignore() で指定したレコードは除外される
     */
    public function test_ignores_specified_record(): void
    {
        $id = DB::table($this->table)->insertGetId([
            'slug' => 'my-page',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rule = UniqueContentSlug::for($this->table)->ignore($id);
        $failed = false;

        $rule->validate('slug', 'my-page', function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    /**
     * ignore() で指定したレコード以外の重複はエラーになる
     */
    public function test_fails_when_other_record_has_same_slug(): void
    {
        DB::table($this->table)->insert([
            'slug' => 'my-page',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherId = DB::table($this->table)->insertGetId([
            'slug' => 'other-page',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rule = UniqueContentSlug::for($this->table)->ignore($otherId);
        $failed = false;

        $rule->validate('slug', 'my-page', function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed);
    }

    /**
     * ソフトデリートされたレコードは自動除外される
     */
    public function test_passes_when_only_soft_deleted_record_has_slug(): void
    {
        DB::table($this->table)->insert([
            'slug' => 'my-page',
            'deleted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rule = UniqueContentSlug::for($this->table);
        $failed = false;

        $rule->validate('slug', 'my-page', function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    /**
     * withoutSoftDeletes() でソフトデリート除外を無効化できる
     */
    public function test_fails_with_soft_deleted_when_disabled(): void
    {
        DB::table($this->table)->insert([
            'slug' => 'my-page',
            'deleted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rule = UniqueContentSlug::for($this->table)->withoutSoftDeletes();
        $failed = false;

        $rule->validate('slug', 'my-page', function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed);
    }

    /**
     * where() で追加条件を指定できる
     */
    public function test_where_scopes_query(): void
    {
        DB::table($this->table)->insert([
            'slug' => 'my-page',
            'category' => 'news',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 別カテゴリならパスする
        $rule = UniqueContentSlug::for($this->table)->where('category', 'blog');
        $failed = false;

        $rule->validate('slug', 'my-page', function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);

        // 同カテゴリならエラー
        $rule2 = UniqueContentSlug::for($this->table)->where('category', 'news');
        $failed2 = false;

        $rule2->validate('slug', 'my-page', function () use (&$failed2) {
            $failed2 = true;
        });

        $this->assertTrue($failed2);
    }

    /**
     * 空文字列はスキップされる
     */
    public function test_skips_empty_string(): void
    {
        $rule = UniqueContentSlug::for($this->table);
        $failed = false;

        $rule->validate('slug', '', function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    /**
     * 非文字列はスキップされる
     */
    public function test_skips_non_string(): void
    {
        $rule = UniqueContentSlug::for($this->table);
        $failed = false;

        $rule->validate('slug', 123, function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    /**
     * for() 静的ファクトリーメソッドがインスタンスを返す
     */
    public function test_for_factory_returns_instance(): void
    {
        $rule = UniqueContentSlug::for($this->table);
        $this->assertInstanceOf(UniqueContentSlug::class, $rule);
    }

    /**
     * カスタムカラム名を指定できる
     */
    public function test_custom_column_name(): void
    {
        DB::table($this->table)->insert([
            'slug' => 'my-page',
            'category' => 'unique-category',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // categoryカラムで一意性チェック
        $rule = UniqueContentSlug::for($this->table, 'category');
        $failed = false;

        $rule->validate('category', 'unique-category', function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed);
    }

    /**
     * エラーメッセージに属性名が含まれる
     */
    public function test_error_message_contains_attribute(): void
    {
        app()->setLocale('en');

        DB::table($this->table)->insert([
            'slug' => 'my-page',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rule = UniqueContentSlug::for($this->table);
        $message = null;

        $rule->validate('slug', 'my-page', function ($msg) use (&$message) {
            $message = $msg;
        });

        $this->assertNotNull($message);
        $this->assertStringContainsString('slug', $message);
        $this->assertStringContainsString('already in use', $message);
    }
}
