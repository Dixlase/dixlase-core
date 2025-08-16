<?php
/*
This file is part of MySoftware.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
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
        Schema::create('captcha_form_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->comment('キー');
            $table->string('route_name')->comment('ルート名');
            $table->boolean('enabled')->default(false)->comment('reCAPTCHA有効フラグ');
            $table->string('plugin_name', 100)->nullable()->comment('プラグイン名（コア機能の場合はnull）');
            $table->boolean('is_core')->default(false)->comment('コア機能フラグ');
            $table->integer('display_order')->default(0)->comment('表示順序');
            $table->timestamps();
            
            $table->index(['enabled']);
            $table->index(['plugin_name']);
            $table->index(['display_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('captcha_form_settings');
    }
};
