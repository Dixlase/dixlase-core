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
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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

            // Which plugin created this
            $table->string('plugin_slug', 100)->index();

            // Backup type: files, database, full
            $table->string('type', 20)->index();

            // Target: ["core", "plugins", "database", "themes"] etc.
            $table->json('targets');

            // File information
            $table->string('file_path', 500);
            $table->string('file_name', 255);
            $table->unsignedBigInteger('file_size');

            // Encryption information
            $table->boolean('is_encrypted')->default(false)->index();
            $table->string('encryption_algorithm', 30)->nullable();

            // Hash verification information
            $table->string('hash', 128)->nullable();
            $table->string('hash_algorithm', 20)->nullable();

            // Verification status: unchecked, valid, invalid
            $table->string('verification_status', 20)->default('unchecked')->index();
            $table->timestamp('last_verified_at')->nullable();

            // Retention
            $table->timestamp('retention_until')->nullable()->index();

            // Metadata: duration, table_count, file_count, db_size etc.
            $table->json('metadata')->nullable();

            // Status: completed, failed, expired, deleted
            $table->string('status', 20)->default('completed')->index();

            $table->timestamps();

            // Composite index
            $table->index(['plugin_slug', 'type', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_records');
    }
};
