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

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            // プラグインと同じカラムを追加（カラム順序も統一）
            // name は既存
            $table->string('package_name')->nullable()->after('name');
            // directory は既存
            // slug は既存
            $table->string('namespace')->nullable()->after('slug');
            // description は既存（順序調整不要）
            $table->string('license')->nullable()->after('description');
            $table->string('author')->nullable()->after('license');
            $table->string('email')->nullable()->after('author');
            $table->string('web')->nullable()->after('email');
            // version は既存（順序調整不要）
            // config は既存
            // activated_at は 2025_01_17_000001 で追加済み
            // installed_at は 2025_01_17_000001 で追加済み
            // timestamps は既存
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn([
                'package_name',
                'namespace',
                'license',
                'author',
                'email',
                'web',
            ]);
        });
    }
};
