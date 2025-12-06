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
    protected $table = 'plugins_members_role_permissions';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->string('plugin_slug', 100)->index();  // プラグインのスラッグ名（例：dixlase-inquiry）
            $table->string('menu_key', 255);              // メニューキー（例：settings.inquiry）
            $table->string('access_roles', 255)->nullable(); // 編集権限（この値以上の権限を持つユーザーがアクセス可能）
            $table->string('view_roles', 255)->nullable();   // 閲覧権限（この値以上の権限を持つユーザーが閲覧可能）
            $table->timestamps();
            
            // プラグインスラッグとメニューキーの組み合わせでユニーク
            $table->unique(['plugin_slug', 'menu_key'], 'plugin_menu_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->table);
    }
};
