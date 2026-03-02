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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('front_pages', function (Blueprint $table) {
            $table->id();
            $table->string('page_type', 50)->default('main_content');
            $table->string('lang', 10)->default('en');
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->text('custom_js')->nullable();
            $table->text('custom_css')->nullable();
            $table->tinyInteger('storage_type')->default(0);
            $table->tinyInteger('editor_type')->default(3);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->unique(['page_type', 'lang']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('front_pages');
    }
};
