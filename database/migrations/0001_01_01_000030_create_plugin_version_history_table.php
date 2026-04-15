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
     *
     * プラグインのバージョン履歴テーブル
     * サプライチェーン攻撃防御のため、インストール・アップデート・ロールバックを記録する
     */
    public function up(): void
    {
        Schema::create('plugin_version_history', function (Blueprint $table) {
            $table->id();
            $table->string('plugin_slug')->index();
            $table->string('old_version')->nullable(); // 初回インストール時は null
            $table->string('new_version');
            $table->string('old_signing_key_id')->nullable();
            $table->string('new_signing_key_id')->nullable();
            $table->string('old_author_id')->nullable();
            $table->string('new_author_id')->nullable();
            $table->unsignedInteger('files_changed_count')->default(0);
            $table->unsignedInteger('lines_added')->default(0);
            $table->unsignedInteger('lines_removed')->default(0);
            $table->boolean('signing_key_changed')->default(false)->index();
            $table->boolean('author_id_changed')->default(false)->index();
            $table->string('installation_method'); // install/update/rollback
            $table->string('installed_from_url')->nullable();
            $table->unsignedBigInteger('applied_by_id')->nullable()->index();
            $table->timestamp('applied_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugin_version_history');
    }
};
