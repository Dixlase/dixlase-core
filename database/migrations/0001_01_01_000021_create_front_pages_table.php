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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // front_pagesテーブル作成
        Schema::create('front_pages', function (Blueprint $table) {
            $table->id();
            $table->string('page_type', 50)->default('main_content')->unique();
            $table->string('storage_type', 20)->default('database');
            $table->string('editor_type', 20)->default('html');
            $table->enum('status', ['draft', 'published'])->default('published');
            $table->timestamps();
        });

        // front_page_translationsテーブル作成
        Schema::create('front_page_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('front_page_id');
            $table->string('locale', 10);
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->timestamps();

            // インデックス
            $table->unique(['front_page_id', 'locale']);
            $table->index('locale');

            // 外部キー制約
            $table->foreign('front_page_id')
                ->references('id')
                ->on('front_pages')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('front_page_translations');
        Schema::dropIfExists('front_pages');
    }
};
