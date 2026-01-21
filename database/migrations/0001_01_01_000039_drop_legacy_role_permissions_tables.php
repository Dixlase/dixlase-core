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
     * 旧方式の権限テーブルを廃止
     * 新方式では config/roles.php でデフォルト宣言し、
     * role_permission_overrides テーブルに差分のみ保存
     */
    public function up(): void
    {
        // 旧コア権限テーブルを削除
        Schema::dropIfExists('members_role_permissions');
        
        // 旧プラグイン権限テーブルを削除
        Schema::dropIfExists('plugins_members_role_permissions');
    }

    /**
     * Reverse the migrations.
     * 
     * ロールバック時は旧テーブルを再作成
     */
    public function down(): void
    {
        // コア権限テーブルを再作成
        Schema::create('members_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('menu_key')->unique();
            $table->string('access_roles', 255)->nullable();
            $table->string('view_roles', 255)->nullable();
            $table->timestamps();
        });
        
        // プラグイン権限テーブルを再作成
        Schema::create('plugins_members_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('plugin_slug', 100)->index();
            $table->string('menu_key', 255);
            $table->string('access_roles', 255)->nullable();
            $table->string('view_roles', 255)->nullable();
            $table->timestamps();
            
            $table->unique(['plugin_slug', 'menu_key'], 'plugin_menu_unique');
        });
    }
};
