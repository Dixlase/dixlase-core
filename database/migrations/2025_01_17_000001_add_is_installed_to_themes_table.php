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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            // is_active を activated_at に変更
            $table->timestamp('activated_at')->nullable()->after('config');
            // is_installed を installed_at に変更
            $table->timestamp('installed_at')->nullable()->after('activated_at');
        });

        // 既存データの移行
        DB::statement('UPDATE themes SET activated_at = NOW() WHERE is_active = 1');
        
        // 古いカラムを削除
        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('config');
        });

        // データを戻す
        DB::statement('UPDATE themes SET is_active = 1 WHERE activated_at IS NOT NULL');

        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn(['activated_at', 'installed_at']);
        });
    }
};
