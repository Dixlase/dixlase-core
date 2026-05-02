<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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
     * Backup records table
     * Stores metadata for backups created by backup plugins
     */
    public function up(): void
    {
        Schema::create('backup_records', function (Blueprint $table) {
            $table->id();

            // Multisite scope. Site-scoped backups carry the site id;
            // network-wide backups leave it null.
            $table->unsignedBigInteger('site_id')->nullable()->index();

            // どのプラグインが作成したか
            $table->string('plugin_slug', 100)->index();

            // バックアップ種別: files, database, full
            $table->string('type', 20)->index();

            // 対象: ["core", "plugins", "database", "themes"] etc.
            $table->json('targets');

            // ファイル情報
            $table->string('file_path', 500);
            $table->string('file_name', 255);
            $table->unsignedBigInteger('file_size');

            // 暗号化情報
            $table->boolean('is_encrypted')->default(false)->index();
            $table->string('encryption_algorithm', 30)->nullable();

            // ハッシュ検証情報
            $table->string('hash', 128)->nullable();
            $table->string('hash_algorithm', 20)->nullable();

            // 検証ステータス: unchecked, valid, invalid
            $table->string('verification_status', 20)->default('unchecked')->index();
            $table->timestamp('last_verified_at')->nullable();

            // リテンション
            $table->timestamp('retention_until')->nullable()->index();

            // メタデータ: duration, table_count, file_count, db_size etc.
            $table->json('metadata')->nullable();

            // ステータス: completed, failed, expired, deleted
            $table->string('status', 20)->default('completed')->index();

            $table->timestamps();

            // 複合インデックス
            $table->index(['plugin_slug', 'type', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_records');
    }
};
